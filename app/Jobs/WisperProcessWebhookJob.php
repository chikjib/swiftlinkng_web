<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Subcategory;
use App\Traits\WalletTrait;
use App\Traits\ReferenceTrait;
use App\Traits\IntegrationsTrait;
use App\Traits\OrderTrait;

use Spatie\WebhookClient\Jobs\ProcessWebhookJob as SpatieProcessWebhookJob;


class WisperProcessWebhookJob extends SpatieProcessWebhookJob
{
    use IntegrationsTrait;
    use WalletTrait;
    use ReferenceTrait;
    use OrderTrait;

    public function handle()
    {
        // $this->webhookCall // contains an instance of `WebhookCall`

        // perform the work here

        $payload = $this->webhookCall->payload;

        $response = $payload;
        \Log::info("WISPER WEBHOOK RESPONSE");
        \Log::info($response);
        //if($response['transaction']['status'])

        $ref = $response['transaction']['customer_reference'];

        $oldOrder = Order::where('ref', $ref)->where('status', 0)->first();


        if (!is_null($oldOrder)) {
            $subcategory = Subcategory::findOrFail($oldOrder->subcategory_id);

            if ($response['transaction']['status'] == "success") {

                // $updateOrder = Order::where('ref', $ref)->first();
                $oldOrder->status = 1;
                $resp = $response['transaction']['response'];
                $oldOrder->response = $resp;
                $oldOrder->save();
                $msg = $resp . " SMEPLUG Ref: " . $ref;
                //$subcategory =  Subcategory::find($oldOrder->subcategory_id);

               // $this->sendTelegramMessage($oldOrder->amount, $oldOrder->phone, $subcategory->pins, "", $ref, $subcategory->telegram, $msg);
            }elseif ($response['transaction']['status'] == "failed") {
                
                $response_msg = $response['transaction']['response'];
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $oldOrder->subtotal,
                        [
                            'bal' => $oldOrder->prev_bal,
                            'prev_bal' => $oldOrder->bal
                        ]
                    );

                
                

            }
        }

    }
}
