<?php

namespace App\Http\Controllers\API;

use App\Models\Order;

use App\Models\Farmer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Subcategory;
use App\Traits\WalletTrait;
use Illuminate\Http\Request;
use App\Traits\ReferenceTrait;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Setting;
use App\Models\User;
use App\Traits\TelegramTrait;

class PaymentController extends BaseController
{
    use WalletTrait;
    use ReferenceTrait;
    use TelegramTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = Payment::orderBy('created_at', 'desc')->paginate(2);

        $search = $request->input('search');
        $user_id = $request->input('user_id');



        if ($search) {
            $data =  Payment::where('order_id', 'like', '%' . $search . '%')
                ->orWhere('amount', 'like', '%' . $search . '%')
                ->orWhere('status', 'like', '%' . $search . '%')
                ->paginate(2);
        }

        if ($user_id) {
            $data =  Payment::where('farmer_id', $user_id)->paginate(2);
        }


        $products = PaymentResource::collection($data);



        return $products;
        //return $this->sendResponse(ProductResource::collection($products), 'Products retrieved successfully.');
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'user_id' => 'required',
            'order_id' => 'required',
            'payment_method_id' => 'required',
            // 'user_id' => 'required',
            'amount' => 'required',

        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $product = Payment::create($input);


        return $this->sendResponse(new PaymentResource($product), 'Product created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $product = Payment::find($id);

        if (is_null($product)) {
            return $this->sendError('Product not found.');
        }

        return $this->sendResponse(new PaymentResource($product), 'Product retrieved successfully.');
    }


    public function initiateManualPayment(Request $request)
    {

        $subcategory =  Subcategory::where('title', 'Manual')->first();


        if (is_null($request->paymentname)) {
            return $this->sendError('Enter a name', 'Enter a name');
        }
        if (is_null($request->type)) {
            return $this->sendError('Enter a payment type', 'Enter a payment type');
        }

        $fundLimitsubCategory =  Subcategory::where('title', 'Upgrade')->first();

        $fundproductCollection = collect(json_decode($fundLimitsubCategory->products));

        $getSelectedLevel = $fundproductCollection->where('title', $this->translateLevel($this->user->userlevel))->first();



        if ($request->amount < $getSelectedLevel->fundmin) {
            return $this->sendError('You cannot fund below ' . $getSelectedLevel->fundmin, 'You cannot fund below ' . $getSelectedLevel->fundmin);
        }

        if ($request->amount > $getSelectedLevel->fundmax) {
            return $this->sendError('You cannot fund above ' . $getSelectedLevel->fundmax, 'You cannot fund above ' . $getSelectedLevel->fundmax);
        }

        $productCollection = collect(json_decode($subcategory->products));
        $getSelectedProduct = $productCollection->where('account', $request->bank)->first();

        $ref = $this->referenceCode();
        $order = new Order();
        $order->ref = $ref;
        $order->user_id =  $this->user->id;
        $order->subcategory_id =  $subcategory->id;
        $order->plan =  $subcategory->title;
        $order->amount = $request->amount;
        $order->quantity =  1;
        $order->channel = is_null($request->channel) ? 'Web' : 'App';

        $order->subtotal =  $request->amount;
        $order->total = $request->amount;
        $order->description = $subcategory->title . " Name: " . $request->paymentname . ' Bank: ' . $getSelectedProduct->bank . " " . $getSelectedProduct->account . " " . $getSelectedProduct->name  . ' Type: ' . $request->type;
        $order->status = 0;
        $order->save();

        $msg = "Name: " . $request->paymentname . ' Bank: ' . $getSelectedProduct->bank . " " . $getSelectedProduct->account . " " . $getSelectedProduct->name  . ' Type: ' . $request->type . ' Amount N' . $request->amount . ' Email: ' . $this->user->email;

        $this->sendTelegramMessage($request->amount, "", $subcategory->title, $getSelectedProduct->account, $ref, $subcategory->telegram, $msg);

        return $this->sendResponse('Payment initialized successfully. It will take less than 1hr to reflect on your account', 'Payment initialized successfully.  It will take less than 1hr to reflect on your account');
    }




