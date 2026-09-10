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


class VtuPlugWebhookJob extends SpatieProcessWebhookJob
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
        \Log::info("VTUPLUG PORTAL WEBHOOK RESPONSE");
        \Log::info($response);

        $ref = $response['request-id'];

        $oldOrder = Order::where('ref', $ref)->where('status', 0)->first();
        
        if (!is_null($oldOrder)) {
            $subcategory = Subcategory::findOrFail($oldOrder->subcategory_id);
            
            if ($response['status'] == "success" || $response['status'] == "successful") {

                // $updateOrder = Order::where('ref', $ref)->first();
                $oldOrder->status = 1;
                $resp = $response['message'];
                $oldOrder->response = $resp;
                $oldOrder->save();
                $msg = $resp . " VTUPLUGPORTAL Ref: " . $resp;
                //$subcategory =  Subcategory::find($oldOrder->subcategory_id);

            }elseif ($response['status'] == "failed" || $response['status'] == 'fail') {
                
                $response_msg = $response['message'];
                    
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
