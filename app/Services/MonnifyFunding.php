<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MonnifyFunding
{
    // Preserve the existing Monnify funding restrictions.
    private const BLOCKED_EMAILS = [
        'salihusanusi853@gmail.com', 'polmimusa9@gmail.com', 'adamshauwa744@gmail.com',
        'davetech261@gmail.com', 'preciousadeola42@gmail.com', 'mojeedbolaji340@gmail.com',
        'musamuhammad9215@gmail.com', 'rayyanumuazu5@gmail.com', 'jonathanazubike90@gmail.com',
    ];

    public function credit(array $payment): Order
    {
        $reference = $payment['transactionReference'] ?? '';
        $email = $payment['customer']['email'] ?? '';
        $net = $payment['settlementAmount'] ?? null;
        $gross = $payment['amountPaid'] ?? null;
        if (!is_string($reference) || $reference === '' || !is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || ($payment['paymentStatus'] ?? '') !== 'PAID' || ($payment['currency'] ?? '') !== 'NGN'
            || !is_numeric($net) || !is_numeric($gross) || !is_finite((float) $net) || !is_finite((float) $gross)
            || round((float) $net, 2) <= 0 || (float) $gross < (float) $net) {
            throw ValidationException::withMessages(['transaction_reference' => 'Monnify returned invalid payment, currency, customer or amount details.']);
        }
        return DB::transaction(function () use ($reference, $email, $net, $gross) {
            // Every Monnify webhook and repush takes this lock before checking references.
            $user = User::where('email', $email)->lockForUpdate()->first();
            if (!$user) {
                throw ValidationException::withMessages(['transaction_reference' => 'No user matches the Monnify customer email.']);
            }
            if (Order::where('ref', $reference)->orWhere('monnify_reference', $reference)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['transaction_reference' => 'This transaction is already recorded. No additional funding was made.']);
            }
            if (in_array(strtolower($user->email), self::BLOCKED_EMAILS, true)) {
                throw ValidationException::withMessages(['transaction_reference' => 'Wallet funding is restricted for this customer.']);
            }
            $subcategory = Subcategory::where('title', 'Monnify')->firstOrFail();
            $amount = round((float) $net, 2);
            $order = new Order();
            $order->ref = $reference;
            $order->monnify_reference = $reference;
            $order->user_id = $user->id;
            $order->subcategory_id = $subcategory->id;
            $order->plan = $subcategory->title;
            $order->amount = $amount;
            $order->quantity = 1;
            $order->subtotal = $amount;
            $order->total = $amount;
            $order->prev_bal = $user->wallet;
            $user->wallet = round((float) $user->wallet + $amount, 2);
            $user->save();
            $order->bal = $user->wallet;
            $order->description = 'Monnify: Verified wallet funding';
            $order->status = 1;
            $order->save();
            app(FundingRecoveryService::class)->apply($order, 'Monnify', (float) $gross, round((float) $gross - $amount, 2));
            return $order->fresh();
        }, 3);
    }
}
