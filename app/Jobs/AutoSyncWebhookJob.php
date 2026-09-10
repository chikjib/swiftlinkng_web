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


class AutoSyncWebhookJob extends SpatieProcessWebhookJob
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
        \Log::info("AUTOSYNC PORTAL WEBHOOK RESPONSE");
        \Log::info($response);
        //if($response['transaction']['status'])

        $ref = $response['transaction']['request_ref'];

        $oldOrder = Order::where('ref', $ref)->where('status', 0)->first();
        

        if (!is_null($oldOrder)) {
            $subcategory = Subcategory::findOrFail($oldOrder->subcategory_id);
            
            if ($response['transaction']['status'] == "successful") {

                // $updateOrder = Order::where('ref', $ref)->first();
                $oldOrder->status = 1;
                $resp = $response['transaction']['details'];
                $oldOrder->response = $resp;
                $oldOrder->save();
                //$msg = $resp . " AUTOSYNC Ref: " . $resp;

            }elseif ($response['transaction']['status'] == "failed") {
                
                $response_msg = $response['transaction']['details'];
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $oldOrder->subtotal,
                        [
                            'bal' => $oldOrder->prev_bal,
                            'prev_bal' => $oldOrder->bal
                        ]
                    );
                    
                
               
                // return $this->sendError3($ref, $oldOrder->custom_reference, $resp_msg, "Transaction failed: ".$msg, "Transaction failed: ".$msg);
            }
        }

    }
}
