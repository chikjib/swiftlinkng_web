<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Subcategory;
use App\Services\FundingRecoveryService;
use App\Traits\WalletTrait;
use App\Traits\TelegramTrait;
use App\Traits\ReferenceTrait;
use App\Traits\IntegrationsTrait;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob as SpatieProcessWebhookJob;


class BudpayProcessWebhookJob extends SpatieProcessWebhookJob
{
    use IntegrationsTrait;
    use WalletTrait;
    use ReferenceTrait;
    use TelegramTrait;

    public function handle()
    {
        // $this->webhookCall // contains an instance of `WebhookCall`

        // perform the work here

        $payload = $this->webhookCall->payload;

        $response = $payload['data'];
        
        \Log::info("Budpay Webhook response");
        \Log::info($response);

        $reference = $response['reference'];

        $requested_amount =  $response['requested_amount'];

        $fees = 0.014 * $requested_amount;

        $amount = $requested_amount - $fees;

        $verify_response = $this->VerifyBudpay($reference);
        $budpay_verify = $verify_response;

        $budpay_status = $budpay_verify['status'];

        $budpay_data_status = $budpay_verify['data']['status'];

        //$response = $payload;

        if ($budpay_status == true && $budpay_data_status == "success") {

                    $findUser = User::where('email', $response['customer']['email'])->first();

                    if (!is_null($findUser)) {

                        $findOrder = Order::where('ref', $reference)->first();

                        if (is_null($findOrder)) {
                            //$com = 0.0085 * $response['settlementAmount'];
                            // $amount = $response['amountPaid'];
                            $amt = $amount;
                            //- $com;
                            // $amount = $response['amountPaid'];
                            //$amt = $amount - 50;
                            // $amt = $amount;
                            if ($this->isCredited($amt, $findUser->id)) {

                                $subcategory =  Subcategory::where('title', 'budpay')->first();

                                $ref = $reference;
                                $order = new Order();
                                $order->ref = $ref;
                                $order->user_id =  $findUser->id;
                                $order->subcategory_id =  $subcategory->id;
                                $order->plan =  $subcategory->title;
                                $order->amount = $amt;
                                $order->quantity =  1;
                                $order->bal = $findUser->wallet + $amt;
                                $order->prev_bal = $findUser->wallet;
                                $order->subtotal =  $amt;
                                $order->total = $amt;
                                $order->description = $subcategory->title . " RA: Auto Credited";
                                $order->response = sprintf(
                                    'BudPay deposit of N%s confirmed. Charge: N%s. Wallet credited: N%s.',
                                    number_format((float) $requested_amount, 2, '.', ''),
                                    number_format((float) $fees, 2, '.', ''),
                                    number_format((float) $amt, 2, '.', '')
                                );
                                $order->status = 1;
                                $order->save();
                                app(FundingRecoveryService::class)->apply(
                                    $order,
                                    'BudPay',
                                    (float) $requested_amount,
                                    (float) $fees
                                );
                            }
                        }
                    }
            }
    }
}
