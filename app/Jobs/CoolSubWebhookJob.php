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


class CoolSubWebhookJob extends SpatieProcessWebhookJob
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
        \Log::info("COOLSUB PORTAL WEBHOOK RESPONSE");
        \Log::info($response);

        $ref = $response['your_ref'];

        $oldOrder = Order::where('ref', $ref)->whereIn('status', [0, 1])->first();
        
        if (!is_null($oldOrder)) {
            $subcategory = Subcategory::findOrFail($oldOrder->subcategory_id);
            
            if ($response['Status'] == "success" || $response['Status'] == "successful") {

                // $updateOrder = Order::where('ref', $ref)->first();
                $oldOrder->status = 1;
                $resp = isset($response['api_response']) ? $response['api_response'] : null ;
                $oldOrder->response = $resp;
                $oldOrder->save();
                $msg = $resp . " COOLSUBPORTAL Ref: " . $resp;
                //$subcategory =  Subcategory::find($oldOrder->subcategory_id);

            }elseif ($response['Status'] == "failed" || $response['Status'] == 'fail') {
                
                $response_msg = isset($response['api_response']) ? $response['api_response'] : null ;
                    
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
