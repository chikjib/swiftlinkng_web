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
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob as SpatieProcessWebhookJob;


class ProcessWebhookJob extends SpatieProcessWebhookJob
{
    use IntegrationsTrait;
    use WalletTrait;
    use ReferenceTrait;
    use TelegramTrait;

     public function handle()
    {
        // $this->webhookCall // contains an instance of `WebhookCall`

        // perform the work here

        $payload = $this->webhookCall->payload;

        $response = $payload['eventData'];
        //$response = $payload;
        
        $transactionReference = $response['transactionReference'];
        
        $new_response = $this->VerifyMonnify($transactionReference);
        
        \Log::info("Monnify Verification Response");
        \Log::info($new_response);
        
        $response = $new_response['responseBody'];
        
        
        if($new_response['requestSuccessful'] == true && $new_response['responseCode'] == '0' && $response['paymentStatus'] == 'PAID') {
            

            switch ($response['product']['type']) {
                case "RESERVED_ACCOUNT":
                    $findUser = User::where('email', $response['customer']['email'])->first();

                    if (!is_null($findUser)) {
                    
                        $findOrder = Order::where('ref', $response['transactionReference'])->first();
                    
                        if (is_null($findOrder)) {
                    
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
                    
                            $amt = (float) $response['settlementAmount'];
                            $grossAmount = (float) ($response['amountPaid'] ?? $response['amount'] ?? $amt);
                            $charge = max(0, round($grossAmount - $amt, 2));
                    
                            $subcategory = Subcategory::where('title', 'Monnify')->first();
                    
                            $ref = $response['transactionReference'];
                    
                            $order = new Order();
                            $order->ref = $ref;
                            $order->monnify_reference = $ref;
                            $order->user_id = $findUser->id;
                            $order->subcategory_id = $subcategory->id;
                            $order->plan = $subcategory->title;
                            $order->amount = $amt;
                            $order->quantity = 1;
                            $order->prev_bal = $findUser->wallet;
                            $order->subtotal = $amt;
                            $order->total = $amt;
                    
                            if (in_array(strtolower($findUser->email), array_map('strtolower', $blockedEmails))) {
                                // Block crediting wallet
                                $order->bal = $findUser->wallet; // wallet unchanged
                                $order->description = $subcategory->title . " RA: Auto Credited";
                                $order->status = 2; // blocked status code
                            } else {
                                // Normal crediting
                                if ($this->isCredited($amt, $findUser->id)) {
                                    $order->bal = $findUser->wallet + $amt;
                                    $order->description = $subcategory->title . " RA: Auto Credited";
                                    $order->response = sprintf(
                                        'Monnify deposit of N%s confirmed. Charge: N%s. Wallet credited: N%s.',
                                        number_format($grossAmount, 2, '.', ''),
                                        number_format($charge, 2, '.', ''),
                                        number_format($amt, 2, '.', '')
                                    );
                                    $order->status = 1;
                                } else {
                                    // Optional: handle credit failure here
                                    $order->bal = $findUser->wallet;
                                    $order->description = $subcategory->title . " RA: Auto Credited";
                                    $order->status = 3; // credit failed
                                }
                            }
                    
                            $order->save();
                            if ((int) $order->status === 1) {
                                app(FundingRecoveryService::class)->apply(
                                    $order,
                                    'Monnify',
                                    $grossAmount,
                                    $charge
                                );
                            }
                        }
                    }

                    break;
                case "WEB_SDK":

                    $findOrder = Order::where('ref', $response['transactionReference'])->where('status', 0)->first();

                    if (Order::where('ref', $response['transactionReference'])->exists() == false) {
                        if(!is_null($findOrder)){
                            $subcategory =  Subcategory::find($findOrder->subcategory_id);
                        }
                        

                        if (isset($subcategory) && $subcategory->category_id == 2) {


                            if (!is_null($subcategory->description) &&  $subcategory->description == "SMEPLUG") {

                                $network_id = $this->translateSMEPlugAirtimeNetwork($subcategory->title);

                                $response =  $this->SMEPlugAirtimeApi($network_id, intVal($findOrder->amount), $findOrder->phone);
                                if (is_null($response) || ($response->status == false)) {
                                } else {

                                    $findOrder->status = 1;
                                    $findOrder->response = $response->data->msg;
                                    $findOrder->save();
                                    $msg =  $response->data->msg . " SMEPLUG Ref: " . $response->data->reference;

                                    $this->sendTelegramMessage2($findOrder->user, intVal(intVal($findOrder->amount)), $findOrder->phone, $subcategory->pins, $subcategory->pins, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            }

                            $findOrder->status = 1;
                            $findOrder->save();
                            // $channelText = $subcategory->pins;
                            // $channelText = str_replace(['AMOUNT', 'PHONE', 'REF'], [$amount, $phone, $ref], $channelText);

                            $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $subcategory->pins, $findOrder->ref, $subcategory->telegram);
                        } else if (isset($subcategory) && $subcategory->category_id == 1) {
                            //$subcategory =  Subcategory::find($findOrder->subcategory_id);
                            $productCollection = collect(json_decode($subcategory->products));

                            // $plan = $productCollection->first()->plan;
                            $plan =  $findOrder->plan;
                            $getSelectedProduct = $productCollection->where('plan', $plan)->first();
                            $amountActual =  $this->getUserLevel($getSelectedProduct, User::find($findOrder->user_id)->userlevel);
                            $network = explode(' ', $subcategory->title)[0];


                            if (!is_null($subcategory->description) && $subcategory->description == "SMEPLUG") {

                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                $plan_id = $getSelectedProduct->smeplug_id;
                                
                                $response =  $this->SMEPlugApi($network_id, $plan_id, $findOrder->phone);
                                if (is_null($response) || ($response->status == false)) {
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);


                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = User::find($findOrder->user_id)->wallet + $findOrder->amount;
                                    $order->prev_bal = User::find($findOrder->user_id)->wallet;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $findOrder->amount;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                } else {

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }
                                    $findOrder->status = 1;
                                    $findOrder->response = $response->data->msg;
                                    $findOrder->save();
                                    $msg =  $response->data->msg . " SMEPLUG Ref: " . $response->data->reference;


                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "SIMSERVER") {

                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                $plan_id = $getSelectedProduct->ss_product_code;


                                $response = $this->SIMServerBuy($plan_id, 1, $findOrder->phone, $findOrder->ref);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                // return $this->sendError($response, $response);

                                if (is_null($response) || ($response['status'] == false)) {

                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);



                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = User::find($findOrder->user_id)->wallet + $findOrder->amount;
                                    $order->prev_bal = User::find($findOrder->user_id)->wallet;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $findOrder->amount;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                } else {

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }
                                    $findOrder->status = 1;
                                    $findOrder->response = $response['data']['true_response'];
                                    $findOrder->save();
                                    $msg =  $response['data']['true_response'] . " SIMSERVER Ref: " . $response['data']['true_response'];


                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "EGMS") {

                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                $plan_id = $getSelectedProduct->egms_plan_id;

                                $response = $this->EGMSPurchase($plan_id, 1, $findOrder->phone, $findOrder->ref);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if (is_null($response) || !isset($response['status']) || ($response['status'] != "ok")) {
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);



                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = User::find($findOrder->user_id)->wallet + $findOrder->amount;
                                    $order->prev_bal = User::find($findOrder->user_id)->wallet;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                } else {

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }
                                    $findOrder->status = 1;
                                    $findOrder->response = $response['message'];
                                    $findOrder->save();
                                    $msg =  $response['message'] . " EGMS Ref: " . $response['message'];


                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "AIRTIMENIGERIA") {

                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                $plan_id = $getSelectedProduct->aa_package_code;

                                $response = $this->purchaseAirtimeNigeria($plan_id, 1, $findOrder->phone, $findOrder->ref);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if (is_null($response) || ($response['details']['order_status'] == "failed")) {
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);



                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = User::find($findOrder->user_id)->wallet + $findOrder->amount;
                                    $order->prev_bal = User::find($findOrder->user_id)->wallet;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                } else {

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }
                                    $findOrder->status = 1;
                                    $findOrder->response = $response['details']['gateway_response'];
                                    $findOrder->save();
                                    $msg = $response['details']['gateway_response'] . " AIRTIMENIGERIA Ref: " . $response['details']['reference'];
                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "DATABAY") {

                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                $plan_id = $getSelectedProduct->databaycode;

                                $response = $this->dataBayBuyData($plan_id,  $findOrder->phone);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if (is_null($response) || ($response['success'] == false)) {
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);



                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = User::find($findOrder->user_id)->wallet + $findOrder->amount;
                                    $order->prev_bal = User::find($findOrder->user_id)->wallet;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                } else {

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }
                                    $findOrder->status = 1;
                                    $findOrder->response = $response['payload']['networkResponseMessage'];
                                    $findOrder->save();
                                    $msg = $response['payload']['networkResponseMessage'] . " DATABAY Ref: " . $response['payload']['reference'];
                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "OGADAM") {

                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                $plan_id = $getSelectedProduct->ogadam_id;

                                $response = $this->ogaDamBuyData($network_id, $plan_id, $findOrder->phone, $findOrder->ref);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if (is_null($response) || ($response['code'] == 424)) {
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);



                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = User::find($findOrder->user_id)->wallet + $findOrder->amount;
                                    $order->prev_bal = User::find($findOrder->user_id)->wallet;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                } else  if (is_null($response) || ($response['code'] != 200)) {
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 0;
                                    $updOrder->save();
                                    $msg = $response['data']['msg'] . " OGADAM Ref: " . $response['data']['msg'];

                                    $this->sendTelegramMessage($findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                } else {

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }

                                    $updateOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updateOrder->status = 1;
                                    $updateOrder->response = $response['data']['msg'];
                                    $updateOrder->save();
                                    $msg = $response['data']['msg'] . " OGADAM Ref: " . $response['data']['msg'];

                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "AIRTELEDUSITE") {

                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                 $plan_id = $getSelectedProduct->airtel_id;
                                 $request_body = $getSelectedProduct->airtel_plan;

                                $response = $this->AirtelEduBuyData($findOrder->phone, $request_body);
                                \Log::info("Airtel Edu Site from Bank Web sdk");
                                 \Log::info($response);
                                 if(isset($response)){
                                        $dstatus = $response['data'][0]['status'];
                                        $desc = $response['data'][0]['description'];
                                    }
                                 if (!isset($dstatus) || is_null($dstatus) || $dstatus == "error") {
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);



                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = User::find($findOrder->user_id)->wallet + $findOrder->amount;
                                    $order->prev_bal = User::find($findOrder->user_id)->wallet;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                } else {

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }

                                    $updateOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updateOrder->status = 1;
                                    $updateOrder->response = $desc;
                                    $updateOrder->save();
                                    $msg = $desc . " AIRTEL EDUSUITE Ref: " . $desc;

                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "GONGOZ") {

                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                $plan_id = $getSelectedProduct->gongzo_id;

                                $response = $this->gongozconceptData($network_id, $plan_id, $findOrder->phone);
                                \Log::info("From Bank Gongoz");
                                \Log::info($response);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if (is_null($response) || ($response['Status'] != 'successful')) {
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);



                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = User::find($findOrder->user_id)->wallet + $findOrder->amount;
                                    $order->prev_bal = User::find($findOrder->user_id)->wallet;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                }else {

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }

