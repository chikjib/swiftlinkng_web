<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Subcategory;
use App\Services\FundingRecoveryService;
use App\Traits\WalletTrait;
use App\Traits\ReferenceTrait;
use App\Traits\IntegrationsTrait;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob as SpatieProcessWebhookJob;


class SquadCoProcessWebhookJob extends SpatieProcessWebhookJob
{
    use IntegrationsTrait;
    use WalletTrait;
    use ReferenceTrait;

    public function handle()
    {
        // $this->webhookCall // contains an instance of `WebhookCall`

        // perform the work here

        $payload = $this->webhookCall->payload;

        $response = $payload;


        if (!is_null($response)) {

            $check = Order::where('gtbank_reference', $response['transaction_reference'])->first();

            if (!is_null($check)) {
                $data = array(
                    "response_code" => 400,
                    "transaction_reference" => $response['transaction_reference'],
                    "response_description" => "Validation failure",
                );
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($data);
                exit;
            } else {

             $findUser = User::where('email', $response['customer_identifier'])->first();

                if (!is_null($findUser)) {
                
                    // Blocked email list
                    $blockedEmails = [
                                'salihusanusi853@gmail.com',
                                'polmimusa9@gmail.com',
                                'adamshauwa744@gmail.com',
                                'davetech261@gmail.com',
                                'preciousadeola42@gmail.com',
                                'mojeedbolaji340@gmail.com',
                                'musamuhammad9215@gmail.com',
                                'rayyanumuazu5@gmail.com',
                                'jonathanazubike90@gmail.com', 
                    ];
                
                    $grossAmount = (float) $response['principal_amount'];
                    $amount = $grossAmount;
                
                    if ($amount >= 10000) {
                        $amount = $amount - (0.005 * $amount); 
                    } else {
                        $amount = $amount - (0.002 * $amount); 
                    }
                
                    $amt = $amount;
                    $charge = round(max(0, $grossAmount - $amt), 2);
                
                    // Always store transaction but conditionally credit
                    $subcategory = Subcategory::where('title', 'GTbank')->first();
                
                    $ref = $response['transaction_reference'];
                    $order = new Order();
                    $order->ref = $ref;
                    $order->gtbank_reference = $response['transaction_reference'];
                    $order->user_id = $findUser->id;
                    $order->subcategory_id = $subcategory->id;
                    $order->plan = $subcategory->title;
                    $order->amount = $amt;
                    $order->quantity = 1;
                    $order->prev_bal = $findUser->wallet;
                    $order->subtotal = $response['settled_amount'];
                    $order->total = $response['settled_amount'];
                
                    // Check if blocked (case-insensitive) and already credited
                    if (!in_array(strtolower($findUser->email), array_map('strtolower', $blockedEmails)) && $this->isCredited($amt, $findUser->id)) {
                        // Credit wallet
                        $order->bal = $findUser->wallet + $amt;
                        $order->description = $subcategory->title . " RA: Auto Credited";
                        $order->status = 1;
                    } else {
                        // Block crediting
                        $order->bal = $findUser->wallet;
                        $order->description = $subcategory->title . " RA: Auto Credited";
                        $order->status = 0;
                    }
                
                    $order->save();
                    if ((int) $order->status === 1) {
                        app(FundingRecoveryService::class)->apply(
                            $order,
                            'GTBank',
                            $grossAmount,
                            $charge
                        );
                    }
                
                    $data = [
                        "response_code" => 200,
                        "transaction_reference" => $response['transaction_reference'],
                        "response_description" => "Success",
                    ];
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode($data);
                    exit;
                
                } else {
                    $data = [
                        "response_code" => 500,
                        "transaction_reference" => $response['transaction_reference'],
                        "response_description" => "System malfunction",
                    ];
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode($data);
                    exit;
                }

            }
        } else {
            $data = array(
                "response_code" => 500,
                "transaction_reference" => $response['transaction_reference'],
                "response_description" => "System malfunction",
            );
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($data);
            exit;
        }
    }
}