    public function initiatePayment(Request $request)
    {

        $subcategory =  Subcategory::where('title', 'Monnify')->first();

        if (is_null($request->user_id)) {
            return $this->sendError('User not found.', 'User not found.');
        }

        $fundLimitsubCategory =  Subcategory::where('title', 'Upgrade')->first();

        $fundproductCollection = collect(json_decode($fundLimitsubCategory->products));

        $getSelectedLevel = $fundproductCollection->where('title', $this->translateLevel($this->user->userlevel))->first();



        if ($request->amount < $getSelectedLevel->fundmin) {
            return $this->sendError('You cannot fund below ' . $getSelectedLevel->fundmin, 'You cannot fund below ' . $getSelectedLevel->fundmin);
        }

        if ($request->amount > $getSelectedLevel->fundmax) {
            return $this->sendError('You cannot fund above ' . $getSelectedLevel->fundmax, 'You cannot fund above ' . $getSelectedLevel->fundmax);
        }


        $ref = $this->referenceCode();
        // $order = new Order();
        // $order->ref = $ref;
        // $order->user_id =  $request->user_id;
        // $order->subcategory_id =  $subcategory->id;
        // $order->plan =  $subcategory->title;
        // $order->amount = $request->amount;
        // $order->quantity =  1;
        // $order->channel = is_null($request->channel) ? 'Web' : 'App';
        // $order->subtotal =  $request->amount;
        // $order->total = $request->amount;
        // $order->description = $subcategory->title . " Auto Credited";
        // $order->status = 0;
        // $order->save();
        return $this->sendResponse($ref, 'Intialized');
    }



    public function initiateOrder(Request $request)
    {
        $phone = $request->phonenumber;
        $subcategory =  Subcategory::find($request->subcategory_id);

        if (is_null($request->user_id)) {
            return $this->sendError('User not found.', 'User not found.');
        }
        $network = explode(' ', $subcategory->title)[0];

        if (is_null($phone)) {
            return $this->sendError('Phone Number is required', 'Phone Number is required');
        }

        if (!$request->ported  && !$this->isValidPhoneProvider(strtolower($network), $phone)) {
            return $this->sendError('This is not an ' . $network . ' Phone Number', 'This is not an ' . $network . ' Phone Number');
        }
        $amount = intVal($request->amount);


        
        if (!is_null($subcategory->description) && $subcategory->description == "JONET") {
            $ref = $this->jonetReferenceCode();
        } elseif (!is_null($subcategory->description) && $subcategory->description == "AUTOPILOT") {
            $ref = $this->autoPilotReferenceCode();
        }elseif (!is_null($subcategory->description) && $subcategory->description == "AUTOPILOTAWUF") {
            $ref = $this->autoPilotReferenceCode();
        }elseif (!is_null($subcategory->description) && $subcategory->description == "TBCHPORTAL") {
            $ref = $this->autoPilotReferenceCode();
        } else {
            $ref = $this->referenceCode();
        }
        
        // $order = new Order();
        // $order->ref = $ref;
        // $order->user_id =  $request->user_id;
        // $order->subcategory_id =  $subcategory->id;
        // $order->plan = is_null($request->plan_id) ? $subcategory->title . " N" . $amount : $request->plan_id;
        // $order->amount = $amount;
        // $order->quantity =  1;
        // $order->phone = $phone;
        // $order->channel = is_null($request->channel) ? 'Web' : 'App';
        // $order->subtotal = !is_null($request->newamount) ? $request->newamount : $amount;
        // $order->total = !is_null($request->newamount) ? $request->newamount : $amount;
        // $order->description = $subcategory->title . " N" . $amount;
        // $order->status = 0;
        // $order->save();
        // return $this->sendResponse($ref, 'Intialized');
        return $this->sendError("This service is not available at the moment, please Fund your Wallet","This service is not available at the moment, please Fund your Wallet");
    }


