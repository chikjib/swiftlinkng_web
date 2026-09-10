<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use App\Models\Subcategory;
use App\Traits\TelegramTrait;
use App\Traits\ReferenceTrait;
use App\Traits\IntegrationsTrait;
use App\Traits\WalletTrait;
use Exception;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\HttpClientException;

class ReprocessController extends BaseController
{
    //
    use ReferenceTrait;
    use WalletTrait;
    use IntegrationsTrait;
    use TelegramTrait;

    protected function formatPhoneNumber($phone)
    {
        // Remove spaces
        $phone = str_replace(' ', '', $phone);

        // Replace starting 234 or +234 with 0
        if (strpos($phone, '234') === 0) {
            $phone = '0' . substr($phone, 3);
        } elseif (strpos($phone, '+234') === 0) {
            $phone = '0' . substr($phone, 4);
        }

        // Return the formatted phone number
        return $phone;
    }

    public function reprocessAirtime()
    {

    }

    public function reprocessData(Request $request, $order_id)
    {
        $order = Order::find($order_id);
        $subcategory = Subcategory::find($order->subcategory_id);
        $plan = $order->plan_id;

        $productCollection = collect(json_decode($subcategory->products));
        $getSelectedProduct = $productCollection->where('plan', $plan)->first();
        $network = explode(' ', $subcategory->title)[0];
        $network_id = $this->parseAutoPilotNetworkID(strtolower($network));
        $user = User::find($order->user_id);

        \Log::info("Reprocess Details for SMEPLUG");
        \Log::info($user->id);
        \Log::info($network);
        \Log::info($network_id);
        \Log::info($plan);
        \Log::info($subcategory->description);
        \Log::info($getSelectedProduct->smeplug_id);

        if($order->status != 0){
            return $this->sendError("Sorry, we can only attend to pending transaction", "Sorry, we can only attend to pending transaction");
        }

        if (!is_null($subcategory->description) && $subcategory->description == "SMEPLUG") {

            try {
                $response = $this->SMEPlugApi($network_id, $plan, $this->formatPhoneNumber($order->phone), $order->ref);
            } catch (TransferException | ConnectException | ServerException | ClientException | RequestException | HttpClientException | ConnectException $e) {
                $response = null;
            }
            \Log::info("Reprocess Response for SMEPLUG");
            \Log::info(print_r($response,true));
            
            if(isset($response->status) && $response->status == true && isset($response->data->current_status) && $response->data->current_status == "success") {



                        if ($this->user->bonusIsAwardable()) {

                            $subcategory = Subcategory::find(23);
                            $productCollection = json_decode($subcategory->products);
                            $bonus = $this->getUserLevel($productCollection, $user->userlevel);
                            $amountadwarded = $this->adwardBonus($user->referrer()->id, $bonus, $order->subtotal);


                            $getbal = User::find($user->referrer()->id);
                            $order = new Order();
                            $order->ref = $this->referenceCode();
                            $order->user_id = $user->referrer()->id;
                            $order->subcategory_id = 23;
                            $order->plan = $subcategory->title . " " . $bonus . "%";
                            $order->amount = $amountadwarded;
                            $order->quantity = 1;
                            $order->subtotal = $amountadwarded;
                            $order->total = $amountadwarded;
                            $order->bal = $getbal->wallet;
                            $order->channel = is_null($order->channel) ? 'Web' : 'App';
                            $order->prev_bal = $getbal->wallet - $bonus;
                            $order->description = $bonus . "% " . $subcategory->title . "   for Referring " . $user->lastname;
                            $order->status = 1;
                            $order->save();
                        }

                            $updateOrder = Order::where('ref', $order->ref)->first();

                            $updateOrder->status = 1;
                            $updateOrder->response = $response->data->msg;
                            $updateOrder->save();
                            $msg = $response->data->msg . " SMEPLUG Ref: " . $response->data->reference;



                          $this->sendTelegramMessage($order->subtotal, $order->phone, $subcategory->pins, $getSelectedProduct->smeplug_id, $order->ref, $subcategory->telegram, $msg);


                        return $this->sendResponse2($order->ref, $order->custom_reference, $order->subtotal, $order->plan . " Purchase successful", $response->data->msg);
            }
            elseif(!isset($response->status) || is_null($response->status) || empty($response->status)){
                if(isset($response->errors[0])){
                    $updateOrder = Order::where('ref', $order->ref)->first();

                    $updateOrder->response = $response->errors[0];
                    $updateOrder->save();
                    
                    return $this->sendError($response->errors[0], $response->errors[0]);
                    
                }elseif(isset($response->msg)){
                    $updateOrder = Order::where('ref', $order->ref)->first();

                    $updateOrder->response = $response->msg;
                    $updateOrder->save();
                    
                    return $this->sendError($response->msg, $response->msg);
                    
                }else{
                    $updateOrder = Order::where('ref', $order->ref)->first();

                    $updateOrder->response = $response->data->message;
                    $updateOrder->save();
                    return $this->sendError($response->data->message, $response->data->message);
                }
                
                
            }
        }

    }

}
