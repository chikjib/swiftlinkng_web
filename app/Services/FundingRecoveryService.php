<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FundingRecoveryService
{
    private const RECOVERY_PLAN = 'Automatic Funding Recovery';

    public function apply(Order $fundingOrder, string $provider, float $grossAmount, float $charge): array
    {
        return DB::transaction(function () use ($fundingOrder, $provider, $grossAmount, $charge) {
            $order = Order::query()->lockForUpdate()->findOrFail($fundingOrder->id);
            $user = User::query()->lockForUpdate()->findOrFail($order->user_id);
            $email = strtolower(trim((string) $user->email));
            $targets = array_change_key_case(config('funding_recoveries', []), CASE_LOWER);
            $target = round((float) ($targets[$email] ?? 0), 2);
            $creditBeforeRecovery = round(max(0, (float) $order->amount), 2);
            $recoveryReference = 'FDR-'.strtoupper(substr(hash('sha256', $email.'|'.$order->ref), 0, 24));
            $existingRecovery = Order::query()
                ->where('ref', $recoveryReference)
                ->where('plan', self::RECOVERY_PLAN)
                ->where('status', 1)
                ->first();

            $deduction = $existingRecovery ? round((float) $existingRecovery->amount, 2) : 0.0;
            if (!$existingRecovery && $target > 0 && $creditBeforeRecovery > 0) {
                $alreadyRecovered = round((float) Order::query()
                    ->where('user_id', $user->id)
                    ->where('plan', self::RECOVERY_PLAN)
                    ->where('status', 1)
                    ->sum('amount'), 2);
                $outstanding = round(max(0, $target - $alreadyRecovered), 2);
                $deduction = round(min($creditBeforeRecovery, $outstanding), 2);

                if ($deduction > 0) {
                    $balanceBeforeDebit = round((float) $user->wallet, 2);
                    $balanceAfterDebit = round($balanceBeforeDebit - $deduction, 2);
                    $user->wallet = $balanceAfterDebit;
                    $user->save();

                    $subcategory = Subcategory::where('title', 'Manual')->first()
                        ?: Subcategory::find($order->subcategory_id);
                    if (!$subcategory) {
                        throw new \RuntimeException('A subcategory is required to record an automatic funding recovery.');
                    }

                    $recovery = new Order();
                    $recovery->ref = $recoveryReference;
                    $recovery->user_id = $user->id;
                    $recovery->subcategory_id = $subcategory->id;
                    $recovery->plan = self::RECOVERY_PLAN;
                    $recovery->amount = $deduction;
                    $recovery->quantity = 1;
                    $recovery->subtotal = $deduction;
                    $recovery->total = $deduction;
                    $recovery->prev_bal = $balanceBeforeDebit;
                    $recovery->bal = $balanceAfterDebit;
                    $recovery->channel = 'System';
                    $recovery->description = 'Automatic debit recovered from a new wallet deposit.';
                    $recovery->response = sprintf(
                        'N%s recovered from this deposit. Outstanding recovery: N%s.',
                        number_format($deduction, 2, '.', ''),
                        number_format(max(0, $outstanding - $deduction), 2, '.', '')
                    );
                    $recovery->status = 1;
                    Order::withoutEvents(function () use ($recovery) {
                        $recovery->save();
                    });

                    Log::warning('Automatic wallet funding recovery applied', [
                        'user_id' => $user->id,
                        'funding_order_id' => $order->id,
                        'funding_reference' => $order->ref,
                        'recovery_reference' => $recoveryReference,
                        'amount' => $deduction,
                        'remaining' => round(max(0, $outstanding - $deduction), 2),
                    ]);
                }
            }

            $walletCredit = round(max(0, $creditBeforeRecovery - $deduction), 2);
            $order->amount = $walletCredit;
            $order->subtotal = $walletCredit;
            $order->total = $walletCredit;
            $order->bal = round((float) $user->fresh()->wallet, 2);
            $order->response = $deduction > 0
                ? sprintf(
                    '%s deposit of N%s confirmed. Charge: N%s. Automatic debit: N%s. Wallet credited: N%s.',
                    $provider,
                    number_format($grossAmount, 2, '.', ''),
                    number_format($charge, 2, '.', ''),
                    number_format($deduction, 2, '.', ''),
                    number_format($walletCredit, 2, '.', '')
                )
                : sprintf(
                    '%s deposit of N%s confirmed. Charge: N%s. Wallet credited: N%s.',
                    $provider,
                    number_format($grossAmount, 2, '.', ''),
                    number_format($charge, 2, '.', ''),
                    number_format($walletCredit, 2, '.', '')
                );
            Order::withoutEvents(function () use ($order) {
                $order->save();
            });

            return [
                'deducted' => $deduction,
                'wallet_credit' => $walletCredit,
                'wallet_balance' => (float) $order->bal,
            ];
        }, 3);
    }
}
