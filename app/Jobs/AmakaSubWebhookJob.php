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


class AmakaSubWebhookJob extends SpatieProcessWebhookJob
{
    use IntegrationsTrait;
    use WalletTrait;
    use ReferenceTrait;
    use OrderTrait;

    public function handle()
    {
        // $this->webhookCall // contains an instance of `WebhookCall`

        $payload = $this->webhookCall->payload;

        $response = $payload;
        \Log::info("AMAKASUB PORTAL WEBHOOK RESPONSE");
        \Log::info($response);

        $ref = $response['txn_id'];

        $oldOrder = Order::where('ref', $ref)->where('status', 0)->first();
        
        if (!is_null($oldOrder)) {
            $subcategory = Subcategory::findOrFail($oldOrder->subcategory_id);
            
            if ($response['status'] == "success") {

                // $updateOrder = Order::where('ref', $ref)->first();
                $oldOrder->status = 1;
                $resp = $response['response'];
                $oldOrder->response = $resp;
                $oldOrder->save();
                $msg = $resp . " AMAKASUBPORTAL Ref: " . $resp;
                //$subcategory =  Subcategory::find($oldOrder->subcategory_id);

            }elseif ($response['status'] == "error") {
                
                $response_msg = $response['response'];
                    
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
