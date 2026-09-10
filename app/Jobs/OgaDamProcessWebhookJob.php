<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Subcategory;
use App\Traits\WalletTrait;
use App\Traits\TelegramTrait;
use App\Traits\ReferenceTrait;
use App\Traits\IntegrationsTrait;
use Illuminate\Support\Facades\Log;
use App\Traits\OrderTrait;

use Spatie\WebhookClient\Jobs\ProcessWebhookJob as SpatieProcessWebhookJob;


class OgaDamProcessWebhookJob extends SpatieProcessWebhookJob
{
    use IntegrationsTrait;
    use WalletTrait;
    use ReferenceTrait; 
    use TelegramTrait;
    use OrderTrait;

    public function handle()
    {
        // $this->webhookCall // contains an instance of `WebhookCall`

        // perform the work here

        $payload = $this->webhookCall->payload;

        $response = $payload;
        Log::debug($response['event']['data']['reference']);

        $ref = $response['event']['data']['reference'];




        $oldOrder = Order::where('ref', $ref)->where('status', 0)->first();

        if (!is_null($oldOrder)) {
            if ($response['code'] != 200) {
                
                $response_msg = null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $oldOrder->subtotal,
                        [
                            'bal' => $oldOrder->prev_bal,
                            'prev_bal' => $oldOrder->bal
                        ]
                    );


                
                $this->sendTelegramMessage($oldOrder->amount, $oldOrder->phone, $subcategory->pins, "", $ref, $subcategory->telegram);
            } else {

                // $updateOrder = Order::where('ref', $ref)->first();
                $oldOrder->status = 1;
                $oldOrder->response = $response['event']['data']['msg'];
                $oldOrder->save();
                $msg = $response['event']['data']['msg'] . " OGADAM Ref: " . $response['event']['data']['msg'];
                $subcategory =  Subcategory::find($oldOrder->subcategory_id);
                $this->sendTelegramMessage($oldOrder->amount, $oldOrder->phone, $subcategory->pins, "", $ref, $subcategory->telegram, $msg);
            }
        }
    }
}