    public function ConfirmOrder(Request $request)
    {
        if (is_null($request->ref)) {
            return $this->sendError('Data not found.', 'Data not found.');
        }

        $order = Order::where('ref', $request->ref)->first();

        if (is_null($order)) {
            return $this->sendError('Not initialized', 'Not initialized');
        }

        if ($order->status == 0) {
            // $findOrder = $order;
            // if ($findOrder->category_id == 2) {
            //     $subcategory =  Subcategory::find($findOrder->subcategory_id);


            //     if (!is_null($findOrder->description) && $findOrder->description == "SMEPLUG") {

            //         $network_id = $this->translateSMEPlugAirtimeNetwork($findOrder->title);

            //         $response =  $this->SMEPlugAirtimeApi($network_id, $findOrder->amount, $findOrder->phone);

            //         if (is_null($response) || ($response->status == false)) {
            //             $updOrder = Order::where('ref', $request->ref)->first();
            //             $updOrder->status = 4;
            //             $updOrder->save();
            //             return $this->sendError("Transaction failed", "Transaction failed");
            //         } else {

            //             $findOrder->status = 1;
            //             $findOrder->response = $response->data->msg;
            //             $findOrder->save();
            //             $msg =  $response->data->msg . " SMEPLUG Ref: " . $response->data->reference;

            //             $this->sendTelegramMessage($findOrder->amount, $findOrder->phone, $subcategory->pins, $subcategory->pins, $findOrder->ref, $subcategory->telegram, $msg);
            //             return $this->sendResponse($subcategory->title . " Purchase successful", $subcategory->title . " Purchase successful");
            //         }
            //     } else {

            //         // $channelText = $subcategory->pins;
            //         // $channelText = str_replace(['AMOUNT', 'PHONE', 'REF'], [$amount, $phone, $ref], $channelText);
            //         $order->status = 2;
            //         $order->save();

            //         $this->sendTelegramMessage($findOrder->amount, $findOrder->phone, $subcategory->pins, $subcategory->pins, $findOrder->ref, $subcategory->telegram);

            //         return $this->sendResponse($subcategory->title . " Purchase successful", $subcategory->title . " Purchase successful");
            //     }
            // } else if ($findOrder->category_id == 1) {
            //     $subcategory =  Subcategory::find($findOrder->subcategory_id);
            //     $productCollection = collect(json_decode($subcategory->products));
            //     $getSelectedProduct = $productCollection->where('plan', $findOrder->plan)->first();
            //     $amountActual =  $this->getUserLevel($getSelectedProduct, User::find($findOrder->user_id)->userlevel);
            //     $network = explode(' ', $subcategory->title)[0];


            //     if (!is_null($subcategory->description) && $subcategory->description == "SMEPLUG") {

            //         $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
            //         // $plan_id = $getSelectedProduct->code;

            //         $response =  $this->SMEPlugApi($network_id, $findOrder->plan, $findOrder->phone);
            //         if (is_null($response) || ($response->status == false)) {
            //             $updOrder = Order::where('ref', $request->ref)->first();
            //             $updOrder->status = 4;
            //             $updOrder->save();
            //             return $this->sendError("Transaction failed", "Transaction failed");
            //         } else {

            //             if (User::find($findOrder->user_id)->bonusIsAwardable()) {

            //                 $subcategory =  Subcategory::find(23);
            //                 $productCollection = json_decode($subcategory->products);
            //                 $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
            //                 $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


            //                 $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

            //                 $order = new Order();
            //                 $order->ref = $this->referenceCode();
            //                 $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
            //                 $order->subcategory_id =  23;
            //                 $order->plan =  $subcategory->title . " " . $bonus . "%";
            //                 $order->amount = $amountadwarded;
            //                 $order->quantity =  1;
            //                 $order->subtotal =  $amountadwarded;
            //                 $order->total = $amountadwarded;
            //                 $order->bal = $getbal->wallet;
            //                 $order->channel = $findOrder->channel;
            //                 $order->prev_bal = $getbal->wallet - $bonus;
            //                 $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
            //                 $order->status = 1;
            //                 $order->save();
            //             }
            //             $findOrder->status = 1;
            //             $findOrder->response = $response->data->msg;
            //             $findOrder->save();
            //             $msg =  $response->data->msg . " SMEPLUG Ref: " . $response->data->reference;


            //             $this->sendTelegramMessage($findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
            //             return $this->sendResponse($findOrder->plan . " Purchase successful", $findOrder->plan . " Purchase successful");
            //         }
            //     } else if (!is_null($subcategory->description) && $subcategory->description == "SIMSERVER") {

            //         $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
            //         // $plan_id = $getSelectedProduct->code;
            //         $plan_id = $getSelectedProduct->ss_product_code;

            //         $response = $this->SIMServerBuy($plan_id, 1, $findOrder->phone, $findOrder->ref);
            //         //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
            //         // return $this->sendError($response, $response);

            //         if (is_null($response) || ($response->status == false)) {
            //             $updOrder = Order::where('ref', $request->ref)->first();
            //             $updOrder->status = 4;
            //             $updOrder->save();
            //             return $this->sendError("Transaction failed", "Transaction failed");
            //         } else {

            //             if (User::find($findOrder->user_id)->bonusIsAwardable()) {

            //                 $subcategory =  Subcategory::find(23);
            //                 $productCollection = json_decode($subcategory->products);
            //                 $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
            //                 $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


            //                 $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

            //                 $order = new Order();
            //                 $order->ref = $this->referenceCode();
            //                 $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
            //                 $order->subcategory_id =  23;
            //                 $order->plan =  $subcategory->title . " " . $bonus . "%";
            //                 $order->amount = $amountadwarded;
            //                 $order->quantity =  1;
            //                 $order->subtotal =  $amountadwarded;
            //                 $order->total = $amountadwarded;
            //                 $order->bal = $getbal->wallet;
            //                 $order->channel = $findOrder->channel;
            //                 $order->prev_bal = $getbal->wallet - $bonus;
            //                 $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
            //                 $order->status = 1;
            //                 $order->save();
            //             }
            //             $findOrder->status = 1;
            //             $findOrder->response = $response['data']['true_response'];
            //             $findOrder->save();
            //             $msg =  $response['data']['true_response'] . " SIMSERVER Ref: " . $response['data']['true_response'];


            //             $this->sendTelegramMessage($findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
            //         }
            //     } else if (!is_null($subcategory->description) && $subcategory->description == "EGMS") {

            //         $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
            //         // $plan_id = $getSelectedProduct->code;
            //         $plan_id = $getSelectedProduct->egms_plan_id;

            //         $response = $this->EGMSPurchase($plan_id, 1, $findOrder->phone, $findOrder->ref);
            //         //$response =  $this->SMEPlugApi($network_id, $plan_id, $phone);
            //         //return $this->sendError($response, $response);

            //         if (is_null($response) || ($response['status'] != "ok")) {

            //             $updOrder = Order::where('ref', $request->ref)->first();
            //             $updOrder->status = 4;
            //             $updOrder->save();
            //             return $this->sendError("Transaction failed", "Transaction failed");
            //         } else {
            //             if (User::find($findOrder->user_id)->bonusIsAwardable()) {

            //                 $subcategory =  Subcategory::find(23);
            //                 $productCollection = json_decode($subcategory->products);
            //                 $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
            //                 $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


            //                 $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

            //                 $order = new Order();
            //                 $order->ref = $this->referenceCode();
            //                 $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
            //                 $order->subcategory_id =  23;
            //                 $order->plan =  $subcategory->title . " " . $bonus . "%";
            //                 $order->amount = $amountadwarded;
            //                 $order->quantity =  1;
            //                 $order->subtotal =  $amountadwarded;
            //                 $order->total = $amountadwarded;
            //                 $order->bal = $getbal->wallet;
            //                 $order->channel = $findOrder->channel;
            //                 $order->prev_bal = $getbal->wallet - $bonus;
            //                 $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
            //                 $order->status = 1;
            //                 $order->save();
            //             }
            //             $findOrder->status = 1;
            //             $findOrder->response = $response['message'];
            //             $findOrder->save();
            //             $msg =  $response['message'] . " EGMS Ref: " . $response['message'];


            //             $this->sendTelegramMessage($findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram, $msg);
            //             return $this->sendResponse($findOrder->plan . " Purchase successful", $findOrder->plan . " Purchase successful");
            //         }
            //     } else {

            //         //adward bonus;
            //         if (User::find($findOrder->user_id)->bonusIsAwardable()) {

            //             $subcategory =  Subcategory::find(23);
            //             $productCollection = json_decode($subcategory->products);
            //             $bonus = $this->getUserLevel($productCollection, User::find($findOrder->user_id)->userlevel);
            //             $amountadwarded =  $this->adwardBonus(User::find($findOrder->user_id)->referrer()->id, $bonus, $amountActual);


            //             $getbal = User::find(User::find($findOrder->user_id)->referrer()->id);

            //             $order = new Order();
            //             $order->ref = $this->referenceCode();
            //             $order->user_id =  User::find($findOrder->user_id)->referrer()->id;
            //             $order->subcategory_id =  23;
            //             $order->plan =  $subcategory->title . " " . $bonus . "%";
            //             $order->amount = $amountadwarded;
            //             $order->quantity =  1;
            //             $order->subtotal =  $amountadwarded;
            //             $order->total = $amountadwarded;
            //             $order->bal = $getbal->wallet;
            //             $order->channel = $findOrder->channel;
            //             $order->prev_bal = $getbal->wallet - $bonus;
            //             $order->description = $bonus . "% " .  $subcategory->title . "   for Referring " . User::find($findOrder->user_id)->lastname;
            //             $order->status = 1;
            //             $order->save();
            //         }
            //     }

            //     $this->sendTelegramMessage($findOrder->amount, $findOrder->phone, $subcategory->pins, $getSelectedProduct->code, $findOrder->ref, $subcategory->telegram);
            //     return $this->sendResponse($findOrder->plan . " Purchase successful", $findOrder->plan . " Purchase successful");
            // }
        } else {
            return $this->sendError('Transaction processed', 'Transaction processed');
        }
    }

