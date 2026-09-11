<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Subcategory;
use App\Models\User;
use App\Services\FundingRecoveryService;
use App\Traits\IntegrationsTrait;
use App\Traits\ReferenceTrait;
use App\Traits\WalletTrait;
use Illuminate\Support\Facades\DB;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob as SpatieProcessWebhookJob;


class PalmpayProcessWebhookJob extends SpatieProcessWebhookJob
{
    use IntegrationsTrait;
    use WalletTrait;
    use ReferenceTrait;

    public function handle()
    {

        //$this->webhookCall // contains an instance of `WebhookCall`

        //echo "yes";

        // perform the work here

        $payload = $this->webhookCall;

        \Log::info("Payload from Palmpay");

        \Log::info($payload);
        $payload = $payload['payload'];

        $response = $payload;



        if ($this->verifyPalmPayTrans($payload) == 1) {
            if($payload['orderStatus'] == 1 || $payload['orderStatus'] == 2){
                $grossAmount = round(
                    ((float) ($response['amount'] ?? $response['orderAmount'] ?? 0)) / 100,
                    2
                );
                $merchantReference = (string) ($response['orderId'] ?? '');
                $gatewayReference = (string) ($response['orderNo'] ?? '');
                $virtualAccount = (string) (
                    $response['virtualAccountNo'] ?? $response['payerVirtualAccNo'] ?? ''
                );

                DB::transaction(function () use (
                    $grossAmount,
                    $merchantReference,
                    $gatewayReference,
                    $virtualAccount
                ) {
                    // PalmPay sends our temporary-account reference as orderId;
                    // orderNo is PalmPay's own platform reference.
                    $intent = $merchantReference !== ''
                        ? Order::where('ref', $merchantReference)->first()
                        : null;
                    $reference = $intent ? $merchantReference : $gatewayReference;
                    if ($reference === '' || $grossAmount <= 0) {
                        throw new \RuntimeException('PalmPay webhook is missing required payment details.');
                    }
                    $findUser = $intent
                        ? User::whereKey($intent->user_id)->lockForUpdate()->first()
                        : User::where('palmpay_account', $virtualAccount)->lockForUpdate()->first();

                    if (!$findUser) {
                        throw new \RuntimeException('PalmPay funding user could not be identified.');
                    }

                    $order = Order::where('ref', $reference)->lockForUpdate()->first();
                    if ($order && (int) $order->status === 1) {
                        return;
                    }

                    $isOneTime = $order && $order->plan === 'PalmPay One-Time Account';
                    $charge = $isOneTime
                        ? round($grossAmount * 0.01, 2)
                        : min(300, round($grossAmount * 0.005, 2));
                    $creditAmount = round($grossAmount - $charge, 2);
                    if ($creditAmount <= 0 || !$this->isCredited($creditAmount, $findUser->id)) {
                        throw new \RuntimeException('PalmPay wallet credit failed.');
                    }

                    $before = round((float) $findUser->wallet, 2);
                    $after = round((float) $findUser->fresh()->wallet, 2);
                    $subcategory = Subcategory::where('title', 'Palmpay')->firstOrFail();
                    $order = $order ?: new Order();
                    $order->ref = $reference;
                    $order->user_id = $findUser->id;
                    $order->subcategory_id = $subcategory->id;
                    $order->plan = $isOneTime ? 'PalmPay One-Time Account' : $subcategory->title;
                    $order->amount = $creditAmount;
                    $order->quantity = 1;
                    $order->bal = $after;
                    $order->prev_bal = $before;
                    $order->subtotal = $creditAmount;
                    $order->total = $creditAmount;
                    $order->description = $isOneTime
                        ? 'PalmPay One-Time Account Auto Credited'
                        : $subcategory->title . ' Auto Credited';
                    $order->response = sprintf(
                        'PalmPay deposit of N%s confirmed. Charge: N%s. Wallet credited: N%s.',
                        number_format($grossAmount, 2, '.', ''),
                        number_format($charge, 2, '.', ''),
                        number_format($creditAmount, 2, '.', '')
                    );
                    $order->channel = 'Web';
                    $order->status = 1;
                    $order->save();
                    app(FundingRecoveryService::class)->apply($order, 'PalmPay', $grossAmount, $charge);
                }, 3);
            }
            
        }
        
        return response('success', 200)->header('Content-Type','text/plain');
    }

}
