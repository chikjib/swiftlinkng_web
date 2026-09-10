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


class TbchPortalWebhookJob extends SpatieProcessWebhookJob
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
        \Log::info("TBCHPORTAL WEBHOOK RESPONSE");
        \Log::info($response);
        //if($response['transaction']['status'])

        $ref = $response['transaction']['customer_ref'];

        $oldOrder = Order::where('ref', $ref)->where('status', 0)->first();
        

        if (!is_null($oldOrder)) {
            $subcategory = Subcategory::findOrFail($oldOrder->subcategory_id);
            
            if ($response['transaction']['status'] == "Successful") {

                // $updateOrder = Order::where('ref', $ref)->first();
                $oldOrder->status = 1;
                $resp = $response['transaction']['beneficiaries'][0]['gateway_response'];
                $oldOrder->response = $resp;
                $oldOrder->save();
                $msg = $resp . " TBCHPORTAL Ref: " . $resp;
                //$subcategory =  Subcategory::find($oldOrder->subcategory_id);

               // $this->sendTelegramMessage($oldOrder->amount, $oldOrder->phone, $subcategory->pins, "", $ref, $subcategory->telegram, $msg);
            }elseif ($response['transaction']['beneficiaries'][0]['status'] == "Failed") {
                $response_msg = $response['transaction']['beneficiaries'][0]['gateway_response'];
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $oldOrder->subtotal,
                        [
                            'bal' => $oldOrder->prev_bal,
                            'prev_bal' => $oldOrder->bal
                        ]
                    );

                

                // $msg = $response['data']['message'];
            //    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->autopilot_code, $ref, $subcategory->telegram, $msg);

            //    $resp_msg = "Data is not available,please try again later";



                // return $this->sendError3($ref, $oldOrder->custom_reference, $resp_msg, "Transaction failed: ".$msg, "Transaction failed: ".$msg);
            }
        }

    }
}
