<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Subcategory;
use App\Models\User;
use App\Services\OpayDigitalWalletService;
use App\Services\FundingRecoveryService;
use App\Traits\WalletTrait;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob as SpatieProcessWebhookJob;

class ProcessOpayWalletDeposit extends SpatieProcessWebhookJob
{
    use WalletTrait;

    public $tries = 8;
    public $backoff = [10, 30, 60, 120, 300, 600];

    public function handle(OpayDigitalWalletService $opay)
    {
        $payload = (array) $this->webhookCall->payload;
        if (is_array($payload['data'] ?? null)) {
            $payload = $payload['data'];
        }

        if (strtoupper((string) ($payload['status'] ?? 'PENDING')) !== 'SUCCESS') {
            return;
        }

        $transactionId = (string) ($payload['transactionId'] ?? '');
        $orderNo = (string) ($payload['orderNo'] ?? '');
        $depositCode = (string) ($payload['depositCode'] ?? '');
        $notifiedAmount = self::parseAmount($payload['depositAmount'] ?? 0);
        if ($transactionId === '' || $depositCode === '' || $notifiedAmount <= 0) {
            throw new RuntimeException('OPay webhook is missing required deposit details.');
        }

        $date = now('Africa/Lagos');
        if (!empty($payload['formatDateTime'])) {
            $date = \Carbon\Carbon::parse($payload['formatDateTime'], 'Africa/Lagos');
        }
        $history = $opay->transactionHistory([
            'depositCodeList' => [$depositCode],
            'startDate' => $date->copy()->subDay()->format('Ymd'),
            'endDate' => $date->copy()->addDay()->format('Ymd'),
            'pageIndex' => 1,
            'pageSize' => 200,
        ]);
        $items = collect($history['data']['transactionList'] ?? []);
        $match = $items->first(function ($item) use ($transactionId, $orderNo) {
            return in_array($transactionId, [
                (string) ($item['transactionId'] ?? ''), (string) ($item['orderNo'] ?? ''),
                (string) ($item['outChannelOrderNo'] ?? ''),
            ], true) || ($orderNo !== '' && (string) ($item['orderNo'] ?? '') === $orderNo);
        });
        if (!$match || strtoupper((string) ($match['orderStatus'] ?? '')) !== 'SUCCESS') {
            throw new RuntimeException('OPay deposit is not yet confirmed by transaction-history re-query.');
        }
        $verifiedAmount = self::parseAmount($match['orderAmount'] ?? $notifiedAmount);
        if ($verifiedAmount <= 0 || abs($verifiedAmount - $notifiedAmount) > 0.01) {
            \Log::warning('opay.deposit_amount_mismatch', [
                'transaction_id' => $transactionId,
                'order_no' => $orderNo,
                'notified_amount' => $notifiedAmount,
                'verified_amount' => $verifiedAmount,
                'raw_verified_amount' => $match['orderAmount'] ?? null,
            ]);
            throw new RuntimeException('OPay deposit amount did not match the webhook notification.');
        }
        $feePercent = max(0, (float) config('services.opay_wallet.fee_percent', 0.4));
        $fee = min(150, round($verifiedAmount * ($feePercent / 100), 2));
        $creditAmount = round($verifiedAmount - $fee, 2);
        if ($creditAmount <= 0) {
            throw new RuntimeException('OPay deposit is not enough to cover the funding charge.');
        }

        DB::transaction(function () use ($transactionId, $depositCode, $verifiedAmount, $fee, $creditAmount) {
            $user = User::where('opay_wallet_number', $depositCode)->lockForUpdate()->firstOrFail();

            // The completed funding order is the idempotency record, matching
            // the existing PalmPay and Monnify wallet-funding integrations.
            if (Order::where('ref', $transactionId)->exists()) {
                return;
            }

            $before = round((float) $user->wallet, 2);
            if (!$this->isCredited($creditAmount, $user->id)) {
                throw new RuntimeException('Unable to credit the Swiftlink wallet for the OPay deposit.');
            }
            $after = round((float) $user->fresh()->wallet, 2);

            $subcategory = Subcategory::where('title', 'OPay')->first()
                ?: Subcategory::where('title', 'Manual')->firstOrFail();
            $order = new Order();
            $order->ref = $transactionId;
            $order->user_id = $user->id;
            $order->subcategory_id = $subcategory->id;
            $order->plan = 'OPay Digital Wallet Funding';
            $order->amount = $creditAmount;
            $order->quantity = 1;
            $order->subtotal = $creditAmount;
            $order->total = $creditAmount;
            $order->bal = $after;
            $order->prev_bal = $before;
            $order->channel = 'App';
            $order->description = 'OPay Digital Wallet Auto Credited';
            $order->response = sprintf(
                'OPay deposit of N%s confirmed. Charge: N%s. Wallet credited: N%s.',
                number_format($verifiedAmount, 2, '.', ''),
                number_format($fee, 2, '.', ''),
                number_format($creditAmount, 2, '.', '')
            );
            $order->status = 1;
            $order->save();
            app(FundingRecoveryService::class)->apply($order, 'OPay', $verifiedAmount, $fee);
        }, 3);
    }

    public static function parseAmount($value): float
    {
        if (is_int($value) || is_float($value)) {
            return round((float) $value, 2);
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', (string) $value);
        if ($normalized === '' || !is_numeric($normalized)) {
            return 0.0;
        }

        return round((float) $normalized, 2);
    }
}
