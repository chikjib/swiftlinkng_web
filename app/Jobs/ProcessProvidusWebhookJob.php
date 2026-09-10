<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Subcategory;
use App\Services\FundingRecoveryService;
use App\Traits\WalletTrait;
use App\Traits\TelegramTrait;
use App\Traits\ReferenceTrait;
use App\Traits\IntegrationsTrait;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob as SpatieProcessWebhookJob;


class ProcessProvidusWebhookJob extends SpatieProcessWebhookJob
{
    use IntegrationsTrait;
    use WalletTrait;
    use ReferenceTrait;
    use TelegramTrait;

    
    
    public function handle()
    {
    $payload = $this->webhookCall->payload;
    $response = $payload;

    $check_sporty_remarks = $response['tranRemarks'];

    if (str_contains($check_sporty_remarks, "sporty")) {
        exit;
    } elseif (str_contains($check_sporty_remarks, "football")) {
        exit;
    } elseif (str_contains($check_sporty_remarks, "puid")) {
        exit;
    } else {
        if (!is_null($response)) {

            $check = Order::where('settlement_id', $response['settlementId'])->first();

            if (!is_null($check)) {
                $data = array(
                    "requestSuccessful" => true,
                    "sessionId" => $response['sessionId'],
                    "responseMessage" => "duplicate transaction",
                    "responseCode" => "01",
                );
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($data);
                exit;
            } else {

                $findOrder = Order::where('settlement_id', $response['settlementId'])->where('status', 0)->first();

                if (is_null($findOrder)) {
                    $findUser = User::where('providus_account', $response['accountNumber'])->first();

                    if (!is_null($findUser)) {

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

                        $amount = $response['settledAmount'];
                        $com = 0.002 * $amount;
                        $amt = $amount - $com;

                        $subcategory = Subcategory::where('title', 'Providus')->first();
                        $ref = $response['settlementId'];

                        $order = new Order();
                        $order->ref = $ref;
                        $order->settlement_id = $response['settlementId'];
                        $order->user_id = $findUser->id;
                        $order->subcategory_id = $subcategory->id;
                        $order->plan = $subcategory->title;
                        $order->amount = $amt;
                        $order->quantity = 1;
                        $order->prev_bal = $findUser->wallet;
                        $order->subtotal = $response['settledAmount'];
                        $order->total = $response['settledAmount'];

                        // Check if user email is blocked
                        if (in_array(strtolower($findUser->email), array_map('strtolower', $blockedEmails))) {
                            // Don't credit wallet
                            $order->bal = $findUser->wallet; // wallet unchanged
                            $order->description = $subcategory->title . " RA: Auto Credited";
                            $order->status = 2; // blocked status code, adjust if needed
                        } else {
                            // Credit wallet normally
                            if ($this->isCredited($amt, $findUser->id)) {
                                $order->bal = $findUser->wallet + $amt;
                                $order->description = $subcategory->title . " RA: Auto Credited";
                                $order->response = sprintf(
                                    'Providus deposit of N%s confirmed. Charge: N%s. Wallet credited: N%s.',
                                    number_format((float) $amount, 2, '.', ''),
                                    number_format((float) $com, 2, '.', ''),
                                    number_format((float) $amt, 2, '.', '')
                                );
                                $order->status = 1;
                            } else {
                                // In case isCredited fails, you may want to handle it here
                                $order->bal = $findUser->wallet;
                                $order->description = $subcategory->title . "RA: Auto Credited";
                                $order->status = 3; // custom status for failed credit, adjust as needed
                            }
                        }

                        $order->save();
                        if ((int) $order->status === 1) {
                            app(FundingRecoveryService::class)->apply(
                                $order,
                                'Providus',
                                (float) $amount,
                                (float) $com
                            );
                        }

                    } else {
                        $data = array(
                            "requestSuccessful" => true,
                            "sessionId" => $response['sessionId'],
                            "responseMessage" => "success",
                            "responseCode" => "00",
                        );

                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode($data);
                        exit;
                    }
                } else {
                    $data = array(
                        "requestSuccessful" => true,
                        "sessionId" => $response['sessionId'],
                        "responseMessage" => "duplicate transaction",
                        "responseCode" => "01",
                    );
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode($data);
                    exit;
                }
            }
        } else {
            $data = array(
                "requestSuccessful" => true,
                "sessionId" => $response['sessionId'],
                "responseMessage" => "rejected transaction",
                "responseCode" => "02",
            );
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($data);
            exit;
        }
    }
}

}
