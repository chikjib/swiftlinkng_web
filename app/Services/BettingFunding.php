<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BettingFunding
{
    public function __construct(private PayVesselBetting $provider) {}

    public function purchase(int $userId, array $input): object
    {
        $existing = DB::table('betting_fundings')->where('reference', $input['reference'])->first();
        if ($existing) return $this->matching($existing, $userId, $input);
        [$biller, $name] = $this->provider->validatePurchase($input);
        $subcategory = ServiceControls::enabled('betting')->betting_subcategory_id;
        abort_unless($subcategory && DB::table('subcategory')->where('id', $subcategory)->exists(), 503, 'Betting is being set up. Please try again later.');
        [$funding, $created] = DB::transaction(function () use ($userId, $input, $biller, $name, $subcategory) {
            ServiceControls::enabled('betting', true);
            $user = User::whereKey($userId)->lockForUpdate()->firstOrFail();
            $existing = DB::table('betting_fundings')->where('reference', $input['reference'])->first();
            if ($existing) return [$this->matching($existing, $userId, $input), false];
            $amount = (int) $input['amount'];
            if ($user->wallet < $amount) throw ValidationException::withMessages(['amount' => 'Insufficient wallet balance.']);
            $before = $user->wallet;
            $user->decrement('wallet', $amount);
            // Shared ledger, without Eloquent outbound webhook side effects inside the wallet transaction.
            $orderId = DB::table('orders')->insertGetId([
                'ref' => $input['reference'], 'user_id' => $userId, 'subcategory_id' => $subcategory,
                'plan' => $biller['biller_name'].' betting funding', 'amount' => $amount,
                'quantity' => 1, 'subtotal' => $amount, 'total' => $amount,
                'phone' => $input['recharge_account'], 'channel' => 'App',
                'prev_bal' => $before, 'bal' => $before - $amount,
                'description' => 'Betting funding — '.$biller['biller_name'], 'status' => 0,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('betting_fundings')->insert([
                'user_id' => $userId, 'order_id' => $orderId, 'reference' => $input['reference'],
                'biller_id' => $input['biller_id'], 'biller_name' => $biller['biller_name'],
                'item_id' => $input['item_id'], 'recharge_account' => $input['recharge_account'],
                'account_name' => $name, 'amount' => $amount, 'status' => 'pending',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            return [DB::table('betting_fundings')->where('reference', $input['reference'])->first(), true];
        });
        if (!$created) return $funding;
        try {
            $data = $this->provider->request('POST', 'orders', array_intersect_key($input,
                array_flip(['biller_id', 'item_id', 'recharge_account', 'amount', 'reference'])));
            return $this->settle($funding, $data);
        } catch (\Throwable $e) {
            // Includes non-2xx and duplicate-reference responses: reconcile, never blindly refund/retry.
            return $funding;
        }
    }

    private function matching(object $funding, int $userId, array $input): object
    {
        abort_unless((int) $funding->user_id === $userId, 409, 'Reference is already in use.');
        foreach (['biller_id', 'item_id', 'recharge_account', 'amount'] as $key) {
            abort_unless((string) $funding->$key === (string) $input[$key], 409, 'Reference belongs to a different payment.');
        }
        return $funding;
    }

    public function refresh(object $funding): object
    {
        if (in_array($funding->status, ['success', 'failed', 'cancelled'], true)) return $funding;
        try {
            return $this->settle($funding, $this->provider->request('GET', 'orders/verify/'.rawurlencode($funding->reference)));
        } catch (\Throwable $e) {
            return $funding;
        }
    }

    public function settle(object $funding, array $data): object
    {
        // Bind a provider response to the exact debit before accepting a terminal state.
        foreach (['biller_id', 'item_id', 'recharge_account'] as $key) {
            if (($data[$key] ?? null) !== $funding->$key) return $funding;
        }
        if (($data['merchant_reference'] ?? null) !== $funding->reference ||
            (float) ($data['amount'] ?? -1) !== (float) $funding->amount ||
            !in_array($data['status'] ?? null, ['pending', 'processing', 'success', 'failed', 'cancelled'], true)) return $funding;
        return DB::transaction(function () use ($funding, $data) {
            $user = User::whereKey($funding->user_id)->lockForUpdate()->firstOrFail();
            $row = DB::table('betting_fundings')->where('id', $funding->id)->lockForUpdate()->first();
            if (in_array($row->status, ['success', 'failed', 'cancelled'], true)) return $row;
            $status = $data['status'];
            if (in_array($status, ['failed', 'cancelled'], true)) $user->increment('wallet', $row->amount);
            DB::table('betting_fundings')->where('id', $row->id)->update(['status' => $status, 'updated_at' => now()]);
            DB::table('orders')->where('id', $row->order_id)->update([
                'status' => $status === 'success' ? 1 : (in_array($status, ['failed', 'cancelled'], true) ? 2 : 0),
                'description' => $row->biller_name.' betting funding: '.$status,
                'updated_at' => now(),
            ]);
            return DB::table('betting_fundings')->where('id', $row->id)->first();
        });
    }
}