                                    $updateOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updateOrder->status = 1;
                                    $updateOrder->response = $response['apiresponse'];
                                    $updateOrder->save();
                                    $msg = $response['apiresponse'] . " GONGOZ Ref: " . $response['ident'];

                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "AYINLAK") {
            
                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                $plan_id = $getSelectedProduct->ayinlak_id;

                                $response = $this->ayinlakconnectData($network_id, $plan_id, $findOrder->phone);
                                \Log::info("From Bank Ayinlak");
                                \Log::info($response);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if (is_null($response) || ($response['Status'] != 'successful')) {
                                    \Log::info("it failed Ayinlak");
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);



                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = User::find($findOrder->user_id)->wallet + $findOrder->amount;
                                    $order->prev_bal = User::find($findOrder->user_id)->wallet;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                } else {
                                    \Log::info("it was successful Ayinlak");

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }

                                    $updateOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updateOrder->status = 1;
                                    $updateOrder->response = $response['api_response'];
                                    $updateOrder->save();
                                    $msg = $response['api_response'] . " AYINLAK Ref: " . $response['ident'];

                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "JONET") {
            
                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                $plan_id = $getSelectedProduct->jonet_code;

                                $response = $this->jonetData($plan_id, $findOrder->phone,$findOrder->ref);
                                \Log::info("From Bank JONET");
                                \Log::info($response);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if ($response['status'] == 'Failed' || $response['responseCode'] == "002" || $response['responseCode'] == "101" || $response['responseCode'] == "102" || $response['responseCode'] == "103" || $response['responseCode'] == "104" || $response['responseCode'] == "105" || $response['responseCode'] == "108" || $response['responseCode'] == "109" || $response['responseCode'] == "106" || $response['responseCode'] == "107" || $response['responseCode'] == "JO101" || $response['responseCode'] == "JO102" || $response['responseCode'] == "JO103" || $response['responseCode'] == "JO104" || $response['responseCode'] == "JO106"|| $response['responseCode'] == "JO107" || $response['responseCode'] == "JO109" || $response['responseCode'] == "JO110" || $response['responseCode'] == "JO119") {
                                    \Log::info("it failed JONET");
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    $prev_bal = User::find($findOrder->user_id)->wallet;

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);
                                    
                                    $new_bal = User::find($findOrder->user_id)->wallet;
                                    


                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = $new_bal;
                                    $order->prev_bal = $prev_bal;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                } elseif($response['responseCode'] == "200") {
                                    \Log::info("it was successful JONET");

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }

                                    $updateOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updateOrder->status = 1;
                                    $updateOrder->response = $response['server_response'];
                                    $updateOrder->save();
                                    $msg = $response['server_response'] . " Jonet Ref: " . $response['customer_id'];

                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "AUTOPILOT") {
            
                                $network_id = $this->parseAutoPilotNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                $plan_id = $getSelectedProduct->autopilot_code;
                                $datatype = $getSelectedProduct->autopilot_datatype;

                                $response = $this->autoPilotData($network_id,$datatype,$plan_id, $findOrder->phone, $findOrder->ref);
                                \Log::info("From Bank AUTOPILOT");
                                \Log::info($response);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if ($response['status'] == false || $response['code'] == "424" || $response['code'] == 500) {
                                    \Log::info("it failed AUTOPILOT");
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    $prev_bal = User::find($findOrder->user_id)->wallet;

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);
                                    
                                    $new_bal = User::find($findOrder->user_id)->wallet;
                                    


                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = $new_bal;
                                    $order->prev_bal = $prev_bal;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->autopilot_code, $findOrder->ref, $subcategory->telegram);
                                } elseif($response['status'] == true) {
                                    \Log::info("it was successful AUTOPILOT From bank");

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }

                                    $updateOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updateOrder->status = 1;
                                    $updateOrder->response = $response['data']['message'];
                                    $updateOrder->save();
                                    $msg = $response['data']['message'] . " AutoPilot Ref: " . $response['data']['reference'];

                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->autopilot_code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "AUTOSYNCAWUF") {
            
                                $network_id = $this->parseAutoPilotNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                
                                $product_id = $getSelectedProduct->product_id;
                                $variation_code = $getSelectedProduct->variation_code;
                                $pin = $getSelectedProduct->pin;

                                $response = $this->autoSyncPortal($findOrder->ref,$findOrder->phone,$product_id,$variation_code,$pin);
                                \Log::info("From Bank AUTOSYNC RESPONSE");
                                \Log::info($response);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if (isset($response['data']) && isset($response['data']['transaction']) && $response['data']['transaction']['status'] == "pending"){
                                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
                                } elseif(isset($response['status']) && $response['status'] == "error"){
                                
                                    \Log::info("it failed AUTOPILOT");
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    $prev_bal = User::find($findOrder->user_id)->wallet;

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);
                                    
                                    $new_bal = User::find($findOrder->user_id)->wallet;
                                    


                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = $new_bal;
                                    $order->prev_bal = $prev_bal;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->autopilot_code, $findOrder->ref, $subcategory->telegram);
                                } elseif (isset($response['data']) && isset($response['data']['transaction']) && $response['data']['transaction']['status'] == "failed"){
                                
                                    \Log::info("it failed AUTOPILOT");
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    $prev_bal = User::find($findOrder->user_id)->wallet;

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);
                                    
                                    $new_bal = User::find($findOrder->user_id)->wallet;
                                    


                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = $new_bal;
                                    $order->prev_bal = $prev_bal;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->autopilot_code, $findOrder->ref, $subcategory->telegram);
                                    
                                } elseif(isset($response['data']) && isset($response['data']['transaction']) && $response['data']['transaction']['status'] == "successful") {
                                    \Log::info("it was successful AUTOPILOT From bank");

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }

                                    $updateOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updateOrder->status = 1;
                                    $updateOrder->response = $response['data']['message'];
                                    $updateOrder->save();
                                    $msg = $response['data']['message'] . " AutoPilot Ref: " . $response['data']['reference'];

                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->autopilot_code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "TBCHPORTAL") {
            
                                $network_id = $this->parseTBCHPortalID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                
                                $plan_id = $getSelectedProduct->tbch_code;

                                $response = $this->tbchPortal($network_id,$plan_id, $findOrder->phone, $findOrder->ref);
                                \Log::info("From Bank TBCHPORTAL");
                                \Log::info($response);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if ($response['code'] == 216) {
                                    \Log::info("it failed TBCHPORTAL");
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    $prev_bal = User::find($findOrder->user_id)->wallet;

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);
                                    
                                    $new_bal = User::find($findOrder->user_id)->wallet;
                                    


                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = $new_bal;
                                    $order->prev_bal = $prev_bal;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->response = $response['data']['gateway_response'];
                                    $order->status = 2;
                                    $order->save();
                                    $msg = $response['data']['gateway_response'];

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->autopilot_code, $findOrder->ref, $subcategory->telegram);
                                } elseif($response['code'] == "00" && $response['data']['status'] == "Successful") {
                                    \Log::info("it was successful AUTOPILOT From bank");

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }

                                    $updateOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updateOrder->status = 1;
                                    $updateOrder->response = $response['data']['gateway_response'];
                                    $updateOrder->save();
                                    $msg = $response['data']['gateway_response'] . " TBCHPORTAL Ref: " . $response['data']['customer_ref'];

                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->autopilot_code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else {

                                //adward bonus;
                                if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                    $subcategory =  Subcategory::find(23);
                                    $productCollection = json_decode($subcategory->products);
                                    $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                    $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                    $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                    $order = new Order();
                                    $order->ref = $this->referenceCode();
                                    $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                    $order->subcategory_id =  23;
                                    $order->plan =  $subcategory->title . " " . $bonus . "%";
                                    $order->amount = $amountadwarded;
                                    $order->quantity =  1;
                                    $order->subtotal =  $amountadwarded;
                                    $order->total = $amountadwarded;
                                    $order->bal = $getbal->wallet;
                                    $order->channel = $findOrder->channel;
                                    $order->prev_bal = $getbal->wallet - $bonus;
                                    $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                    $order->status = 1;
                                    $order->save();
                                }
                            }

                            $updOrder = Order::where('ref', $findOrder->ref)->first();
                            $updOrder->status = 1;
                            $updOrder->save();

                            $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                        } else {
                            $findUser = User::where('email', $response['customer']['email'])->first();

                            \Log::info("monnify card webhook");
                            \Log::info($findUser);
                            
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
                            
                            $amt = $response['settlementAmount'];
                            
                            $subcategory = Subcategory::where('title', 'Monnify')->first();
                            
                            $ref = $response['transactionReference'];
                            
                            $order = new Order();
                            $order->ref = $ref;
                            $order->user_id = $findUser->id;
                            $order->subcategory_id = $subcategory->id;
                            $order->plan = $subcategory->title;
                            $order->amount = $amt;
                            $order->quantity = 1;
                            $order->prev_bal = $findUser->wallet;
                            $order->subtotal = $amt;
                            $order->total = $amt;
                            
                            if (in_array(strtolower($findUser->email), array_map('strtolower', $blockedEmails))) {
                                // Block crediting wallet
                                $order->bal = $findUser->wallet; // wallet unchanged
                                $order->description = $subcategory->title . " Card Auto Credited";
                                $order->status = 2; // blocked status
                                $order->save();
                            } else {
                                // Normal crediting
                                if ($this->isCredited($amt, $findUser->id)) {
                                    $order->bal = $findUser->wallet + $amt;
                                    $order->description = $subcategory->title . " Card Auto Credited";
                                    $order->status = 1;
                                    $order->save();
                                }
                            }

                        }
                    }

                    break;

                case "MOBILE_SDK":
                    $findOrder = Order::where('ref', $response['transactionReference'])->where('status', 0)->first();
                    if (Order::where('ref', $response['transactionReference'])->exists() == false) {
                        if(!is_null($findOrder)){
                            $subcategory =  Subcategory::find($findOrder->subcategory_id);
                        }
                        

                        if (isset($subcategory) && $subcategory->category_id == 2) {


                            if (!is_null($subcategory->description) &&  $subcategory->description == "SMEPLUG") {

                                $network_id = $this->translateSMEPlugAirtimeNetwork($subcategory->title);

                                $response =  $this->SMEPlugAirtimeApi($network_id, intVal($findOrder->amount), $findOrder->phone);
                                if (is_null($response) || ($response->status == false)) {
                                } else {

                                    $findOrder->status = 1;
                                    $findOrder->response = $response->data->msg;
                                    $findOrder->save();
                                    $msg =  $response->data->msg . " SMEPLUG Ref: " . $response->data->reference;

                                    $this->sendTelegramMessage2($findOrder->user, intVal(intVal($findOrder->amount)), $findOrder->phone, $subcategory->pins, $subcategory->pins, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            }

                            $findOrder->status = 1;
                            $findOrder->save();
                            // $channelText = $subcategory->pins;
                            // $channelText = str_replace(['AMOUNT', 'PHONE', 'REF'], [$amount, $phone, $ref], $channelText);

                            $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $subcategory->pins, $findOrder->ref, $subcategory->telegram);
                        } else if (isset($subcategory) && $subcategory->category_id == 1) {
                            //$subcategory =  Subcategory::find($findOrder->subcategory_id);
                            $productCollection = collect(json_decode($subcategory->products));

                            // $plan = $productCollection->first()->plan;
                            $plan =  $findOrder->plan;
                            $getSelectedProduct = $productCollection->where('plan', $plan)->first();
                            $amountActual =  $this->getUserLevel($getSelectedProduct, User::find($findOrder->user_id)->userlevel);
                            $network = explode(' ', $subcategory->title)[0];


                            if (!is_null($subcategory->description) && $subcategory->description == "SMEPLUG") {

                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                $plan_id = $getSelectedProduct->smeplug_id;
                                Log::debug($plan_id);

                                Log::debug($plan);

                                $response =  $this->SMEPlugApi($network_id, $plan_id, $findOrder->phone);
                                if (is_null($response) || ($response->status == false)) {
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);


                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = User::find($findOrder->user_id)->wallet + $findOrder->amount;
                                    $order->prev_bal = User::find($findOrder->user_id)->wallet;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $findOrder->amount;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                } else {

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }
                                    $findOrder->status = 1;
                                    $findOrder->response = $response->data->msg;
                                    $findOrder->save();
                                    $msg =  $response->data->msg . " SMEPLUG Ref: " . $response->data->reference;


                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "SIMSERVER") {

                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                $plan_id = $getSelectedProduct->ss_product_code;


                                $response = $this->SIMServerBuy($plan_id, 1, $findOrder->phone, $findOrder->ref);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                // return $this->sendError($response, $response);

                                if (is_null($response) || ($response['status'] == false)) {

                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);



                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = User::find($findOrder->user_id)->wallet + $findOrder->amount;
                                    $order->prev_bal = User::find($findOrder->user_id)->wallet;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $findOrder->amount;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                } else {

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }
                                    $findOrder->status = 1;
                                    $findOrder->response = $response['data']['true_response'];
                                    $findOrder->save();
                                    $msg =  $response['data']['true_response'] . " SIMSERVER Ref: " . $response['data']['true_response'];


                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "EGMS") {

                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                $plan_id = $getSelectedProduct->egms_plan_id;

                                $response = $this->EGMSPurchase($plan_id, 1, $findOrder->phone, $findOrder->ref);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if (is_null($response) || !isset($response['status']) || ($response['status'] != "ok")) {
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);



                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = User::find($findOrder->user_id)->wallet + $findOrder->amount;
                                    $order->prev_bal = User::find($findOrder->user_id)->wallet;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                } else {

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }
                                    $findOrder->status = 1;
                                    $findOrder->response = $response['message'];
                                    $findOrder->save();
                                    $msg =  $response['message'] . " EGMS Ref: " . $response['message'];


                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "AIRTIMENIGERIA") {

                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                $plan_id = $getSelectedProduct->aa_package_code;

                                $response = $this->purchaseAirtimeNigeria($plan_id, 1, $findOrder->phone, $findOrder->ref);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if (is_null($response) || ($response['details']['order_status'] == "failed")) {
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);



                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = User::find($findOrder->user_id)->wallet + $findOrder->amount;
                                    $order->prev_bal = User::find($findOrder->user_id)->wallet;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                } else {

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }
                                    $findOrder->status = 1;
                                    $findOrder->response = $response['details']['gateway_response'];
                                    $findOrder->save();
                                    $msg = $response['details']['gateway_response'] . " AIRTIMENIGERIA Ref: " . $response['details']['reference'];
                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "DATABAY") {

                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                $plan_id = $getSelectedProduct->databaycode;

                                $response = $this->dataBayBuyData($plan_id,  $findOrder->phone);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if (is_null($response) || ($response['success'] == false)) {
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);



                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = User::find($findOrder->user_id)->wallet + $findOrder->amount;
                                    $order->prev_bal = User::find($findOrder->user_id)->wallet;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                } else {

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }
                                    $findOrder->status = 1;
                                    $findOrder->response = $response['payload']['networkResponseMessage'];
                                    $findOrder->save();
                                    $msg = $response['payload']['networkResponseMessage'] . " DATABAY Ref: " . $response['payload']['reference'];
                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "OGADAM") {

                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                $plan_id = $getSelectedProduct->ogadam_id;

                                $response = $this->ogaDamBuyData($network_id, $plan_id, $findOrder->phone, $findOrder->ref);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if (is_null($response) || ($response['code'] == 424)) {
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);



                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = User::find($findOrder->user_id)->wallet + $findOrder->amount;
                                    $order->prev_bal = User::find($findOrder->user_id)->wallet;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                } else  if (is_null($response) || ($response['code'] != 200)) {
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 0;
                                    $updOrder->save();
                                    $msg = $response['data']['msg'] . " OGADAM Ref: " . $response['data']['msg'];

                                    $this->sendTelegramMessage($findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                } else {

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }

                                    $updateOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updateOrder->status = 1;
                                    $updateOrder->response = $response['data']['msg'];
                                    $updateOrder->save();
                                    $msg = $response['data']['msg'] . " OGADAM Ref: " . $response['data']['msg'];

                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "AIRTELEDUSITE") {

                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                 $plan_id = $getSelectedProduct->airtel_id;
                                 $request_body = $getSelectedProduct->airtel_plan;

                                $response = $this->AirtelEduBuyData($findOrder->phone, $request_body);
                                \Log::info("Airtel edu site from bank app sdk");
                                \Log::info($response);
                               
                                    if(isset($response)){
                                        $dstatus = $response['data'][0]['status'];
                                        $desc = $response['data'][0]['description'];
                                    }
                                    if (!isset($dstatus) || is_null($dstatus) || $dstatus == "error") {
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);



                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = User::find($findOrder->user_id)->wallet + $findOrder->amount;
                                    $order->prev_bal = User::find($findOrder->user_id)->wallet;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                } else {

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }

                                    $updateOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updateOrder->status = 1;
                                    $updateOrder->response = $desc;
                                    $updateOrder->save();
                                    $msg = $desc . " AIRTEL EDUSUITE Ref: " . $desc;

                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "GONGOZ") {

                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                $plan_id = $getSelectedProduct->gongzo_id;

                                $response = $this->gongozconceptData($network_id, $plan_id, $findOrder->phone);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if (is_null($response) || ($response['Status'] != 'successful')) {
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);



                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = User::find($findOrder->user_id)->wallet + $findOrder->amount;
                                    $order->prev_bal = User::find($findOrder->user_id)->wallet;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                } else {

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }

                                    $updateOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updateOrder->status = 1;
                                    $updateOrder->response = $response['apiresponse'];
                                    $updateOrder->save();
                                    $msg = $response['apiresponse'] . " GONGOZ Ref: " . $response['ident'];

                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "AYINLAK") {

                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                $plan_id = $getSelectedProduct->ayinlak_id;

                                $response = $this->ayinlakconnectData($network_id, $plan_id, $findOrder->phone);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if (is_null($response) || ($response['Status'] != 'successful')) {
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);



                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = User::find($findOrder->user_id)->wallet + $findOrder->amount;
                                    $order->prev_bal = User::find($findOrder->user_id)->wallet;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                }else {

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }

                                    $updateOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updateOrder->status = 1;
                                    $updateOrder->response = $response['api_response'];
                                    $updateOrder->save();
                                    $msg = $response['api_response'] . " AYINLAK Ref: " . $response['ident'];

                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "JONET") {
            
                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                $plan_id = $getSelectedProduct->jonet_code;

                                $response = $this->jonetData($plan_id, $findOrder->phone,$findOrder->ref);
                                \Log::info("From Bank JONET");
                                \Log::info($response);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if ($response['status'] == 'Failed' || $response['responseCode'] == "002" || $response['responseCode'] == "101" || $response['responseCode'] == "102" || $response['responseCode'] == "103" || $response['responseCode'] == "104" || $response['responseCode'] == "105" || $response['responseCode'] == "108" || $response['responseCode'] == "109" || $response['responseCode'] == "106" || $response['responseCode'] == "107" || $response['responseCode'] == "JO101" || $response['responseCode'] == "JO102" || $response['responseCode'] == "JO103" || $response['responseCode'] == "JO104" || $response['responseCode'] == "JO106"|| $response['responseCode'] == "JO107" || $response['responseCode'] == "JO109" || $response['responseCode'] == "JO110" || $response['responseCode'] == "JO119") {
                                    \Log::info("it failed JONET");
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    $prev_bal = User::find($findOrder->user_id)->wallet;

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);
                                    
                                    $new_bal = User::find($findOrder->user_id)->wallet;
                                    


                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = $new_bal;
                                    $order->prev_bal = $prev_bal;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                } elseif($response['responseCode'] == "200") {
                                    \Log::info("it was successful JONET");

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }

                                    $updateOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updateOrder->status = 1;
                                    $updateOrder->response = $response['server_response'];
                                    $updateOrder->save();
                                    $msg = $response['server_response'] . " Jonet Ref: " . $response['customer_id'];

                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "AUTOPILOT") {
            
                                $network_id = $this->parseAutoPilotNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                $plan_id = $getSelectedProduct->autopilot_code;
                                $datatype = $getSelectedProduct->autopilot_datatype;

                                $response = $this->autoPilotData($network_id,$datatype,$plan_id, $findOrder->phone, $findOrder->ref);
                                \Log::info("From Bank AUTOPILOT");
                                \Log::info($response);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if ($response['status'] == false || $response['code'] == "424" || $response['code'] == 500) {
                                    \Log::info("it failed AUTOPILOT");
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    $prev_bal = User::find($findOrder->user_id)->wallet;

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);
                                    
                                    $new_bal = User::find($findOrder->user_id)->wallet;
                                    


                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = $new_bal;
                                    $order->prev_bal = $prev_bal;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->autopilot_code, $findOrder->ref, $subcategory->telegram);
                                } elseif($response['status'] == true) {
                                    \Log::info("it was successful AUTOPILOT From bank");

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }

                                    $updateOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updateOrder->status = 1;
                                    $updateOrder->response = $response['data']['message'];
                                    $updateOrder->save();
                                    $msg = $response['data']['message'] . " AutoPilot Ref: " . $response['data']['reference'];

                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->autopilot_code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "AUTOSYNCAWUF") {
            
                                $network_id = $this->parseAutoPilotNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                
                                $product_id = $getSelectedProduct->product_id;
                                $variation_code = $getSelectedProduct->variation_code;
                                $pin = $getSelectedProduct->pin;

                                $response = $this->autoSyncPortal($findOrder->ref,$findOrder->phone,$product_id,$variation_code,$pin);
                                \Log::info("From Bank AUTOSYNC RESPONSE");
                                \Log::info($response);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if (isset($response['data']) && isset($response['data']['transaction']) && $response['data']['transaction']['status'] == "pending"){
                                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
                                } elseif(isset($response['status']) && $response['status'] == "error"){
                                
                                    \Log::info("it failed AUTOPILOT");
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    $prev_bal = User::find($findOrder->user_id)->wallet;

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);
                                    
                                    $new_bal = User::find($findOrder->user_id)->wallet;
                                    


                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = $new_bal;
                                    $order->prev_bal = $prev_bal;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->autopilot_code, $findOrder->ref, $subcategory->telegram);
                                } elseif (isset($response['data']) && isset($response['data']['transaction']) && $response['data']['transaction']['status'] == "failed"){
                                
                                    \Log::info("it failed AUTOPILOT");
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    $prev_bal = User::find($findOrder->user_id)->wallet;

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);
                                    
                                    $new_bal = User::find($findOrder->user_id)->wallet;
                                    


                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = $new_bal;
                                    $order->prev_bal = $prev_bal;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->autopilot_code, $findOrder->ref, $subcategory->telegram);
                                    
                                } elseif(isset($response['data']) && isset($response['data']['transaction']) && $response['data']['transaction']['status'] == "successful") {
                                    \Log::info("it was successful AUTOPILOT From bank");

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }

                                    $updateOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updateOrder->status = 1;
                                    $updateOrder->response = $response['data']['message'];
                                    $updateOrder->save();
                                    $msg = $response['data']['message'] . " AutoPilot Ref: " . $response['data']['reference'];

                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->autopilot_code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            
                            } else if (!is_null($subcategory->description) && $subcategory->description == "TBCHPORTAL") {
            
                                $network_id = $this->parseTBCHPortalID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                
                                $plan_id = $getSelectedProduct->tbch_code;

                                $response = $this->tbchPortal($network_id,$plan_id, $findOrder->phone, $findOrder->ref);
                                \Log::info("From Bank TBCHPORTAL");
                                \Log::info($response);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if ($response['code'] == 216) {
                                    \Log::info("it failed TBCHPORTAL");
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    $prev_bal = User::find($findOrder->user_id)->wallet;

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);
                                    
                                    $new_bal = User::find($findOrder->user_id)->wallet;
                                    


                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = $new_bal;
                                    $order->prev_bal = $prev_bal;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->response = $response['data']['gateway_response'];
                                    $order->status = 2;
                                    $order->save();
                                    $msg = $response['data']['gateway_response'];

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->autopilot_code, $findOrder->ref, $subcategory->telegram);
                                } elseif($response['code'] == "00" && $response['data']['status'] == "Successful") {
                                    \Log::info("it was successful AUTOPILOT From bank");

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }

                                    $updateOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updateOrder->status = 1;
                                    $updateOrder->response = $response['data']['gateway_response'];
                                    $updateOrder->save();
                                    $msg = $response['data']['gateway_response'] . " TBCHPORTAL Ref: " . $response['data']['customer_ref'];

                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->autopilot_code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else {

                                //adward bonus;
                                if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                    $subcategory =  Subcategory::find(23);
                                    $productCollection = json_decode($subcategory->products);
                                    $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                    $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                    $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                    $order = new Order();
                                    $order->ref = $this->referenceCode();
                                    $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                    $order->subcategory_id =  23;
                                    $order->plan =  $subcategory->title . " " . $bonus . "%";
                                    $order->amount = $amountadwarded;
                                    $order->quantity =  1;
                                    $order->subtotal =  $amountadwarded;
                                    $order->total = $amountadwarded;
                                    $order->bal = $getbal->wallet;
                                    $order->channel = $findOrder->channel;
                                    $order->prev_bal = $getbal->wallet - $bonus;
                                    $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                    $order->status = 1;
                                    $order->save();
                                }
                            }

                            $updOrder = Order::where('ref', $findOrder->ref)->first();
                            $updOrder->status = 1;
                            $updOrder->save();

                            $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                        } else {
                            $findUser = User::where('email', $response['customer']['email'])->first();

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
                            
                            if (!is_null($findUser)) {
                                $amt = $response['settlementAmount'];
                                $subcategory = Subcategory::where('title', 'Monnify')->first();
                                $ref = $response['transactionReference'];
                            
                                $order = new Order();
                                $order->ref = $ref;
                                $order->user_id = $findUser->id;
                                $order->subcategory_id = $subcategory->id;
                                $order->plan = $subcategory->title;
                                $order->amount = $amt;
                                $order->quantity = 1;
                                $order->prev_bal = $findUser->wallet;
                                $order->subtotal = $amt;
                                $order->total = $amt;
                            
                                if (in_array(strtolower($findUser->email), array_map('strtolower', $blockedEmails))) {
                                    // Block crediting wallet
                                    $order->bal = $findUser->wallet; // wallet unchanged
                                    $order->description = $subcategory->title . " Card Auto Credited";
                                    $order->status = 2; // blocked status code
                                    $order->save();
                                } else {
                                    // Normal crediting
                                    if ($this->isCredited($amt, $findUser->id)) {
                                        $order->bal = $findUser->wallet + $amt;
                                        $order->description = $subcategory->title . " Card Auto Credited";
                                        $order->status = 1;
                                        $order->save();
                                    }
                                }
                            }

                        }
                    }
                    break;
                    
                case "API_NOTIFICATION":
                    $findOrder = Order::where('ref', $response['transactionReference'])->where('status', 0)->first();
                    if (Order::where('ref', $response['transactionReference'])->exists() == false) {
                        if(!is_null($findOrder)){
                            $subcategory =  Subcategory::find($findOrder->subcategory_id);
                        }


                        if (isset($subcategory) && $subcategory->category_id == 2) {


                            if (!is_null($subcategory->description) &&  $subcategory->description == "SMEPLUG") {

                                $network_id = $this->translateSMEPlugAirtimeNetwork($subcategory->title);

                                $response =  $this->SMEPlugAirtimeApi($network_id, intVal($findOrder->amount), $findOrder->phone);
                                if (is_null($response) || ($response->status == false)) {
                                } else {

                                    $findOrder->status = 1;
                                    $findOrder->response = $response->data->msg;
                                    $findOrder->save();
                                    $msg =  $response->data->msg . " SMEPLUG Ref: " . $response->data->reference;

                                    $this->sendTelegramMessage2($findOrder->user, intVal(intVal($findOrder->amount)), $findOrder->phone, $subcategory->pins, $subcategory->pins, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            }

                            $findOrder->status = 1;
                            $findOrder->save();
                            // $channelText = $subcategory->pins;
                            // $channelText = str_replace(['AMOUNT', 'PHONE', 'REF'], [$amount, $phone, $ref], $channelText);

                            $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $subcategory->pins, $findOrder->ref, $subcategory->telegram);
                        } else if (isset($subcategory) && $subcategory->category_id == 1) {
                            //$subcategory =  Subcategory::find($findOrder->subcategory_id);
                            $productCollection = collect(json_decode($subcategory->products));

                            // $plan = $productCollection->first()->plan;
                            $plan =  $findOrder->plan;
                            $getSelectedProduct = $productCollection->where('plan', $plan)->first();
                            $amountActual =  $this->getUserLevel($getSelectedProduct, User::find($findOrder->user_id)->userlevel);
                            $network = explode(' ', $subcategory->title)[0];


                            if (!is_null($subcategory->description) && $subcategory->description == "SMEPLUG") {

                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                $plan_id = $getSelectedProduct->smeplug_id;
                                Log::debug($plan_id);

                                Log::debug($plan);

                                $response =  $this->SMEPlugApi($network_id, $plan_id, $findOrder->phone);
                                if (is_null($response) || ($response->status == false)) {
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);


                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = User::find($findOrder->user_id)->wallet + $findOrder->amount;
                                    $order->prev_bal = User::find($findOrder->user_id)->wallet;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $findOrder->amount;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                } else {

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }
                                    $findOrder->status = 1;
                                    $findOrder->response = $response->data->msg;
                                    $findOrder->save();
                                    $msg =  $response->data->msg . " SMEPLUG Ref: " . $response->data->reference;


                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "SIMSERVER") {

                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                $plan_id = $getSelectedProduct->ss_product_code;


                                $response = $this->SIMServerBuy($plan_id, 1, $findOrder->phone, $findOrder->ref);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                // return $this->sendError($response, $response);

                                if (is_null($response) || ($response['status'] == false)) {

                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);



                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = User::find($findOrder->user_id)->wallet + $findOrder->amount;
                                    $order->prev_bal = User::find($findOrder->user_id)->wallet;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $findOrder->amount;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                } else {

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }
                                    $findOrder->status = 1;
                                    $findOrder->response = $response['data']['true_response'];
                                    $findOrder->save();
                                    $msg =  $response['data']['true_response'] . " SIMSERVER Ref: " . $response['data']['true_response'];


                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "EGMS") {

                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                $plan_id = $getSelectedProduct->egms_plan_id;

                                $response = $this->EGMSPurchase($plan_id, 1, $findOrder->phone, $findOrder->ref);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if (is_null($response) || !isset($response['status']) || ($response['status'] != "ok")) {
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);



                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = User::find($findOrder->user_id)->wallet + $findOrder->amount;
                                    $order->prev_bal = User::find($findOrder->user_id)->wallet;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                } else {

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }
                                    $findOrder->status = 1;
                                    $findOrder->response = $response['message'];
                                    $findOrder->save();
                                    $msg =  $response['message'] . " EGMS Ref: " . $response['message'];


                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "AIRTIMENIGERIA") {

                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                $plan_id = $getSelectedProduct->aa_package_code;

                                $response = $this->purchaseAirtimeNigeria($plan_id, 1, $findOrder->phone, $findOrder->ref);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if (is_null($response) || ($response['details']['order_status'] == "failed")) {
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);



                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = User::find($findOrder->user_id)->wallet + $findOrder->amount;
                                    $order->prev_bal = User::find($findOrder->user_id)->wallet;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                } else {

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }
                                    $findOrder->status = 1;
                                    $findOrder->response = $response['details']['gateway_response'];
                                    $findOrder->save();
                                    $msg = $response['details']['gateway_response'] . " AIRTIMENIGERIA Ref: " . $response['details']['reference'];
                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "DATABAY") {

                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                $plan_id = $getSelectedProduct->databaycode;

                                $response = $this->dataBayBuyData($plan_id,  $findOrder->phone);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if (is_null($response) || ($response['success'] == false)) {
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);



                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = User::find($findOrder->user_id)->wallet + $findOrder->amount;
                                    $order->prev_bal = User::find($findOrder->user_id)->wallet;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                } else {

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }
                                    $findOrder->status = 1;
                                    $findOrder->response = $response['payload']['networkResponseMessage'];
                                    $findOrder->save();
                                    $msg = $response['payload']['networkResponseMessage'] . " DATABAY Ref: " . $response['payload']['reference'];
                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "OGADAM") {

                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                $plan_id = $getSelectedProduct->ogadam_id;

                                $response = $this->ogaDamBuyData($network_id, $plan_id, $findOrder->phone, $findOrder->ref);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if (is_null($response) || ($response['code'] == 424)) {
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);



                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = User::find($findOrder->user_id)->wallet + $findOrder->amount;
                                    $order->prev_bal = User::find($findOrder->user_id)->wallet;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                } else  if (is_null($response) || ($response['code'] != 200)) {
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 0;
                                    $updOrder->save();
                                    $msg = $response['data']['msg'] . " OGADAM Ref: " . $response['data']['msg'];

                                    $this->sendTelegramMessage($findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                } else {

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }

                                    $updateOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updateOrder->status = 1;
                                    $updateOrder->response = $response['data']['msg'];
                                    $updateOrder->save();
                                    $msg = $response['data']['msg'] . " OGADAM Ref: " . $response['data']['msg'];

                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "AIRTELEDUSITE") {

                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                 $plan_id = $getSelectedProduct->airtel_id;
                                 $request_body = $getSelectedProduct->airtel_plan;

                                $response = $this->AirtelEduBuyData($findOrder->phone, $request_body);
                                \Log::info("Airtel edu site from bank app sdk");
                                \Log::info($response);
                               
                                    if(isset($response)){
                                        $dstatus = $response['data'][0]['status'];
                                        $desc = $response['data'][0]['description'];
                                    }
                                    if (!isset($dstatus) || is_null($dstatus) || $dstatus == "error") {
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);



                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = User::find($findOrder->user_id)->wallet + $findOrder->amount;
                                    $order->prev_bal = User::find($findOrder->user_id)->wallet;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                } else {

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }

                                    $updateOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updateOrder->status = 1;
                                    $updateOrder->response = $desc;
                                    $updateOrder->save();
                                    $msg = $desc . " AIRTEL EDUSUITE Ref: " . $desc;

                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "GONGOZ") {

                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                $plan_id = $getSelectedProduct->gongzo_id;

                                $response = $this->gongozconceptData($network_id, $plan_id, $findOrder->phone);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if (is_null($response) || ($response['Status'] != 'successful')) {
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);



                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = User::find($findOrder->user_id)->wallet + $findOrder->amount;
                                    $order->prev_bal = User::find($findOrder->user_id)->wallet;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                } else {

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }

                                    $updateOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updateOrder->status = 1;
                                    $updateOrder->response = $response['apiresponse'];
                                    $updateOrder->save();
                                    $msg = $response['apiresponse'] . " GONGOZ Ref: " . $response['ident'];

                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "AYINLAK") {

                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                $plan_id = $getSelectedProduct->ayinlak_id;

                                $response = $this->ayinlakconnectData($network_id, $plan_id, $findOrder->phone);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if (is_null($response) || ($response['Status'] != 'successful')) {
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);



                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = User::find($findOrder->user_id)->wallet + $findOrder->amount;
                                    $order->prev_bal = User::find($findOrder->user_id)->wallet;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                }else {

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }

                                    $updateOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updateOrder->status = 1;
                                    $updateOrder->response = $response['api_response'];
                                    $updateOrder->save();
                                    $msg = $response['api_response'] . " AYINLAK Ref: " . $response['ident'];

                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "JONET") {
            
                                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                $plan_id = $getSelectedProduct->jonet_code;

                                $response = $this->jonetData($plan_id, $findOrder->phone,$findOrder->ref);
                                \Log::info("From Bank JONET");
                                \Log::info($response);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if ($response['status'] == 'Failed' || $response['responseCode'] == "002" || $response['responseCode'] == "101" || $response['responseCode'] == "102" || $response['responseCode'] == "103" || $response['responseCode'] == "104" || $response['responseCode'] == "105" || $response['responseCode'] == "108" || $response['responseCode'] == "109" || $response['responseCode'] == "106" || $response['responseCode'] == "107" || $response['responseCode'] == "JO101" || $response['responseCode'] == "JO102" || $response['responseCode'] == "JO103" || $response['responseCode'] == "JO104" || $response['responseCode'] == "JO106"|| $response['responseCode'] == "JO107" || $response['responseCode'] == "JO109" || $response['responseCode'] == "JO110" || $response['responseCode'] == "JO119") {
                                    \Log::info("it failed JONET");
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    $prev_bal = User::find($findOrder->user_id)->wallet;

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);
                                    
                                    $new_bal = User::find($findOrder->user_id)->wallet;
                                    


                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = $new_bal;
                                    $order->prev_bal = $prev_bal;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                                } elseif($response['responseCode'] == "200") {
                                    \Log::info("it was successful JONET");

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }

                                    $updateOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updateOrder->status = 1;
                                    $updateOrder->response = $response['server_response'];
                                    $updateOrder->save();
                                    $msg = $response['server_response'] . " Jonet Ref: " . $response['customer_id'];

                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "AUTOPILOT") {
            
                                $network_id = $this->parseAutoPilotNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                $plan_id = $getSelectedProduct->autopilot_code;
                                $datatype = $getSelectedProduct->autopilot_datatype;

                                $response = $this->autoPilotData($network_id,$datatype,$plan_id, $findOrder->phone, $findOrder->ref);
                                \Log::info("From Bank AUTOPILOT");
                                \Log::info($response);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if ($response['status'] == false || $response['code'] == "424" || $response['code'] == 500) {
                                    \Log::info("it failed AUTOPILOT");
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    $prev_bal = User::find($findOrder->user_id)->wallet;

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);
                                    
                                    $new_bal = User::find($findOrder->user_id)->wallet;
                                    


                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = $new_bal;
                                    $order->prev_bal = $prev_bal;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->autopilot_code, $findOrder->ref, $subcategory->telegram);
                                } elseif($response['status'] == true) {
                                    \Log::info("it was successful AUTOPILOT From bank");

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }

                                    $updateOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updateOrder->status = 1;
                                    $updateOrder->response = $response['data']['message'];
                                    $updateOrder->save();
                                    $msg = $response['data']['message'] . " AutoPilot Ref: " . $response['data']['reference'];

                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->autopilot_code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else if (!is_null($subcategory->description) && $subcategory->description == "AUTOSYNCAWUF") {
            
                                $network_id = $this->parseAutoPilotNetworkID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                
                                $product_id = $getSelectedProduct->product_id;
                                $variation_code = $getSelectedProduct->variation_code;
                                $pin = $getSelectedProduct->pin;

                                $response = $this->autoSyncPortal($findOrder->ref,$findOrder->phone,$product_id,$variation_code,$pin);
                                \Log::info("From Bank AUTOSYNC RESPONSE");
                                \Log::info($response);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if (isset($response['data']) && isset($response['data']['transaction']) && $response['data']['transaction']['status'] == "pending"){
                                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
                                } elseif(isset($response['status']) && $response['status'] == "error"){
                                
                                    \Log::info("it failed AUTOPILOT");
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    $prev_bal = User::find($findOrder->user_id)->wallet;

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);
                                    
                                    $new_bal = User::find($findOrder->user_id)->wallet;
                                    


                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = $new_bal;
                                    $order->prev_bal = $prev_bal;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->autopilot_code, $findOrder->ref, $subcategory->telegram);
                                } elseif (isset($response['data']) && isset($response['data']['transaction']) && $response['data']['transaction']['status'] == "failed"){
                                
                                    \Log::info("it failed AUTOPILOT");
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    $prev_bal = User::find($findOrder->user_id)->wallet;

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);
                                    
                                    $new_bal = User::find($findOrder->user_id)->wallet;
                                    


                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = $new_bal;
                                    $order->prev_bal = $prev_bal;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->status = 2;
                                    $order->save();

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->autopilot_code, $findOrder->ref, $subcategory->telegram);
                                    
                                } elseif(isset($response['data']) && isset($response['data']['transaction']) && $response['data']['transaction']['status'] == "successful") {
                                    \Log::info("it was successful AUTOPILOT From bank");

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }

                                    $updateOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updateOrder->status = 1;
                                    $updateOrder->response = $response['data']['message'];
                                    $updateOrder->save();
                                    $msg = $response['data']['message'] . " AutoPilot Ref: " . $response['data']['reference'];

                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->autopilot_code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            
                            } else if (!is_null($subcategory->description) && $subcategory->description == "TBCHPORTAL") {
            
                                $network_id = $this->parseTBCHPortalID(strtolower($network));
                                // $plan_id = $getSelectedProduct->code;
                                
                                $plan_id = $getSelectedProduct->tbch_code;

                                $response = $this->tbchPortal($network_id,$plan_id, $findOrder->phone, $findOrder->ref);
                                \Log::info("From Bank TBCHPORTAL");
                                \Log::info($response);
                                //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
                                //return $this->sendError($response, $response);
                                if ($response['code'] == 216) {
                                    \Log::info("it failed TBCHPORTAL");
                                    $updOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updOrder->status = 4;
                                    $updOrder->save();

                                    $prev_bal = User::find($findOrder->user_id)->wallet;

                                    //refund
                                    $this->isCredited($findOrder->subtotal, $findOrder->user_id);
                                    
                                    $new_bal = User::find($findOrder->user_id)->wallet;
                                    


                                    $order = new Order();
                                    $order->ref = $findOrder->ref;
                                    $order->user_id =  $findOrder->user_id;
                                    $order->subcategory_id =  $subcategory->id;
                                    $order->plan =  $subcategory->title . " " . $plan . " at N" . $amountActual;
                                    $order->amount =  $findOrder->amount;
                                    $order->quantity =  1;

                                    $order->subtotal =  $findOrder->subtotal;
                                    $order->total = $findOrder->total;
                                    $order->phone = $findOrder->phone;
                                    $order->bal = $new_bal;
                                    $order->prev_bal = $prev_bal;
                                    $order->description = $subcategory->title . " " . $plan .
                                        " at N" . $amountActual;
                                    $order->response = $response['data']['gateway_response'];
                                    $order->status = 2;
                                    $order->save();
                                    $msg = $response['data']['gateway_response'];

                                    $this->sendTelegramMessage2($findOrder->user, $findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->autopilot_code, $findOrder->ref, $subcategory->telegram);
                                } elseif($response['code'] == "00" && $response['data']['status'] == "Successful") {
                                    \Log::info("it was successful AUTOPILOT From bank");

                                    if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                        $subcategory =  Subcategory::find(23);
                                        $productCollection = json_decode($subcategory->products);
                                        $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                        $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                        $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                        $order = new Order();
                                        $order->ref = $this->referenceCode();
                                        $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                        $order->subcategory_id =  23;
                                        $order->plan =  $subcategory->title . " " . $bonus . "%";
                                        $order->amount = $amountadwarded;
                                        $order->quantity =  1;
                                        $order->subtotal =  $amountadwarded;
                                        $order->total = $amountadwarded;
                                        $order->bal = $getbal->wallet;
                                        $order->channel = $findOrder->channel;
                                        $order->prev_bal = $getbal->wallet - $bonus;
                                        $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                        $order->status = 1;
                                        $order->save();
                                    }

                                    $updateOrder = Order::where('ref', $findOrder->ref)->first();
                                    $updateOrder->status = 1;
                                    $updateOrder->response = $response['data']['gateway_response'];
                                    $updateOrder->save();
                                    $msg = $response['data']['gateway_response'] . " TBCHPORTAL Ref: " . $response['data']['customer_ref'];

                                    $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->autopilot_code, $findOrder->ref, $subcategory->telegram, $msg);
                                }
                            } else {

                                //adward bonus;
                                if (User::find($findOrder->user_id)->bonusIsAwardable()) {

                                    $subcategory =  Subcategory::find(23);
                                    $productCollection = json_decode($subcategory->products);
                                    $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
                                    $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


                                    $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

                                    $order = new Order();
                                    $order->ref = $this->referenceCode();
                                    $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
                                    $order->subcategory_id =  23;
                                    $order->plan =  $subcategory->title . " " . $bonus . "%";
                                    $order->amount = $amountadwarded;
                                    $order->quantity =  1;
                                    $order->subtotal =  $amountadwarded;
                                    $order->total = $amountadwarded;
                                    $order->bal = $getbal->wallet;
                                    $order->channel = $findOrder->channel;
                                    $order->prev_bal = $getbal->wallet - $bonus;
                                    $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
                                    $order->status = 1;
                                    $order->save();
                                }
                            }

                            $updOrder = Order::where('ref', $findOrder->ref)->first();
                            $updOrder->status = 1;
                            $updOrder->save();

                            $this->sendTelegramMessage2($findOrder->user, intVal($findOrder->amount), $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
                        } else {
                            $findUser = User::where('email', $response['customer']['email'])->first();

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
                            
                            if (!is_null($findUser)) {
                                $amt = $response['settlementAmount'];
                                $subcategory = Subcategory::where('title', 'Monnify')->first();
                                $ref = $response['transactionReference'];
                            
                                $order = new Order();
                                $order->ref = $ref;
                                $order->user_id = $findUser->id;
                                $order->subcategory_id = $subcategory->id;
                                $order->plan = $subcategory->title;
                                $order->amount = $amt;
                                $order->quantity = 1;
                                $order->prev_bal = $findUser->wallet;
                                $order->subtotal = $amt;
                                $order->total = $amt;
                            
                                if (in_array(strtolower($findUser->email), array_map('strtolower', $blockedEmails))) {
                                    // Block crediting wallet
                                    $order->bal = $findUser->wallet; // wallet unchanged
                                    $order->description = $subcategory->title . " Card Auto Credited";
                                    $order->status = 2; // blocked status code
                                    $order->save();
                                } else {
                                    // Normal crediting
                                    if ($this->isCredited($amt, $findUser->id)) {
                                        $order->bal = $findUser->wallet + $amt;
                                        $order->description = $subcategory->title . "Card Auto Credited";
                                        $order->status = 1;
                                        $order->save();
                                    }
                                }
                            }

                        }
                    }
                    break;

                default:
                    break;
            }
        }
    }
}
