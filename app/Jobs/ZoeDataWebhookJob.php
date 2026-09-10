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


class ZoeDataWebhookJob extends SpatieProcessWebhookJob
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
        \Log::info("ZOEDATA PORTAL WEBHOOK RESPONSE");
        \Log::info($response);

        $ref = $response['user_reference'];

        $oldOrder = Order::where('ref', $ref)->where('status', 0)->first();
        
        if (!is_null($oldOrder)) {
            $subcategory = Subcategory::findOrFail($oldOrder->subcategory_id);
            
            if ($response['status'] == "COMPLETED" || $response['status'] == 'Done') {

                // $updateOrder = Order::where('ref', $ref)->first();
                $oldOrder->status = 1;
                $resp = isset($response['true_response']) ? $response['true_response'] : null ;
                $oldOrder->response = $resp;
                $oldOrder->save();
                $msg = $resp . " ZOEDATAPORTAL Ref: " . $resp;
                //$subcategory =  Subcategory::find($oldOrder->subcategory_id);

            }elseif ($response['status'] == "FAILED" || $response['status'] == 'Failed') {
                
                $response_msg = isset($response['true_response']) ? $response['true_response'] : null ;
                    
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
