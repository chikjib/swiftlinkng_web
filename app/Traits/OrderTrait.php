<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Order;
use App\Models\RefundLog;
use Illuminate\Support\Facades\DB;



trait OrderTrait
{


    private function refundUser(string $ref, $response, $amountActual, array $extraData = [])
    {
        return DB::transaction(function () use ($ref, $response, $amountActual, $extraData) {
            // Fetch original order
            $order = Order::where('ref', $ref)->lockForUpdate()->first();
            if (!$order) {
                throw new \Exception("Order with ref {$ref} not found");
            }

            // ✅ Prevent duplicate refunds
            if (RefundLog::where('ref', $ref)->exists()) {
                return "Already refunded for reference {$ref}";
            }

            // ✅ Update original order as failed
            if ($order->status !== 4) {
                $order->status = 4; // failed
                $order->save();
            }

            // ✅ Credit the user only once
            $this->isCredited($amountActual, $order->user_id);

            // ✅ Duplicate order with overrides
            $newOrder = $order->replicate();
            $newOrder->status = 2; // reversed
            $newOrder->response = $response;

            foreach ($extraData as $key => $value) {
                $newOrder->{$key} = $value;
            }

            $newOrder->save();

            // ✅ Log refund to prevent re-crediting
            RefundLog::create([
                'ref' => $ref,
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'credited_amount' => $amountActual
            ]);

            return $newOrder;
        });
    }
    
    private function reverseConfirmedOrder($pendingOrders, $amount, $bal, $prev)
    {
        return DB::transaction(function () use ($pendingOrders, $amount, $bal, $prev) {
            // Lock the order to prevent concurrent updates
            $pendingOrders = Order::where('id', $pendingOrders->id)
                ->lockForUpdate()
                ->first();

            if (!$pendingOrders) {
                throw new \Exception("Pending order not found");
            }

            // ✅ Prevent duplicate reversal by checking RefundLog
            if (RefundLog::where('ref', $pendingOrders->ref)->exists()) {
                return "Already reversed for reference {$pendingOrders->ref}";
            }

            // ✅ Credit the user only if not credited before
            if ($this->isCredited($amount, $pendingOrders->user_id)) {
                $pendingOrders->status = 2; // reversed
                $pendingOrders->bal = $bal;
                $pendingOrders->prev_bal = $prev;
                $pendingOrders->save();

                // ✅ Log reversal to prevent duplicates
                RefundLog::create([
                    'ref' => $pendingOrders->ref,
                    'order_id' => $pendingOrders->id,
                    'user_id' => $pendingOrders->user_id,
                    'credited_amount' => $amount
                ]);

                return 'done';
            }

            return "Credit action skipped (already credited)";
        });
    }
    
    private function normalizeBundleMessage(string $message): string
    {
        /**
         * Pattern breakdown:
         * - Bundle purchase of Nxxx/<bundle> for Recharge of NGN xxx.xx
         * - Status phrase: "has been done for" OR "is in progress for"
         * - Then phone
         */
        $pattern = '/Bundle purchase of\s+[^\/\s]+\/\s*' .
                   '([0-9]+(?:\.[0-9]+)?\s*(?:GB|MB)\/\s*\d+\s*day(?:s)?)' .
                   '\s*for\s+Recharge\s+of\s+NGN\s+[^\s]+' .
                   '\s*(?:has\s+been\s+done|is\s+in\s+progress)\s+for\s*' .
                   '([0-9]+)/i';
    
        return preg_replace_callback($pattern, function ($m) {
            $bundle = preg_replace('/\s+/', ' ', trim($m[1]));
            $phone = trim($m[2]);
            return "Bundle purchase of {$bundle} plan is Successful to {$phone}";
        }, $message) ?? $message;
    }


}
