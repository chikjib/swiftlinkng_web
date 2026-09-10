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


class NaijaSubWebhookJob extends SpatieProcessWebhookJob
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
        \Log::info("NAIJASUB PORTAL WEBHOOK RESPONSE");
        \Log::info($response);

        $ref = $response['your_ref'];

        $oldOrder = Order::where('ref', $ref)->whereIn('status', [0, 1])->first();
        
        if (!is_null($oldOrder)) {
            $subcategory = Subcategory::findOrFail($oldOrder->subcategory_id);
            
            if ($response['Status'] == "success" || $response['Status'] == "successful") {

                // $updateOrder = Order::where('ref', $ref)->first();
                $oldOrder->status = 1;
                $resp = preg_replace(
                    '/Transaction Sent For Processing \.\s*(.*?)\s*Beneficiary :/',
                    'Transaction Sent For $1 is Successful' . "\n" . 'Beneficiary :',
                    $response['api_response']
                );
                $oldOrder->response = $resp;
                $oldOrder->save();
                $msg = $resp . " NAIJASUBPORTAL Ref: " . $resp;
                //$subcategory =  Subcategory::find($oldOrder->subcategory_id);

            }elseif ($response['Status'] == "failed" || $response['Status'] == 'fail') {
                
                $response_msg = preg_replace(
                    '/Transaction Sent For Processing \.\s*(.*?)\s*Beneficiary :/',
                    'Transaction Sent For $1 Failed' . "\n" . 'Beneficiary :',
                    $response['api_response']
                );
                    
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
