<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Subcategory;
use App\Models\User;
use App\Traits\IntegrationsTrait;
use App\Traits\ReferenceTrait;
use App\Traits\WalletTrait;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob as SpatieProcessWebhookJob;


class RaveProcessWebhookJob extends SpatieProcessWebhookJob
{
    use IntegrationsTrait;
    use WalletTrait;
    use ReferenceTrait;

    public function handle()
    {
        // $this->webhookCall // contains an instance of `WebhookCall`

        // perform the work here

        $payload = $this->webhookCall;

        $response = $payload['data'];

        if ($response['status'] == 'successful') {

            // $fundLimitsubCategory =  Subcategory::where('title', 'Upgrade')->first();
            // $fundproductCollection = collect(json_decode($fundLimitsubCategory->products));
            // $getSelectedLevel = $fundproductCollection->where('title', $this->translateLevel($this->user->userlevel))->first();



            // if ($request->amount < $getSelectedLevel->fundmin) {
            //     return $this->sendError('You cannot fund below ' . $getSelectedLevel->fundmin, 'You cannot fund below ' . $getSelectedLevel->fundmin);
            // }
            // if ($request->amount > $getSelectedLevel->fundmax) {
            //     return $this->sendError('You cannot fund above ' . $getSelectedLevel->fundmax, 'You cannot fund above ' . $getSelectedLevel->fundmax);
            // }

            $findOrder = Order::where('ref', $response['txRef'])->first();
            if (is_null($findOrder)) {
                $findUser = User::where('email', $response['customer']['email'])->first();

                if (!is_null($findUser)) {
                    if ($this->isCredited($response['amount'], $findUser->id)) {

                        $subcategory =  Subcategory::where('title', 'Rave')->first();

                        $ref = $response['txRef'];
                        $order = new Order();
                        $order->ref = $ref;
                        $order->user_id =  $findUser->id;
                        $order->subcategory_id =  $subcategory->id;
                        $order->plan =  $subcategory->title;
                        $order->amount = $response['amount'];
                        $order->quantity =  1;
                        $order->bal = $findUser->wallet + $response['amount'];
                        $order->prev_bal = $findUser->wallet;
                        $order->subtotal =  $response['amount'];
                        $order->total = $response['amount'];
                        $order->description = $subcategory->title . " Auto Credited";
                        $order->response = sprintf(
                            'Rave deposit of N%s confirmed. Charge: N0.00. Wallet credited: N%s.',
                            number_format((float) $response['amount'], 2, '.', ''),
                            number_format((float) $response['amount'], 2, '.', '')
                        );
                        $order->status = 1;
                        $order->save();
                    }
                }
            }
        }
    }
}