    public function ConfirmPayment(Request $request)
    {

        if (is_null($request->ref)) {
            return $this->sendError('Data not found.', 'Data not found.');
        }

        $order = Order::where('ref', $request->ref)->first();

        // if (is_null($order)) {
        //     return $this->sendError('Not initialized', 'Not initialized');
        // }

        // if ($order->status == 0) {
        //     // $settings = Setting::where('key', 'RAVE_CARD_BANK_CHARGE');
        //     // $charge =  $settings->first()->value;
        //     // $amount = $order->amount - (($charge / 100) * $order->amount);
            
            
        //     $settings = Setting::where('key', 'MONNIFY_CARD_BANK_CHARGE');
        //     $charge =  $settings->first()->value;
        //     $amount = - (($charge / 100) * $order->amount);
            
        //     \Log::info("Mobile App Story");
        //     \Log::info($amount);
        //     \Log::info($order->user_id);
            
        //     return $this->sendResponse('Wallet credited successfully.', 'Wallet credited successfully.');

        //     // if ($this->isCredited($amount, $order->user_id)) {
        //     //     $bal = User::find($order->user_id)->wallet;

        //     //     // $bal =  $this->user->wallet + $order->subtotal;
        //     //     $order->prev_bal = $this->user->wallet;
        //     //     $order->bal = $bal;
        //     //     //$order->description = $order->description . " Prev Bal: $prev, Bal: $bal";

        //     //     $order->status = 1;
        //     //     $order->save();
        //     //     return $this->sendResponse('Wallet credited successfully.', 'Wallet credited successfully.');
        //     // } else {
        //     //     return $this->sendError('We could not credit your wallet', 'Error crediting wallet, contact Admin');
        //     // }
        // } else if ($order->status == 1) {
        //     return $this->sendResponse('Wallet credited successfully.', 'Wallet credited successfully.');
        // } else {
        //     return $this->sendError('We could not credit your wallet', 'Error crediting wallet, contact Admin');
        // }
        
        return $this->sendResponse('Wallet credited successfully.', 'Wallet credited successfully.');
    }



    public function showByUser($user_id)
    {
        $data = Payment::where('user_id', $user_id)->get();

        if (is_null($data)) {
            return $this->sendError('No product for user was found');
        }

        $products = PaymentResource::collection($data);

        return $products;

        //  return $this->sendResponse(new ProductResource($dispatch), 'Product retrieved successfully.');
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id, Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'user_id' => 'required',
            'order_id' => 'required',
            'payment_method_id' => 'required',
            // 'user_id' => 'required',
            'amount' => 'required',

        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        // $product->subcategory_id = $input['subcategory_id'];
        // $product->product_name = $input['product_name'];
        // $product->description = $input['description'];
        // $product->tags = $input['tags'];
        // $product->user_id = $input['user_id'];
        // $product->price = $input['price'];
        // $product->quantity = $input['quantity'];
        // $product->stock = $input['stock'];
        // $product->min_qty = $input['min_qty'];
        // $product->save();
        // $input['farmer_id'] =  $request->farmer_id;
        $product = Payment::find($id);

        $product->update($request->all());



        return $this->sendResponse(new PaymentResource($product), 'Product updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $payment = Payment::find($id);
        $payment->delete();

        return $this->sendResponse([], 'Product deleted successfully.');
    }
}
