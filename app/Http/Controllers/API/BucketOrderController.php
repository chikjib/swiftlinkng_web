<?php

namespace App\Http\Controllers\API;

use App\Models\Setting;
use Carbon\Carbon;
use App\Models\Bucket;
use App\Models\BucketOrder;
use App\Models\Order;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\ReferenceTrait;
use App\Traits\WalletTrait;
use App\Traits\TelegramTrait;
use App\Traits\IntegrationsTrait;
use App\Http\Resources\BucketOrderResource;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\HttpClientException;

use App\Http\Controllers\API\BaseController as BaseController;


class BucketOrderController extends BaseController
{
    use ReferenceTrait;
    use WalletTrait;
    use IntegrationsTrait;
    use TelegramTrait;
    //

    public function index(Request $request)
    {


        $search = $request->input('search');
        $user_id = $request->input('user_id');

        if ($request->has('search')) {

            if ($request->has('field') && $request->field != "Search By" && $request->has('status') && $request->status != "Select Status") {  // 3 active variable search
                $data = BucketOrder::with(['user:id,firstname,phone,email'])
                    //->where('bal', '>', 0)->orWhere('prev_bal', '>', 0)
                    ->where($request->field, 'like', '%' . $search . '%')
                    ->where('status', $request->status)
                    ->orderBy('created_at', 'desc')->paginate(20);
                $products = BucketOrderResource::collection($data);
            } else { // 1 variable
                if ($request->has('field') && $request->field == "type") {
                    $data = BucketOrder::with(['user:id,firstname,phone,email'])
                        //->where('bal', '>', 0)->orWhere('prev_bal', '>', 0)
                        ->where('bucket_id', 8)->orWhere('bucket_id', 9)->orWhere('bucket_id', 10)->orWhere('subcategory_id', 7)
                        ->orderBy('created_at', 'desc')->paginate(20);
                } else {
                    $data = BucketOrder::with(['user:id,firstname,phone,email'])
                        //->where('bal', '>', 0)->orWhere('prev_bal', '>', 0)
                        ->where($request->field, 'like', '%' . $search . '%')
                        ->orderBy('created_at', 'desc')->paginate(20);
                }
                $products = BucketOrderResource::collection($data);
            }

            return $products;
        }

        if ($request->has('search_status')) {
            $search = $request->search_status;
            if ($request->field == 'userphone') {
                $data = BucketOrder::with(['user:id,firstname,phone,email'])
                    ->where('status', 0)
                    ->WhereHas(
                        'user',
                        function ($query) use ($search) {
                            return $query->where('phone', 'LIKE', '%' . $search . '%');
                        }
                    )->orderBy('created_at', 'desc')->paginate(20);
                $products = BucketOrderResource::collection($data);
            } else {
                $data = BucketOrder::with(['user:id,firstname,phone,email'])
                    ->where('status', 0)
                    ->where($request->field, 'like', '%' . $search . '%')
                    ->orderBy('created_at', 'desc')->paginate(20);
                $products = BucketOrderResource::collection($data);
            }
            return $products;
        }

        if ($request->has('section') && $request->section == 1) {
            \Log::info("here works too");

            $data = BucketOrder::with(['user:id,firstname,phone,email'])
                // ->where('bal', '>', 0)->orWhere('prev_bal', '>', 0)
                ->where('status', 0)->orderBy('created_at', 'desc')->paginate(20);
            $products = BucketOrderResource::collection($data);

            return $products;
        }

        if ($request->has('search_user')) {
            $search = $request->search_user;
            if ($request->has('stats')) {
                if ($request->has('field') && $request->field != "Search By" && $request->has('status') && $request->status != "Select Status") {  // 3 active variable search
                    $data = BucketOrder::with(['user:id,firstname,phone,email'])
                        ->where('user_id', $user_id)
                        ->where($request->field, 'like', '%' . $search . '%')
                        ->where('status', $request->status)
                        ->orderBy('created_at', 'desc')->paginate(20);
                    $products = BucketOrderResource::collection($data);
                } else { // 1 variable
                    $data = BucketOrder::with(['user:id,firstname,phone,email'])
                        ->where('user_id', $user_id)
                        ->where($request->field, 'like', '%' . $search . '%')
                        ->orderBy('created_at', 'desc')->paginate(20);
                    $products = BucketOrderResource::collection($data);
                }

            } else {
                if ($request->has('field') && $request->field != "Search By" && $request->has('status') && $request->status != "Select Status") {  // 3 active variable search
                    $data = BucketOrder::with(['user:id,firstname,phone,email'])
                        ->where('user_id', $user_id)
                        ->where($request->field, 'like', '%' . $search . '%')
                        ->where('status', $request->status)
                        ->orderBy('created_at', 'desc')->paginate(20);
                    $products = BucketOrderResource::collection($data);
                } else { // 1 variable
                    $data = BucketOrder::with(['user:id,firstname,phone,email'])
                        ->where('user_id', $user_id)
                        ->where($request->field, 'like', '%' . $search . '%')
                        ->orderBy('created_at', 'desc')->paginate(20);
                    $products = BucketOrderResource::collection($data);
                }

            }

            return $products;
        }

        if ($request->has('user')) {
            $user_id = $request->user;

            if ($request->has('stats')) {
                $data = BucketOrder::with(['user:id,firstname,phone,email'])
                    //->where('bal', '>', 0)->orWhere('prev_bal', '>', 0)
                    ->where('user_id', $user_id)
                    //->where('status', '>', 0)
                    ->orderBy('created_at', 'desc')->paginate(20);
                $products = BucketOrderResource::collection($data);
            } else {
                $data = BucketOrder::with(['user:id,firstname,phone,email'])
                    //->where('bal', '>', 0)->orWhere('prev_bal', '>', 0)
                    ->where('user_id', $user_id)->orderBy('created_at', 'desc')->paginate(20);
                $products = BucketOrderResource::collection($data);
            }


            return $products;
        }

        $data = BucketOrder::with(['user:id,firstname,phone,email'])
            ->orderBy('created_at', 'desc')->paginate(20);


        $products = BucketOrderResource::collection($data);

        return $products;
    }

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

    public function purchaseBucket(Request $request)
    {
        $bucket_id = $request->bucket_id;
        $data_size = $request->data_size;

        \Log::info($request);
        
        $bucket = Bucket::findOrFail($bucket_id);
        if($bucket->is_purchase == 0) {
            return $this->sendError($bucket->title .' Bucket is currently not available',$bucket->title.' Bucket is currently not available');
        }


        $data_bundles = collect(json_decode($bucket->price_per_gb,true));
        
        $wallet_name = $this->getBucketTitle($bucket->id);

        $wallet_status = $wallet_name."_status";

        // Check that bucket transaction is on for this user
        if($this->user->$wallet_status == 0) {
            return $this->sendError($bucket->title .' Bucket purchase denied', $bucket->title . 'Bucket purchase denied');
        }

        if(is_null($request->price)) {
            return $this->sendError("Data size is out of range, please select within the range", "Data size is out of range, please select within the range");
        }

        $matching_bundle = $data_bundles->first(function ($bundle) use ($data_size) {
            \Log::info($bundle['lower_limit']);
            return $data_size >= $bundle['lower_limit'] && $data_size <= $bundle['upper_limit'];
        });



        $amountActual = $data_size * $matching_bundle['price'];

        \Log::info("Data size amount");
        \Log::info($amountActual);


        $ref = $this->referenceCode();


        if ($bucket->status == 0) {
            return $this->sendError('Bucket not available right now', 'Bucket not available right');
        }

        if (!$this->isDebited($amountActual)) {
            return $this->sendError('Insufficient Balance for this transaction', 'Insufficient Balance for this transaction');
        }

        $wallet_name = $this->getBucketTitle($bucket->id);

        $this->IncrementBucket($wallet_name, $this->user->id, $data_size);

        $prev_wallet_bal = $this->user->wallet;

        $wallet_bal = $this->user->wallet - $amountActual;

        $prev_data_wallet_bal = $this->user->$wallet_name;

        $data_wallet_bal = $this->user->$wallet_name + $data_size;

        // $order_wallet_name_bal = $wallet_name . "_bal";
        // $prev_order_wallet_name_bal = "prev_" . $wallet_name . "_bal";

        $bucket_order = new BucketOrder();
        $bucket_order->ref = $ref;
        // $order->custom_reference = $custom_reference;
        $bucket_order->user_id = $this->user->id;
        $bucket_order->bucket_id = $bucket->id;
        $bucket_order->plan = $bucket->title . " Bucket Purchase of " . $data_size . "GB";
        $bucket_order->amount = $amountActual;
        $bucket_order->quantity = 1;
        $bucket_order->subtotal = $amountActual;
        $bucket_order->total = $amountActual;
        $bucket_order->channel = is_null($request->channel) ? 'Web' : 'App';
        $bucket_order->data_size = $data_size * 1000;
        $bucket_order->bucket_bal = $data_wallet_bal;
        $bucket_order->prev_bucket_bal = $prev_data_wallet_bal;
        $bucket_order->description = $bucket->title . " Bucket Purchase of " . $data_size . "GB";
        $bucket_order->status = 1;
        $bucket_order->save();

        $bucket_title = $bucket->title;
        
        $order = new Order();
        $order->ref = $ref;
        // $order->custom_reference = $custom_reference;
        $order->user_id = $this->user->id;
        $order->subcategory_id = Subcategory::where('title',$bucket_title)->first()->id;
        $order->plan = $bucket->title . " Bucket Purchase of " . $data_size . "GB";
        $order->amount = $amountActual;
        $order->quantity = 1;
        $order->subtotal = $amountActual;
        $order->total = $amountActual;
        $order->channel = is_null($request->channel) ? 'Web' : 'App';
        $order->data_size = $data_size * 1000;
        $order->bal = $wallet_bal;
        $order->prev_bal = $prev_wallet_bal;
        $order->description = $bucket->title . " Bucket Purchase of " . $data_size . "GB";
        $order->status = 1;
        $order->save();



        return $this->sendResponse($ref, $bucket->title . " Bucket Purchase successful");

    }

    public function purchaseData(Request $request)
    {
        $plan = $request->plan_id;
        $data_size = $request->data_size;
        $phone = $this->formatPhoneNumber($request->phonenumber);
        $data_cost = $request->data_cost;
        \Log::info($phone);
        \Log::info($data_cost);
        $ref = $this->referenceCode();
        $custom_reference = $request->custom_reference != null ? $request->custom_reference : null;
        $bucket = Bucket::find($request->bucket_id);
        $wallet_name = $this->getBucketTitle($bucket->id);

        if (!is_null($bucket->description) && $bucket->description == "JONET") {
            $ref = $this->jonetReferenceCode();
        } elseif (!is_null($bucket->description) && $bucket->description == "AUTOPILOT") {
            $ref = $this->autoPilotReferenceCode();
        } elseif (!is_null($bucket->description) && $bucket->description == "AUTOPILOTAWUF") {
            $ref = $this->autoPilotReferenceCode();
        } elseif (!is_null($bucket->description) && $bucket->description == "TBCHPORTAL") {
            $ref = $this->autoPilotReferenceCode();
        } else {
            $ref = $this->referenceCode();
        }
        
        $wallet_status = $wallet_name."_status";

        // Check that bucket transaction is on for this user
        if($this->user->$wallet_status == 0) {
            return $this->sendError($bucket->title .' Bucket purchase denied', $bucket->title . 'Bucket purchase denied');
        }


        $data_cost = floatval($data_cost);

        $productCollection = collect(json_decode($bucket->products));
        $getSelectedProduct = $productCollection->where('plan', $plan)->first();

        $network = explode(' ', $bucket->title)[0];
        \Log::info($phone);
        if (is_null($request->phonenumber)) {
            return $this->sendError('Phone Number is required', 'Phone Number is required');
        }

        if (!$request->ported && !$this->isValidPhoneProvider(strtolower($network), $phone)) {
            return $this->sendError('This is not an ' . $network . ' Phone Number', 'This is not an ' . $network . ' Phone Number');
        }

        if ($bucket->status == 0) {
            return $this->sendError('Product not available right now', 'Product not available right');
        } elseif ($bucket->status == 1 && is_null($bucket->description)) {
            if (!$this->DecrementBucket($wallet_name, $this->user->id, $data_cost)) {
                return $this->sendError('Insufficient Balance for this transaction', 'Insufficient Balance for this transaction');
            }

            $prev_wallet_bal = $this->user->wallet;

            $wallet_bal = $this->user->wallet;

            $prev_data_wallet_bal = $this->user->$wallet_name;

            $data_wallet_bal = $this->user->$wallet_name - $data_cost;

            // $order_wallet_name_bal = $wallet_name . "_bal";
            // $prev_order_wallet_name_bal = "prev_" . $wallet_name . "_bal";


            $order = new BucketOrder();
            $order->ref = $ref;
            $order->custom_reference = $custom_reference;
            $order->user_id = $this->user->id;
            $order->bucket_id = $bucket->id;
            $order->plan = $bucket->title . " " . $plan;
            $order->quantity = 1;
            $order->amount = $data_cost;
            $order->subtotal = $data_cost;
            $order->total = $data_cost;
            $order->channel = is_null($request->channel) ? 'Web' : 'App';
            $order->data_size = $getSelectedProduct->data_size;
            $order->phone = $phone;
            $order->bucket_bal = $data_wallet_bal;
            $order->prev_bucket_bal = $prev_data_wallet_bal;
            $order->description = $bucket->title . " " . $plan;
            $order->status = 1;
            $order->save();

            $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->code, $ref, $bucket->telegram);

            return $this->sendResponse2($ref, $custom_reference, $data_cost, $plan . " Purchase successful", $plan . " Purchase successful");


        } else {

            if (!$this->DecrementBucket($wallet_name, $this->user->id, $data_cost)) {
                return $this->sendError('Insufficient Balance for this transaction', 'Insufficient Balance for this transaction');
            }

            if (!is_null($bucket->description) && $bucket->description == "SMEPLUG") {

                //$this->AirtelEduBuyData();

                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->smeplug_id;

                // Initialization of bucket order
                $prev_wallet_bal = $this->user->wallet;

                $wallet_bal = $this->user->wallet;

                $prev_data_wallet_bal = $this->user->$wallet_name;

                $data_wallet_bal = $this->user->$wallet_name - $data_cost;

                // $order_wallet_name_bal = $wallet_name . "_bal";
                // $prev_order_wallet_name_bal = "prev_" . $wallet_name . "_bal";
                // End of initialization

                $order = new BucketOrder();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->bucket_id = $bucket->id;
                $order->plan = $bucket->title . " " . $plan;
                $order->quantity = 1;
                $order->amount = $data_cost;
                $order->subtotal = $data_cost;
                $order->total = $data_cost;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->phone = $phone;
                $order->bucket_bal = $data_wallet_bal;
                $order->prev_bucket_bal = $prev_data_wallet_bal;
                $order->description = $bucket->title . " " . $plan;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->SMEPlugApi($network_id, $plan_id, $phone);
                } catch (TransferException | ConnectException | ServerException | ClientException | RequestException | HttpClientException | ConnectException $e) {
                    $response = null;
                }



                if (!isset($response) || is_null($response) || !isset($response->status) || is_null($response->status)) {
                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif ($response->status == false) {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->save();

                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->smeplug_id, $ref, $bucket->telegram);


                    return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                } else {

                    if (stripos($response->data->msg, "not successful") > 0) {
                        $updateOrder = BucketOrder::where('ref', $ref)->first();

                        $updateOrder->status = 0;
                        $updateOrder->response = explode(".", $response->data->msg)[0];
                        $updateOrder->save();
                        $msg = $response->data->msg . " SMEPLUG Ref: " . $response->data->reference;

                    } else {
                        $updateOrder = BucketOrder::where('ref', $ref)->first();

                        $updateOrder->status = 1;
                        $updateOrder->response = explode(".", $response->data->msg)[0];
                        $updateOrder->save();
                        $msg = explode(".", $response->data->msg)[0] . " SMEPLUG Ref: " . $response->data->reference;

                    }

                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->smeplug_id, $ref, $bucket->telegram, $msg);


                    return $this->sendResponse2($ref, $custom_reference, $data_cost, $plan . " Purchase successful", $response->data->msg);
                }
            } elseif (!is_null($bucket->description) && $bucket->description == "AIRTIMENIGERIA") {

                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->aa_package_code;
                // Initialization of bucket order
                $prev_wallet_bal = $this->user->wallet;

                $wallet_bal = $this->user->wallet;

                $prev_data_wallet_bal = $this->user->$wallet_name;

                $data_wallet_bal = $this->user->$wallet_name - $data_cost;

                // $order_wallet_name_bal = $wallet_name . "_bal";
                // $prev_order_wallet_name_bal = "prev_" . $wallet_name . "_bal";
                // End of initialization

                $order = new BucketOrder();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->bucket_id = $bucket->id;
                $order->plan = $bucket->title . " " . $plan;
                $order->quantity = 1;
                $order->amount = $data_cost;
                $order->subtotal = $data_cost;
                $order->total = $data_cost;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->phone = $phone;
                $order->bucket_bal = $data_wallet_bal;
                $order->prev_bucket_bal = $prev_data_wallet_bal;
                $order->description = $bucket->title . " " . $plan;
                $order->status = 0;
                $order->save();


                try {
                    $response = $this->purchaseAirtimeNigeria($plan_id, $data_cost, $phone, $ref);
                } catch (TransferException | ConnectException | ServerException | ClientException | RequestException | HttpClientException | ConnectException $e) {
                    $response = null;
                }

                \Log::info("AIRTIME NIGERIA BUCKET ORDER");
                \Log::info(print_r($response, true));

                if (!isset($response) || is_null($response)) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif ($response['details']['order_status'] == "failed" || $response['status'] == 'failed') {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->save();

                    //$this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->aa_package_code, $ref, $bucket->telegram);
                    $resp_msg = "Data is not available, please try again later";


                    return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed", "Transaction failed");
                } else {

                    $updateOrder = BucketOrder::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['details']['gateway_response'];
                    $updateOrder->save();
                    $msg = $response['details']['gateway_response'] . " AIRTIMENIGERIA Ref: " . $response['details']['reference'];


                    //$this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->aa_package_code, $ref, $bucket->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $data_cost, $plan . " Purchase successful", $response['details']['gateway_response']);
                }
            } elseif (!is_null($bucket->description) && $bucket->description == "OGADAM") {

                $network_id = $this->parseOgaDamsNetworkID(strtolower($network));
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->ogadam_id;

                $prev_wallet_bal = $this->user->wallet;

                $wallet_bal = $this->user->wallet;

                $prev_data_wallet_bal = $this->user->$wallet_name;

                $data_wallet_bal = $this->user->$wallet_name - $data_cost;

                // $order_wallet_name_bal = $wallet_name . "_bal";
                // $prev_order_wallet_name_bal = "prev_" . $wallet_name . "_bal";
                // End of initialization

                $order = new BucketOrder();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->bucket_id = $bucket->id;
                $order->plan = $bucket->title . " " . $plan;
                $order->quantity = 1;
                $order->amount = $data_cost;
                $order->subtotal = $data_cost;
                $order->total = $data_cost;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->phone = $phone;
                $order->bucket_bal = $data_wallet_bal;
                $order->prev_bucket_bal = $prev_data_wallet_bal;
                $order->description = $bucket->title . " " . $plan;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->ogaDamBuyData($network_id, $plan_id, $phone, $ref);

                } catch (TransferException | ConnectException | ServerException | ClientException | RequestException | HttpClientException | ConnectException $e) {
                    $response = array("code" => 424, "data" => array("msg" => "System error!"));
                }

                \Log::info("OGADAMS RESPONSE");
                \Log::info(print_r($response, true));

                // if (isset($response) and $response['data']['msg'] == "Unable to establish connection at the moment. Please try again later!. Your new balance is ₦NA.")
                if (!isset($response) || is_null($response)) {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->save();

                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->ogadam_id, $ref, $bucket->telegram);


                    return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");

                } elseif ($response['code'] == 424) {
                    if (!is_null($response['data']) && !is_null($response['data']['msg']) && stripos($response['data']['msg'], "Activation of Corporate_Data_Gifting was not successful") !== false) {

                        $updOrder = BucketOrder::where('ref', $ref)->first();
                        $updOrder->status = 4;
                        $updOrder->save();

                        //refund
                        $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                        $order = new BucketOrder();
                        $order->ref = $ref;
                        $order->custom_reference = $custom_reference;
                        $order->user_id = $this->user->id;
                        $order->bucket_id = $bucket->id;
                        $order->plan = $bucket->title . " " . $plan;
                        $order->quantity = 1;
                        $order->amount = $data_cost;
                        $order->subtotal = $data_cost;
                        $order->total = $data_cost;
                        $order->channel = is_null($request->channel) ? 'Web' : 'App';
                        $order->data_size = $getSelectedProduct->data_size;
                        $order->phone = $phone;
                        $order->bucket_bal = $prev_data_wallet_bal;
                        $order->prev_bucket_bal = $data_wallet_bal;
                        $order->description = $bucket->title . " " . $plan;
                        $order->response = explode(".", $response['data']['msg'])[0];
                        $order->status = 2;
                        $order->save();

                        $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->ogadam_id, $ref, $bucket->telegram);


                        return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                    } elseif (!is_null($response['data']) && !is_null($response['data']['msg']) && stripos($response['data']['msg'], "not successful") > 0) {
                        if ($bucket->title == "MTN SME") {
                            $updOrder = BucketOrder::where('ref', $ref)->first();
                            $updOrder->status = 4;
                            $updOrder->save();

                            //refund
                            $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                            $order = new BucketOrder();
                            $order->ref = $ref;
                            $order->custom_reference = $custom_reference;
                            $order->user_id = $this->user->id;
                            $order->bucket_id = $bucket->id;
                            $order->plan = $bucket->title . " " . $plan;
                            $order->quantity = 1;
                            $order->amount = $data_cost;
                            $order->subtotal = $data_cost;
                            $order->total = $data_cost;
                            $order->channel = is_null($request->channel) ? 'Web' : 'App';
                            $order->data_size = $getSelectedProduct->data_size;
                            $order->phone = $phone;
                            $order->bucket_bal = $prev_data_wallet_bal;
                            $order->prev_bucket_bal = $data_wallet_bal;
                            $order->description = $bucket->title . " " . $plan;
                            $order->response = explode(".", $response['data']['msg'])[0];
                            $order->status = 2;
                            $order->save();

                            $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->ogadam_id, $ref, $bucket->telegram);

                            return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");

                        } else {
                            $updateOrder = BucketOrder::where('ref', $ref)->first();
                            $updateOrder->status = 0;
                            $updateOrder->response = explode(".", $response['data']['msg'])[0];
                            $updateOrder->save();
                            $msg = $response['data']['msg'] . " OGADAM Ref: " . explode(".", $response['data']['msg'])[0];


                            $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->ogadam_id, $ref, $bucket->telegram, $msg);


                            return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                        }


                    } elseif (!is_null($response['data']) && !is_null($response['data']['msg']) && $response['data']['msg'] == "There was an error while processing the request.") {
                        if ($bucket->title == "MTN SME") {
                            $updOrder = BucketOrder::where('ref', $ref)->first();
                            $updOrder->status = 4;
                            $updOrder->save();

                            //refund
                            $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                            $order = new BucketOrder();
                            $order->ref = $ref;
                            $order->custom_reference = $custom_reference;
                            $order->user_id = $this->user->id;
                            $order->bucket_id = $bucket->id;
                            $order->plan = $bucket->title . " " . $plan;
                            $order->quantity = 1;
                            $order->amount = $data_cost;
                            $order->subtotal = $data_cost;
                            $order->total = $data_cost;
                            $order->channel = is_null($request->channel) ? 'Web' : 'App';
                            $order->data_size = $getSelectedProduct->data_size;
                            $order->phone = $phone;
                            $order->bucket_bal = $prev_data_wallet_bal;
                            $order->prev_bucket_bal = $data_wallet_bal;
                            $order->description = $bucket->title . " " . $plan;
                            $order->response = explode(".", $response['data']['msg'])[0];
                            $order->status = 2;
                            $order->save();



                            $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->ogadam_id, $ref, $bucket->telegram);


                            return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                        }

                    } elseif (!is_null($response['data']) && !is_null($response['data']['msg']) && $response['data']['msg'] == "Sorry! The system is temporarily unable to process your request. Please try after sometime") {
                        if ($bucket->title == "MTN SME") {

                            $updOrder = BucketOrder::where('ref', $ref)->first();
                            $updOrder->status = 4;
                            $updOrder->save();

                            //refund
                            $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                            $order = new BucketOrder();
                            $order->ref = $ref;
                            $order->custom_reference = $custom_reference;
                            $order->user_id = $this->user->id;
                            $order->bucket_id = $bucket->id;
                            $order->plan = $bucket->title . " " . $plan;
                            $order->quantity = 1;
                            $order->amount = $data_cost;
                            $order->subtotal = $data_cost;
                            $order->total = $data_cost;
                            $order->channel = is_null($request->channel) ? 'Web' : 'App';
                            $order->data_size = $getSelectedProduct->data_size;
                            $order->phone = $phone;
                            $order->bucket_bal = $prev_data_wallet_bal;
                            $order->prev_bucket_bal = $data_wallet_bal;
                            $order->description = $bucket->title . " " . $plan;
                            $order->response = explode(".", $response['data']['msg'])[0];
                            $order->status = 2;
                            $order->save();



                            $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->ogadam_id, $ref, $bucket->telegram);


                            return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                        }
                    } elseif (!is_null($response['data']) && !is_null($response['data']['msg']) && $response['data']['msg'] == "An error occured. Try again later!") {
                        if ($bucket->title == "MTN SME") {

                            $updOrder = BucketOrder::where('ref', $ref)->first();
                            $updOrder->status = 4;
                            $updOrder->save();

                            //refund
                            $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                            $order = new BucketOrder();
                            $order->ref = $ref;
                            $order->custom_reference = $custom_reference;
                            $order->user_id = $this->user->id;
                            $order->bucket_id = $bucket->id;
                            $order->plan = $bucket->title . " " . $plan;
                            $order->quantity = 1;
                            $order->amount = $data_cost;
                            $order->subtotal = $data_cost;
                            $order->total = $data_cost;
                            $order->channel = is_null($request->channel) ? 'Web' : 'App';
                            $order->data_size = $getSelectedProduct->data_size;
                            $order->phone = $phone;
                            $order->bucket_bal = $prev_data_wallet_bal;
                            $order->prev_bucket_bal = $data_wallet_bal;
                            $order->description = $bucket->title . " " . $plan;
                            $order->response = explode(".", $response['data']['msg'])[0];
                            $order->status = 2;
                            $order->save();



                            $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->ogadam_id, $ref, $bucket->telegram);


                            return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                        }
                    } elseif (!is_null($response['data']) && !is_null($response['data']['msg']) && $response['data']['msg'] == "An error occurred. Try again later!") {
                        if ($bucket->title == "MTN SME") {

                            $updOrder = BucketOrder::where('ref', $ref)->first();
                            $updOrder->status = 4;
                            $updOrder->save();

                            //refund
                            $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                            $order = new BucketOrder();
                            $order->ref = $ref;
                            $order->custom_reference = $custom_reference;
                            $order->user_id = $this->user->id;
                            $order->bucket_id = $bucket->id;
                            $order->plan = $bucket->title . " " . $plan;
                            $order->quantity = 1;
                            $order->amount = $data_cost;
                            $order->subtotal = $data_cost;
                            $order->total = $data_cost;
                            $order->channel = is_null($request->channel) ? 'Web' : 'App';
                            $order->data_size = $getSelectedProduct->data_size;
                            $order->phone = $phone;
                            $order->bucket_bal = $prev_data_wallet_bal;
                            $order->prev_bucket_bal = $data_wallet_bal;
                            $order->description = $bucket->title . " " . $plan;
                            $order->response = explode(".", $response['data']['msg'])[0];
                            $order->status = 2;
                            $order->save();



                            $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->ogadam_id, $ref, $bucket->telegram);


                            return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                        }
                    } else {
                        $updOrder = BucketOrder::where('ref', $ref)->first();
                        $updOrder->status = 4;
                        $updOrder->save();

                        //refund
                        $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                        $order = new BucketOrder();
                        $order->ref = $ref;
                        $order->custom_reference = $custom_reference;
                        $order->user_id = $this->user->id;
                        $order->bucket_id = $bucket->id;
                        $order->plan = $bucket->title . " " . $plan;
                        $order->quantity = 1;
                        $order->amount = $data_cost;
                        $order->subtotal = $data_cost;
                        $order->total = $data_cost;
                        $order->channel = is_null($request->channel) ? 'Web' : 'App';
                        $order->data_size = $getSelectedProduct->data_size;
                        $order->phone = $phone;
                        $order->bucket_bal = $prev_data_wallet_bal;
                        $order->prev_bucket_bal = $data_wallet_bal;
                        $order->description = $bucket->title . " " . $plan;
                        $order->response = explode(".", $response['data']['msg'])[0];
                        $order->status = 2;
                        $order->save();



                        $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->ogadam_id, $ref, $bucket->telegram);
                        $resp_msg = explode(".", $response['data']['msg'])[0];


                        return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed", "Transaction failed");
                    }
                } else {
                    if (!is_null($response['data']) && !is_null($response['data']['msg']) && $response['data']['msg'] == "Activation of Corporate_Data_Gifting was not successful. Please try again.") {

                        $updOrder = BucketOrder::where('ref', $ref)->first();
                        $updOrder->status = 4;
                        $updOrder->save();

                        //refund
                        $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                        $order = new BucketOrder();
                        $order->ref = $ref;
                        $order->custom_reference = $custom_reference;
                        $order->user_id = $this->user->id;
                        $order->bucket_id = $bucket->id;
                        $order->plan = $bucket->title . " " . $plan;
                        $order->quantity = 1;
                        $order->amount = $data_cost;
                        $order->subtotal = $data_cost;
                        $order->total = $data_cost;
                        $order->channel = is_null($request->channel) ? 'Web' : 'App';
                        $order->data_size = $getSelectedProduct->data_size;
                        $order->phone = $phone;
                        $order->bucket_bal = $prev_data_wallet_bal;
                        $order->prev_bucket_bal = $data_wallet_bal;
                        $order->description = $bucket->title . " " . $plan;
                        $order->response = explode(".", $response['data']['msg'])[0];
                        $order->status = 2;
                        $order->save();


                        $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->ogadam_id, $ref, $bucket->telegram);


                        return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                    } elseif (!is_null($response['data']) && !is_null($response['data']['msg']) && stripos($response['data']['msg'], "not successful") > 0) {
                        if ($bucket->title == "MTN SME") {


                            $updOrder = BucketOrder::where('ref', $ref)->first();
                            $updOrder->status = 4;
                            $updOrder->save();

                            //refund
                            $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                            $order = new BucketOrder();
                            $order->ref = $ref;
                            $order->custom_reference = $custom_reference;
                            $order->user_id = $this->user->id;
                            $order->bucket_id = $bucket->id;
                            $order->plan = $bucket->title . " " . $plan;
                            $order->quantity = 1;
                            $order->amount = $data_cost;
                            $order->subtotal = $data_cost;
                            $order->total = $data_cost;
                            $order->channel = is_null($request->channel) ? 'Web' : 'App';
                            $order->data_size = $getSelectedProduct->data_size;
                            $order->phone = $phone;
                            $order->bucket_bal = $prev_data_wallet_bal;
                            $order->prev_bucket_bal = $data_wallet_bal;
                            $order->description = $bucket->title . " " . $plan;
                            $order->response = explode(".", $response['data']['msg'])[0];
                            $order->status = 2;
                            $order->save();


                            $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->ogadam_id, $ref, $bucket->telegram);


                            return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");

                        } else {
                            $updateOrder = BucketOrder::where('ref', $ref)->first();
                            $updateOrder->status = 0;
                            $updateOrder->response = explode(".", $response['data']['msg'])[0];
                            $updateOrder->save();
                            $msg = $response['data']['msg'] . " OGADAM Ref: " . explode(".", $response['data']['msg'])[0];


                            $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->ogadam_id, $ref, $bucket->telegram, $msg);

                            // return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", explode(".",$response['data']['msg'])[0]);
                            return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                        }


                    } elseif (!is_null($response['data']) && !is_null($response['data']['msg']) && $response['data']['msg'] == "There was an error while processing the request.") {
                        if ($bucket->title == "MTN SME") {
                            $updOrder = BucketOrder::where('ref', $ref)->first();
                            $updOrder->status = 4;
                            $updOrder->save();

                            //refund
                            $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                            $order = new BucketOrder();
                            $order->ref = $ref;
                            $order->custom_reference = $custom_reference;
                            $order->user_id = $this->user->id;
                            $order->bucket_id = $bucket->id;
                            $order->plan = $bucket->title . " " . $plan;
                            $order->quantity = 1;
                            $order->amount = $data_cost;
                            $order->subtotal = $data_cost;
                            $order->total = $data_cost;
                            $order->channel = is_null($request->channel) ? 'Web' : 'App';
                            $order->data_size = $getSelectedProduct->data_size;
                            $order->phone = $phone;
                            $order->bucket_bal = $prev_data_wallet_bal;
                            $order->prev_bucket_bal = $data_wallet_bal;
                            $order->description = $bucket->title . " " . $plan;
                            $order->response = explode(".", $response['data']['msg'])[0];
                            $order->status = 2;
                            $order->save();


                            $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->ogadam_id, $ref, $bucket->telegram);


                            return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                        }

                    } elseif (!is_null($response['data']) && !is_null($response['data']['msg']) && $response['data']['msg'] == "An error occured. Try again later!") {
                        if ($bucket->title == "MTN SME") {
                            $updOrder = BucketOrder::where('ref', $ref)->first();
                            $updOrder->status = 4;
                            $updOrder->save();

                            //refund
                            $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                            $order = new BucketOrder();
                            $order->ref = $ref;
                            $order->custom_reference = $custom_reference;
                            $order->user_id = $this->user->id;
                            $order->bucket_id = $bucket->id;
                            $order->plan = $bucket->title . " " . $plan;
                            $order->quantity = 1;
                            $order->amount = $data_cost;
                            $order->subtotal = $data_cost;
                            $order->total = $data_cost;
                            $order->channel = is_null($request->channel) ? 'Web' : 'App';
                            $order->data_size = $getSelectedProduct->data_size;
                            $order->phone = $phone;
                            $order->bucket_bal = $prev_data_wallet_bal;
                            $order->prev_bucket_bal = $data_wallet_bal;
                            $order->description = $bucket->title . " " . $plan;
                            $order->response = explode(".", $response['data']['msg'])[0];
                            $order->status = 2;
                            $order->save();


                            $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->ogadam_id, $ref, $bucket->telegram);


                            return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                        }
                    } elseif (!is_null($response['data']) && !is_null($response['data']['msg']) && $response['data']['msg'] == "An error occurred. Try again later!") {
                        if ($bucket->title == "MTN SME") {
                            $updOrder = BucketOrder::where('ref', $ref)->first();
                            $updOrder->status = 4;
                            $updOrder->save();

                            //refund
                            $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                            $order = new BucketOrder();
                            $order->ref = $ref;
                            $order->custom_reference = $custom_reference;
                            $order->user_id = $this->user->id;
                            $order->bucket_id = $bucket->id;
                            $order->plan = $bucket->title . " " . $plan;
                            $order->quantity = 1;
                            $order->amount = $data_cost;
                            $order->subtotal = $data_cost;
                            $order->total = $data_cost;
                            $order->channel = is_null($request->channel) ? 'Web' : 'App';
                            $order->data_size = $getSelectedProduct->data_size;
                            $order->phone = $phone;
                            $order->bucket_bal = $prev_data_wallet_bal;
                            $order->prev_bucket_bal = $data_wallet_bal;
                            $order->description = $bucket->title . " " . $plan;
                            $order->response = explode(".", $response['data']['msg'])[0];
                            $order->status = 2;
                            $order->save();


                            $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->ogadam_id, $ref, $bucket->telegram);


                            return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                        }
                    } else {

                        $updateOrder = BucketOrder::where('ref', $ref)->first();
                        $updateOrder->status = 1;
                        $updateOrder->response = $response['data']['msg'];
                        $updateOrder->save();
                        $msg = $response['data']['msg'] . " OGADAM Ref: " . $response['data']['msg'];


                        $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->ogadam_id, $ref, $bucket->telegram, $msg);

                        return $this->sendResponse2($ref, $custom_reference, $data_cost, $plan . " Purchase successful", $response['data']['msg']);
                    }


                }
            } elseif (!is_null($bucket->description) && $bucket->description == "AIRTELEDUSITE") {

                $network_id = $this->parseOgaDamsNetworkID(strtolower($network));
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->airtel_id;
                $request_body = $getSelectedProduct->airtel_plan;

                $prev_wallet_bal = $this->user->wallet;

                $wallet_bal = $this->user->wallet;

                $prev_data_wallet_bal = $this->user->$wallet_name;

                $data_wallet_bal = $this->user->$wallet_name - $data_cost;

                // $order_wallet_name_bal = $wallet_name . "_bal";
                // $prev_order_wallet_name_bal = "prev_" . $wallet_name . "_bal";
                // End of initialization

                $order = new BucketOrder();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->bucket_id = $bucket->id;
                $order->plan = $bucket->title . " " . $plan;
                $order->quantity = 1;
                $order->amount = $data_cost;
                $order->subtotal = $data_cost;
                $order->total = $data_cost;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->phone = $phone;
                $order->bucket_bal = $data_wallet_bal;
                $order->prev_bucket_bal = $prev_data_wallet_bal;
                $order->description = $bucket->title . " " . $plan;
                $order->status = 0;
                $order->save();


                try {
                    $response = $this->AirtelEduBuyData($phone, $request_body);
                } catch (TransferException | ConnectException | ServerException | ClientException | RequestException | HttpClientException | ConnectException $e) {
                    $response = null;
                }


                if (isset($response)) {
                    $dstatus = $response['data'][0]['status'];
                    $desc = $response['data'][0]['description'];
                    $new_desc = explode('.', $desc)[0];
                }



                if (!isset($response) || is_null($response) || !isset($dstatus) || is_null($dstatus) || $dstatus == "error" || $desc == "Dear customer, your purchase was not successful. Please try again." || $dstatus == "InsufficientBalance") {
                    \Log::info("it failed Airtel");
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->save();

                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->airtel_id, $ref, $bucket->telegram);


                    return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");

                } elseif ($new_desc == "Invalid character after parsing property name") {
                    \Log::info("it was successful Airtel");


                    $updateOrder = BucketOrder::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $desc;
                    $updateOrder->save();
                    $msg = $desc . " AIRTEL EDUSUITE Ref: " . $desc;


                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->airtel_id, $ref, $bucket->telegram);

                    return $this->sendResponse2($ref, $custom_reference, $data_cost, $plan . " Purchase successful", $desc);

                    // return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } else {
                    \Log::info("it was successful Airtel");


                    $updateOrder = BucketOrder::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $desc;
                    $updateOrder->save();
                    $msg = $desc . " AIRTEL EDUSUITE Ref: " . $desc;


                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->airtel_id, $ref, $bucket->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $data_cost, $plan . " Purchase successful", $desc);
                }
            } elseif (!is_null($bucket->description) && $bucket->description == "AYINLAK") {

                $network_id = $this->ayinlakNetworkID(strtolower($network));
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->ayinlak_id;
                $prev_wallet_bal = $this->user->wallet;

                $wallet_bal = $this->user->wallet;

                $prev_data_wallet_bal = $this->user->$wallet_name;

                $data_wallet_bal = $this->user->$wallet_name - $data_cost;

                // $order_wallet_name_bal = $wallet_name . "_bal";
                // $prev_order_wallet_name_bal = "prev_" . $wallet_name . "_bal";
                // End of initialization

                $order = new BucketOrder();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->bucket_id = $bucket->id;
                $order->plan = $bucket->title . " " . $plan;
                $order->quantity = 1;
                $order->amount = $data_cost;
                $order->subtotal = $data_cost;
                $order->total = $data_cost;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->phone = $phone;
                $order->bucket_bal = $data_wallet_bal;
                $order->prev_bucket_bal = $prev_data_wallet_bal;
                $order->description = $bucket->title . " " . $plan;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->ayinlakconnectData($network_id, $plan_id, $phone);
                    \Log::info("Ayinlak");
                    \Log::info($response);
                } catch (Exception $e) {
                    $response = null;
                }

                if (!isset($response) || is_null($response)) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif ($response['Status'] != 'successful') {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->save();


                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->ayinlak_id, $ref, $bucket->telegram);


                    return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                } elseif (isset($response['error'][0])) {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->save();

                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->ayinlak_id, $ref, $bucket->telegram);

                    $resp_msg = "Data not available at the moment, check back later.";


                    return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed", "Transaction failed");
                } else {

                    $updateOrder = BucketOrder::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['api_response'];
                    $updateOrder->save();
                    $msg = $response['api_response'] . " AYINLAK Ref: " . $response['ident'];


                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->ayinlak_id, $ref, $bucket->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $data_cost, $plan . " Purchase successful", $response['api_response']);
                }
            } elseif (!is_null($bucket->description) && $bucket->description == "JONET") {

                $network_id = $this->parseOgaDamsNetworkID(strtolower($network));
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->jonet_code;
                $prev_wallet_bal = $this->user->wallet;

                $wallet_bal = $this->user->wallet;

                $prev_data_wallet_bal = $this->user->$wallet_name;

                $data_wallet_bal = $this->user->$wallet_name - $data_cost;

                // $order_wallet_name_bal = $wallet_name . "_bal";
                // $prev_order_wallet_name_bal = "prev_" . $wallet_name . "_bal";
                // End of initialization

                $order = new BucketOrder();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->bucket_id = $bucket->id;
                $order->plan = $bucket->title . " " . $plan;
                $order->quantity = 1;
                $order->amount = $data_cost;
                $order->subtotal = $data_cost;
                $order->total = $data_cost;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->phone = $phone;
                $order->bucket_bal = $data_wallet_bal;
                $order->prev_bucket_bal = $prev_data_wallet_bal;
                $order->description = $bucket->title . " " . $plan;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->jonetData($plan_id, $phone, $ref);
                    \Log::info("JONET RESPONSE");
                    \Log::info(print_r($response, true));

                } catch (Exception $e) {
                    $response = null;
                }

                if (!isset($response) || is_null($response) || $response['status'] == "Processing" || $response['responseCode'] == "201" || $response['responseCode'] == "202" || $response['responseCode'] == "203" || $response['responseCode'] == "JO105") {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif ($response['status'] == 'Failed' || $response['responseCode'] == "002" || $response['responseCode'] == "101" || $response['responseCode'] == "110" || $response['responseCode'] == "102" || $response['responseCode'] == "103" || $response['responseCode'] == "104" || $response['responseCode'] == "105" || $response['responseCode'] == "108" || $response['responseCode'] == "109" || $response['responseCode'] == "106" || $response['responseCode'] == "107" || $response['responseCode'] == "JO101" || $response['responseCode'] == "JO102" || $response['responseCode'] == "JO103" || $response['responseCode'] == "JO104" || $response['responseCode'] == "JO106" || $response['responseCode'] == "JO107" || $response['responseCode'] == "JO109" || $response['responseCode'] == "JO110" || $response['responseCode'] == "JO119") {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->save();

                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->jonet_code, $ref, $bucket->telegram);

                    $resp_msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed", "Transaction failed");
                } else {

                    $updateOrder = BucketOrder::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['server_response'];
                    $updateOrder->save();
                    $msg = $response['server_response'] . " Jonet Ref: " . $response['customer_id'];


                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->jonet_code, $ref, $bucket->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $data_cost, $plan . " Purchase successful", $response['server_response']);
                }
            } elseif (!is_null($bucket->description) && $bucket->description == "AUTOPILOT") {

                $network_id = $this->parseAutoPilotNetworkID(strtolower($network));
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->autopilot_code;
                $datatype = $getSelectedProduct->autopilot_datatype;
                $prev_wallet_bal = $this->user->wallet;

                $wallet_bal = $this->user->wallet;

                $prev_data_wallet_bal = $this->user->$wallet_name;

                $data_wallet_bal = $this->user->$wallet_name - $data_cost;

                // $order_wallet_name_bal = $wallet_name . "_bal";
                // $prev_order_wallet_name_bal = "prev_" . $wallet_name . "_bal";
                // End of initialization

                $order = new BucketOrder();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->bucket_id = $bucket->id;
                $order->plan = $bucket->title . " " . $plan;
                $order->quantity = 1;
                $order->amount = $data_cost;
                $order->subtotal = $data_cost;
                $order->total = $data_cost;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->phone = $phone;
                $order->bucket_bal = $data_wallet_bal;
                $order->prev_bucket_bal = $prev_data_wallet_bal;
                $order->description = $bucket->title . " " . $plan;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->autoPilotData($network_id, $datatype, $plan_id, $phone, $ref);
                    \Log::info("AUTOPILOT RESPONSE");
                    \Log::info(print_r($response, true));

                } catch (TransferException | ConnectException | ServerException | ClientException | RequestException | HttpClientException | ConnectException $e) {
                    $response = array("code" => 200, "data" => array("msg" => "System error!"));
                }

                if (!isset($response) || is_null($response) || $response['code'] == 201 || $response['code'] == 500) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif ($response['code'] == 424) {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->response = $response['data']['message'];
                    $order->status = 2;
                    $order->save();

                    $msg = $response['data']['message'];
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->autopilot_code, $ref, $bucket->telegram, $msg);

                    $resp_msg = "Data is not available,please try again later";



                    return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } else {


                    $updateOrder = BucketOrder::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['data']['message'];
                    $updateOrder->save();
                    $msg = $response['data']['message'] . " Autopilot Ref: " . $response['data']['reference'];


                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->autopilot_code, $ref, $bucket->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $data_cost, $plan . " Purchase successful", $response['data']['message']);
                }
            } elseif (!is_null($bucket->description) && $bucket->description == "AUTOPILOTAWUF") {

                $network_id = $this->parseAutoPilotNetworkID(strtolower($network));
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->autopilot_code;
                $datatype = $getSelectedProduct->autopilot_datatype;
                $prev_wallet_bal = $this->user->wallet;

                $wallet_bal = $this->user->wallet;

                $prev_data_wallet_bal = $this->user->$wallet_name;

                $data_wallet_bal = $this->user->$wallet_name - $data_cost;

                // $order_wallet_name_bal = $wallet_name . "_bal";
                // $prev_order_wallet_name_bal = "prev_" . $wallet_name . "_bal";
                // End of initialization

                $order = new BucketOrder();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->bucket_id = $bucket->id;
                $order->plan = $bucket->title . " " . $plan;
                $order->quantity = 1;
                $order->amount = $data_cost;
                $order->subtotal = $data_cost;
                $order->total = $data_cost;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->phone = $phone;
                $order->bucket_bal = $data_wallet_bal;
                $order->prev_bucket_bal = $prev_data_wallet_bal;
                $order->description = $bucket->title . " " . $plan;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->autoPilotAwuf($network_id, $datatype, $plan_id, $phone, $ref);
                    \Log::info("AUTOPILOTAwuf RESPONSE");
                    \Log::info(print_r($response, true));

                } catch (TransferException | ConnectException | ServerException | ClientException | RequestException | HttpClientException | ConnectException $e) {
                    $response = null;
                }

                if (!isset($response) || is_null($response) || $response['code'] == 201 || $response['code'] == 500) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif ($response['code'] == 424) {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->response = $response['data']['message'];
                    $order->status = 2;
                    $order->save();

                    $msg = $response['data']['message'];
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->autopilot_code, $ref, $bucket->telegram, $msg);

                    $resp_msg = "Data is not available,please try again later";


                    if ($response['data']['message'] == "You may have exhaust all sender number limit") {
                        Bucket::find(48)->update(['status' => 0]);
                    }




                    return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
                } else {


                    $updateOrder = BucketOrder::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['data']['message'];
                    $updateOrder->save();

                    $msg1 = $response['data']['message'];

                    $msg = $msg1 . " Autopilot Ref: " . $response['data']['reference'];


                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->autopilot_code, $ref, $bucket->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $data_cost, $plan . " Purchase successful", $msg1);
                }
            } elseif (!is_null($bucket->description) && $bucket->description == "AUTOSYNCAWUF") {

                $network_id = $this->parseAutoPilotNetworkID(strtolower($network));
                // $plan_id = $getSelectedProduct->code;
                $product_id = $getSelectedProduct->product_id;
                $variation_code = $getSelectedProduct->variation_code;
                $pin = $getSelectedProduct->pin;
                $prev_wallet_bal = $this->user->wallet;

                $wallet_bal = $this->user->wallet;

                $prev_data_wallet_bal = $this->user->$wallet_name;

                $data_wallet_bal = $this->user->$wallet_name - $data_cost;

                // $order_wallet_name_bal = $wallet_name . "_bal";
                // $prev_order_wallet_name_bal = "prev_" . $wallet_name . "_bal";
                // End of initialization

                $order = new BucketOrder();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->bucket_id = $bucket->id;
                $order->plan = $bucket->title . " " . $plan;
                $order->quantity = 1;
                $order->amount = $data_cost;
                $order->subtotal = $data_cost;
                $order->total = $data_cost;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->phone = $phone;
                $order->bucket_bal = $data_wallet_bal;
                $order->prev_bucket_bal = $prev_data_wallet_bal;
                $order->description = $bucket->title . " " . $plan;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->autoSyncPortal($ref, $phone, $product_id, $variation_code, $pin);

                } catch (Exception $e) {
                    $response = null;

                }


                \Log::info("AUTOSYNCAwuf RESPONSE");
                \Log::info(print_r($response, true));

                if (!isset($response) || is_null($response)) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif (isset($response['data']) && isset($response['data']['transaction']) && $response['data']['transaction']['status'] == "pending") {
                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
                } elseif (isset($response['status']) && $response['status'] == "error") {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->response = $response['data']['message'];
                    $order->status = 2;
                    $order->save();

                    $msg = $response['message'];
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->variation_code, $ref, $bucket->telegram, $msg);

                    $resp_msg = "Data is not available,please try again later";

                    //   if($response['data']['message'] == "You may have exhaust all sender number limit"){
                    //         Subcategory::find(48)->update(['status' => 0]);
                    //   }



                    return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
                } elseif (isset($response['data']) && isset($response['data']['transaction']) && $response['data']['transaction']['status'] == "failed") {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->response = $response['data']['message'];
                    $order->status = 2;
                    $order->save();

                    // $msg = $response['data']['transaction']['details'];
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->variation_code, $ref, $bucket->telegram);

                    $resp_msg = "Data is not available,please try again later";

                    //   if($response['data']['message'] == "You may have exhaust all sender number limit"){
                    //         Subcategory::find(48)->update(['status' => 0]);
                    //   }



                    return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed: " . $resp_msg, "Transaction failed: " . $resp_msg);
                } else {


                    $updateOrder = BucketOrder::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['data']['transaction']['details'];
                    $updateOrder->save();
                    $msg1 = $response['data']['transaction']['details'];
                    $msg = $msg1 . " AutoSync Ref: " . $response['data']['transaction']['reference'];


                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->variation_code, $ref, $bucket->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $data_cost, $plan . " Purchase successful", $msg1);
                }
            } elseif (!is_null($bucket->description) && $bucket->description == "TBCHPORTAL") {

                $network_id = $this->parseTBCHPortalID(strtolower($network));
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->tbch_code;
                $prev_wallet_bal = $this->user->wallet;

                $wallet_bal = $this->user->wallet;

                $prev_data_wallet_bal = $this->user->$wallet_name;

                $data_wallet_bal = $this->user->$wallet_name - $data_cost;

                // $order_wallet_name_bal = $wallet_name . "_bal";
                // $prev_order_wallet_name_bal = "prev_" . $wallet_name . "_bal";
                // End of initialization

                $order = new BucketOrder();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->bucket_id = $bucket->id;
                $order->plan = $bucket->title . " " . $plan;
                $order->quantity = 1;
                $order->amount = $data_cost;
                $order->subtotal = $data_cost;
                $order->total = $data_cost;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->phone = $phone;
                $order->bucket_bal = $data_wallet_bal;
                $order->prev_bucket_bal = $prev_data_wallet_bal;
                $order->description = $bucket->title . " " . $plan;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->tbchPortal($network_id, $plan_id, $phone, $ref);

                } catch (TransferException | ConnectException | ServerException | ClientException | RequestException | HttpClientException | ConnectException $e) {
                    $response = null;
                }

                \Log::info("TBCH PORTAL FROM BUCKET RESPONSE");
                \Log::info(print_r($response, true));

                if ($response['code'] == 109 || $response['code'] == 110 || $response['code'] == 111 || $response['code'] == 112 || $response['code'] == 113 || $response['code'] == 114 || $response['code'] == 115 || $response['code'] == 116 || $response['code'] == 120 || $response['code'] == 121 || $response['code'] == 122 || $response['code'] == 310 || $response['code'] == 311 || $response['code'] == 401 || $response['code'] == 402 || $response['code'] == 601) {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->response = $response['message'];
                    $order->created_at = Carbon::now()->addMinutes(1);
                    $order->status = 2;
                    $order->save();


                    $msg = $response['message'];
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->tbch_code, $ref, $bucket->telegram, $msg);

                    $resp_msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (!isset($response) || is_null($response) || $response['data']['status'] == "Pending" || $response['data']['status'] == "Processing") {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif ($response['code'] == "00" && $response['data']['status'] == "Successful") {


                    $updateOrder = BucketOrder::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['data']['gateway_response'];
                    $updateOrder->save();
                    $msg = $response['data']['gateway_response'] . " TbchPortal Ref: " . $response['data']['customer_ref'];


                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->tbch_code, $ref, $bucket->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $data_cost, $plan . " Purchase successful", $response['data']['gateway_response']);
                }
            } elseif (!is_null($bucket->description) && $bucket->description == "TOPUPACCESS") {

                // $network_id = $getSelectedProduct->subarena_network_id;
                // $plan_id = $getSelectedProduct->code;
                $topup_access_pin = $getSelectedProduct->topup_access_pin;
                $plan_id = $getSelectedProduct->topup_access_id;

                $prev_wallet_bal = $this->user->wallet;

                $wallet_bal = $this->user->wallet;

                $prev_data_wallet_bal = $this->user->$wallet_name;

                $data_wallet_bal = $this->user->$wallet_name - $data_cost;

                // $order_wallet_name_bal = $wallet_name . "_bal";
                // $prev_order_wallet_name_bal = "prev_" . $wallet_name . "_bal";
                // End of initialization

                $order = new BucketOrder();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->bucket_id = $bucket->id;
                $order->plan = $bucket->title . " " . $plan;
                $order->quantity = 1;
                $order->amount = $data_cost;
                $order->subtotal = $data_cost;
                $order->total = $data_cost;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->phone = $phone;
                $order->bucket_bal = $data_wallet_bal;
                $order->prev_bucket_bal = $prev_data_wallet_bal;
                $order->description = $bucket->title . " " . $plan;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->topupAccessPortal($plan_id, $topup_access_pin, $phone);

                } catch (Exception $e) {
                    $response = null;
                }

                \Log::info("TOPUP ACCESS PORTAL RESPONSE");
                \Log::info($response);

                if (isset($response) && isset($response['status']) && $response['code'] != 200 && $response['status'] == "failed") {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->save();

                    // $msg = $response['data']['gateway_response'];
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->topup_access_id, $ref, $bucket->telegram);

                    $msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response) && isset($response['status']) && $response['code'] == 200 && $response['status'] == "reversed") {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->response = $response['remark'];
                    $order->status = 2;
                    $order->save();
                    $msg = $response['remark'];
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->topup_access_id, $ref, $bucket->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (!isset($response) || is_null($response)) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif (isset($response) && $response['code'] == 200 && $response['status'] == "success") {


                    $updateOrder = BucketOrder::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['description'];
                    $updateOrder->save();
                    $msg = $response['description'] . " TopupAccess Ref: " . $response['reference'];


                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->topup_access_id, $ref, $bucket->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $data_cost, $plan . " Purchase successful", $response['description']);
                }
            } elseif (!is_null($bucket->description) && $bucket->description == "MTNAPP") {

                // $network_id = $getSelectedProduct->subarena_network_id;
                // $plan_id = $getSelectedProduct->code;
                $pin = $getSelectedProduct->mtn_pin;
                $share_id = $getSelectedProduct->mtn_share_id;

                $prev_wallet_bal = $this->user->wallet;

                $wallet_bal = $this->user->wallet;

                $prev_data_wallet_bal = $this->user->$wallet_name;

                $data_wallet_bal = $this->user->$wallet_name - $data_cost;

                // $order_wallet_name_bal = $wallet_name . "_bal";
                // $prev_order_wallet_name_bal = "prev_" . $wallet_name . "_bal";
                // End of initialization

                $order = new BucketOrder();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->bucket_id = $bucket->id;
                $order->plan = $bucket->title . " " . $plan;
                $order->quantity = 1;
                $order->amount = $data_cost;
                $order->subtotal = $data_cost;
                $order->total = $data_cost;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->phone = $phone;
                $order->bucket_bal = $data_wallet_bal;
                $order->prev_bucket_bal = $prev_data_wallet_bal;
                $order->description = $bucket->title . " " . $plan;
                $order->status = 0;
                $order->save();


                try {
                    $response = $this->buyDataSmeApp($phone, $share_id, $pin);

                } catch (Exception $e) {
                    $response = null;
                }

                \Log::info("MTN PORTAL APP RESPONSE");
                \Log::info($response);
                \Log::info($request);
                \Log::info($data_size);
                \Log::info($data_cost);

                if (!isset($response) || is_null($response)) {

                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->save();

                    $msg = '';
                    //$this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->mtn_share_id, $ref, $bucket->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['message']) && $response['message'] == "Session Not Found" && $response['status'] == "1112") {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->response = !is_null($response['message']) ? $response['message'] : '';
                    $order->status = 2;
                    $order->save();

                    $msg = !is_null($response['message']) ? $response['message'] : '';
                    //$this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->mtn_share_id, $ref, $bucket->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['message']) && $response['message'] == "Valid session" && $response['status'] == "1112") {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->response = !is_null($response['message']) ? $response['message'] : '';
                    $order->status = 2;
                    $order->save();

                    $msg = !is_null($response['message']) ? $response['message'] : '';
                    //$this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->mtn_share_id, $ref, $bucket->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);


                } elseif (isset($response) && !is_array($response)) {
                    if (stripos($response, 'activation') !== false || stripos($response, "do not have an active") !== false || stripos($response, "purchase a data share bundle") !== false || stripos($response, "temporarily unable to process your request") !== false || stripos($response, "issue with the token") !== false || stripos($response, "internal processing error") !== false || stripos($response, "was not successful") !== false || stripos($response, "invalid share pin") !== false || stripos($response, "no healthy upstream") !== false || stripos($response, "error while processing the request") !== false || stripos($response, "not Active on CLM") !== false || stripos($response, "unable to process your request") !== false || stripos($response, "internal server error") !== false || stripos($response, "Datashare is insufficient") !== false || stripos($response, "upstream connect error") !== false || stripos($response, "Connection refused") !== false || stripos($response, "connect timeout") !== false || stripos($response, "Empty response from server") !== false || stripos($response, "unknown error occurred") !== false || stripos($response, "barred due to") !== false || stripos($response, "you are not sending to valid") !== false || stripos($response, "you don't have sufficient") !== false || stripos($response, "connect timeout") !== false || stripos($response, "failed to connect") !== false || stripos($response, "other technical") !== false) {

                        $updOrder = BucketOrder::where('ref', $ref)->first();
                        $updOrder->status = 4;
                        $updOrder->save();

                        //refund
                        $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                        $order = new BucketOrder();
                        $order->ref = $ref;
                        $order->custom_reference = $custom_reference;
                        $order->user_id = $this->user->id;
                        $order->bucket_id = $bucket->id;
                        $order->plan = $bucket->title . " " . $plan;
                        $order->quantity = 1;
                        $order->amount = $data_cost;
                        $order->subtotal = $data_cost;
                        $order->total = $data_cost;
                        $order->channel = is_null($request->channel) ? 'Web' : 'App';
                        $order->data_size = $getSelectedProduct->data_size;
                        $order->phone = $phone;
                        $order->bucket_bal = $prev_data_wallet_bal;
                        $order->prev_bucket_bal = $data_wallet_bal;
                        $order->description = $bucket->title . " " . $plan;
                        $order->response = !is_null($response) ? $response : '';
                        $order->status = 2;
                        $order->save();

                        $msg = !is_null($response) ? $response : '';
                      //  $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $share_id, $ref, $bucket->telegram, $msg);

                        //$msg = "Data is not available,please try again later";


                        return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
                    }

                } elseif (isset($response['message'])) {
                    if (stripos($response['message'], 'activation') !== false || stripos($response['message'], "do not have an active") !== false || stripos($response['message'], "purchase a data share bundle") !== false || stripos($response['message'], "temporarily unable to process your request") !== false || stripos($response['message'], "issue with the token") !== false || stripos($response['message'], "internal processing error") !== false || stripos($response['message'], "was not successful") !== false || stripos($response['message'], "invalid share pin") !== false || stripos($response['message'], "no healthy upstream") !== false || stripos($response['message'], "error while processing the request") !== false || stripos($response['message'], "not Active on CLM") !== false || stripos($response['message'], "unable to process your request") !== false || stripos($response['message'], "internal server error") !== false || stripos($response['message'], "Datashare is insufficient") !== false || stripos($response['message'], "upstream connect error") !== false || stripos($response['message'], "Connection refused") !== false || stripos($response['message'], "connect timeout") !== false || stripos($response['message'], "Empty response from server") !== false || stripos($response['message'], "unknown error occurred") !== false || stripos($response['message'], "barred") !== false || stripos($response['message'], "exception occured") !== false || stripos($response['message'], "meant to happen") !== false || stripos($response['message'], "Other Technical Error") !== false || stripos($response['message'], "phone number field is not blank") !== false) {
                        $updOrder = BucketOrder::where('ref', $ref)->first();
                        $updOrder->status = 4;
                        $updOrder->save();

                        //refund
                        $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                        $order = new BucketOrder();
                        $order->ref = $ref;
                        $order->custom_reference = $custom_reference;
                        $order->user_id = $this->user->id;
                        $order->bucket_id = $bucket->id;
                        $order->plan = $bucket->title . " " . $plan;
                        $order->quantity = 1;
                        $order->amount = $data_cost;
                        $order->subtotal = $data_cost;
                        $order->total = $data_cost;
                        $order->channel = is_null($request->channel) ? 'Web' : 'App';
                        $order->data_size = $getSelectedProduct->data_size;
                        $order->phone = $phone;
                        $order->bucket_bal = $prev_data_wallet_bal;
                        $order->prev_bucket_bal = $data_wallet_bal;
                        $order->description = $bucket->title . " " . $plan;
                        $order->status = 2;
                        $order->save();

                        $msg = !is_null($response['message']) ? $response['message'] : '';
                        //$this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->mtn_share_id, $ref, $bucket->telegram, $msg);

                        // $msg = "Data is not available,please try again later";


                        return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
                    }

                } elseif (isset($response['message']) && stripos($response['message'], 'Other Technical Error') !== false) {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->save();

                    $msg = !is_null($response['message']) ? $response['message'] : '';
                    //$this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->mtn_share_id, $ref, $bucket->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);


                } elseif (isset($response['message']) && stripos($response['message'], 'phone number field is not blank') !== false) {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->response = !is_null($response['message']) ? "recipient's number is barred due to NIN. Ask the recipient to link number at https://nin.mtn.ng/nin/ or visit any MTN store for assistance." : '';
                    $order->save();

                    $msg = !is_null($response['message']) ? "recipient's number is barred due to NIN. Ask the recipient to link number at https://nin.mtn.ng/nin/ or visit any MTN store for assistance." : '';
                //    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->mtn_share_id, $ref, $bucket->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['status']) && $response['status'] == "1112") {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->response = !is_null($response['message']) ? $response['message'] : '';
                    $order->save();

                    $msg = !is_null($response['message']) ? $response['message'] : '';
                  //  $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->mtn_share_id, $ref, $bucket->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['status']) && $response['status'] == "3016") {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->response = !is_null($response['message']) ? $response['message'] : '';
                    $order->save();

                    $msg = !is_null($response['message']) ? $response['message'] : '';
                    //$this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->mtn_share_id, $ref, $bucket->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['status']) && $response['status'] == "3001") {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->response = !is_null($response['message']) ? $response['message'] : '';
                    $order->save();

                    $msg = !is_null($response['message']) ? $response['message'] : '';
                    //$this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->mtn_share_id, $ref, $bucket->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['statusCode']) && $response['statusCode'] == '0000') {


                    $updateOrder = BucketOrder::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['data']['notification'];
                    $updateOrder->save();
                    $msg = $response['data']['notification'] . " MTNNG Ref: " . $response['transactionId'] . " SWIFTLINK REF: " . $ref;


                    //$this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->mtn_share_id, $ref, $bucket->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $data_cost, $plan . " Purchase successful", $response['data']['notification']);
                }
            } 
            
            elseif (!is_null($bucket->description) && $bucket->description == "MTNDIRECT") {

                // $network_id = $getSelectedProduct->subarena_network_id;
                // $plan_id = $getSelectedProduct->code;
                $product_id = $getSelectedProduct->product_id;

                $prev_wallet_bal = $this->user->wallet;

                $wallet_bal = $this->user->wallet;

                $prev_data_wallet_bal = $this->user->$wallet_name;

                $data_wallet_bal = $this->user->$wallet_name - $data_cost;

                // $order_wallet_name_bal = $wallet_name . "_bal";
                // $prev_order_wallet_name_bal = "prev_" . $wallet_name . "_bal";
                // End of initialization

                $order = new BucketOrder();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->bucket_id = $bucket->id;
                $order->plan = $bucket->title . " " . $plan;
                $order->quantity = 1;
                $order->amount = $data_cost;
                $order->subtotal = $data_cost;
                $order->total = $data_cost;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->phone = $phone;
                $order->bucket_bal = $data_wallet_bal;
                $order->prev_bucket_bal = $prev_data_wallet_bal;
                $order->description = $bucket->title . " " . $plan;
                $order->status = 0;
                $order->save();


                try {
                    $response = $this->buyDirectApp($phone, $product_id);

                } catch (Exception $e) {
                    $response = null;
                }

                \Log::info("MTN PORTAL DIRECT APP RESPONSE");
                \Log::info($response);
                \Log::info($request);
                \Log::info($data_size);
                \Log::info($data_cost);

                if (!isset($response) || is_null($response)) {

                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->save();

                    $msg = '';
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->product_id, $ref, $bucket->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['message']) && $response['message'] == "Session Not Found" && $response['statusCode'] == "1112") {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->response = !is_null($response['error']) ? $response['error'] : '';
                    $order->status = 2;
                    $order->save();

                    $msg = !is_null($response['error']) ? $response['error'] : '';
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->product_id, $ref, $bucket->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['message']) && $response['message'] == "Valid session" && $response['statusCode'] == "1112") {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->response = !is_null($response['error']) ? $response['error'] : '';
                    $order->status = 2;
                    $order->save();

                    $msg = !is_null($response['error']) ? $response['error'] : '';
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->product_id, $ref, $bucket->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);


                } elseif (isset($response['message']) && stripos($response['message'], 'Other Technical Error') !== false) {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->save();

                    $msg = !is_null($response['error']) ? $response['error'] : '';
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->product_id, $ref, $bucket->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);


                } elseif (isset($response['message']) && stripos($response['message'], 'phone number field is not blank') !== false) {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->response = !is_null($response['error']) ? "recipient's number is barred due to NIN. Ask the recipient to link number at https://nin.mtn.ng/nin/ or visit any MTN store for assistance." : '';
                    $order->save();

                    $msg = !is_null($response['error']) ? "recipient's number is barred due to NIN. Ask the recipient to link number at https://nin.mtn.ng/nin/ or visit any MTN store for assistance." : '';
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->product_id, $ref, $bucket->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['statusCode']) && $response['statusCode'] == "1112") {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->response = !is_null($response['error']) ? $response['error'] : '';
                    $order->save();

                    $msg = !is_null($response['error']) ? $response['error'] : '';
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->product_id, $ref, $bucket->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['statusCode']) && $response['statusCode'] == "3016") {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->response = !is_null($response['error']) ? $response['error'] : '';
                    $order->save();

                    $msg = !is_null($response['error']) ? $response['error'] : '';
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->product_id, $ref, $bucket->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['statusCode']) && $response['statusCode'] == "3001") {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->response = !is_null($response['error']) ? $response['error'] : '';
                    $order->save();

                    $msg = !is_null($response['error']) ? $response['error'] : '';
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->product_id, $ref, $bucket->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['statusCode']) && $response['statusCode'] == "6001") {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->response = !is_null($response['error']) ? $response['error'] : '';
                    $order->save();

                    $msg = !is_null($response['error']) ? $response['error'] : '';
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->product_id, $ref, $bucket->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['statusCode']) && $response['statusCode'] == '0000') {


                    $updateOrder = BucketOrder::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['subscriptionDescription'];
                    $updateOrder->save();
                    $msg = $response['subscriptionDescription'] . " MTNNG Ref: " . $response['transactionId'] . " SWIFTLINK REF: " . $ref;


                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->mtn_share_id, $ref, $bucket->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $data_cost, $plan . " Purchase successful", $response['subscriptionDescription']);
                }
            }
            
            elseif (!is_null($bucket->description) && $bucket->description == "MTNCG") {

                $share_data = $getSelectedProduct->data_size;

                $prev_wallet_bal = $this->user->wallet;

                $wallet_bal = $this->user->wallet;

                $prev_data_wallet_bal = $this->user->$wallet_name;

                $data_wallet_bal = $this->user->$wallet_name - $data_cost;

                // $order_wallet_name_bal = $wallet_name . "_bal";
                // $prev_order_wallet_name_bal = "prev_" . $wallet_name . "_bal";
                // End of initialization

                $order = new BucketOrder();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->bucket_id = $bucket->id;
                $order->plan = $bucket->title . " " . $plan;
                $order->quantity = 1;
                $order->amount = $data_cost;
                $order->subtotal = $data_cost;
                $order->total = $data_cost;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->phone = $phone;
                $order->bucket_bal = $data_wallet_bal;
                $order->prev_bucket_bal = $prev_data_wallet_bal;
                $order->description = $bucket->title . " " . $plan;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->buyCG($phone, $share_data);

                } catch (Exception $e) {
                    $response = null;
                }

                \Log::info("MTN PORTAL CG RESPONSE");
                \Log::info($response);

                if (isset($response['msg']) || is_null($response)) {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->response = "Sorry an error occurred, kindly try again.";
                    $order->save();

                    $msg = "Sorry an error occurred, kindly try again.";
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $share_data, $ref, $bucket->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response) && isset($response['message']) && $response['message'] == "Unauthorized") {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->response = "Sorry an error occurred, kindly try again.";
                    $order->save();

                    $msg = "Sorry an error occurred, kindly try again.";
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $share_data, $ref, $bucket->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response) && !is_array($response)) {
                    if (stripos($response, 'activation') !== false || stripos($response, "do not have an active") !== false || stripos($response, "purchase a data share bundle") !== false || stripos($response, "temporarily unable to process your request") !== false || stripos($response, "issue with the token") !== false || stripos($response, "internal processing error") !== false || stripos($response, "was not successful") !== false || stripos($response, "invalid share pin") !== false || stripos($response, "no healthy upstream") !== false || stripos($response, "error while processing the request") !== false || stripos($response, "not Active on CLM") !== false || stripos($response, "unable to process your request") !== false || stripos($response, "internal server error") !== false || stripos($response, "Datashare is insufficient") !== false || stripos($response, "upstream connect error") !== false || stripos($response, "Connection refused") !== false || stripos($response, "connect timeout") !== false || stripos($response, "Empty response from server") !== false || stripos($response, "unknown error occurred") !== false || stripos($response, "barred due to") !== false || stripos($response, "you are not sending to valid") !== false || stripos($response, "you don't have sufficient") !== false || stripos($response, "connect timeout") !== false || stripos($response, "failed to connect") !== false || stripos($response, "other technical") !== false) {
                        $updOrder = BucketOrder::where('ref', $ref)->first();
                        $updOrder->status = 4;
                        $updOrder->save();

                        //refund
                        $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                        $order = new BucketOrder();
                        $order->ref = $ref;
                        $order->custom_reference = $custom_reference;
                        $order->user_id = $this->user->id;
                        $order->bucket_id = $bucket->id;
                        $order->plan = $bucket->title . " " . $plan;
                        $order->quantity = 1;
                        $order->amount = $data_cost;
                        $order->subtotal = $data_cost;
                        $order->total = $data_cost;
                        $order->channel = is_null($request->channel) ? 'Web' : 'App';
                        $order->data_size = $getSelectedProduct->data_size;
                        $order->phone = $phone;
                        $order->bucket_bal = $prev_data_wallet_bal;
                        $order->prev_bucket_bal = $data_wallet_bal;
                        $order->description = $bucket->title . " " . $plan;
                        $order->status = 2;
                        $order->response = !is_null($response) ? $response : '';
                        $order->save();

                        $msg = !is_null($response) ? $response : '';
                        $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $share_data, $ref, $bucket->telegram, $msg);

                        //$msg = "Data is not available,please try again later";


                        return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
                    }

                } elseif (isset($response[0]['otherDetails']) && stripos($response[0]['otherDetails'], 'activation') !== false || stripos($response[0]['otherDetails'], "do not have an active") !== false || stripos($response[0]['otherDetails'], "purchase a data share bundle") !== false || stripos($response[0]['otherDetails'], "temporarily unable to process your request") !== false || stripos($response[0]['otherDetails'], "issue with the token") !== false || stripos($response[0]['otherDetails'], "internal processing error") !== false || stripos($response[0]['otherDetails'], "was not successful") !== false || stripos($response[0]['otherDetails'], "invalid share pin") !== false || stripos($response[0]['otherDetails'], "no healthy upstream") !== false || stripos($response[0]['otherDetails'], "error while processing the request") !== false || stripos($response[0]['otherDetails'], "not Active on CLM") !== false || stripos($response[0]['otherDetails'], "unable to process your request") !== false || stripos($response[0]['otherDetails'], "internal server error") !== false || stripos($response[0]['otherDetails'], "Datashare is insufficient") !== false || stripos($response[0]['otherDetails'], "upstream connect error") !== false || stripos($response[0]['otherDetails'], "Connection refused") !== false || stripos($response[0]['otherDetails'], "connect timeout") !== false || stripos($response[0]['otherDetails'], "Empty response from server") !== false || stripos($response[0]['otherDetails'], "unknown error occurred") !== false || stripos($response[0]['otherDetails'], "barred due to") !== false || stripos($response[0]['otherDetails'], "you are not sending to valid") !== false || stripos($response[0]['otherDetails'], "you don't have sufficient") !== false || stripos($response[0]['otherDetails'], "connect timeout") !== false || stripos($response[0]['otherDetails'], "failed to connect") !== false) {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->response = !is_null($response[0]['otherDetails']) ? $response[0]['otherDetails'] : '';
                    $order->save();

                    $msg = !is_null($response[0]['otherDetails']) ? $response[0]['otherDetails'] : '';
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $share_data, $ref, $bucket->telegram, $msg);

                    // $msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (stripos($response[0]['otherDetails'], 'other technical') !== false) {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->response = !is_null($response[0]['otherDetails']) ? $response[0]['otherDetails'] : '';
                    $order->save();

                    $msg = !is_null($response[0]['otherDetails']) ? $response[0]['otherDetails'] : '';
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $share_data, $ref, $bucket->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response[0]['subscriptionStatus']) && $response[0]['subscriptionStatus'] == "Failed") {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->response = !is_null($response[0]['otherDetails']) ? $response[0]['otherDetails'] : '';
                    $order->save();

                    $msg = !is_null($response[0]['otherDetails']) ? $response[0]['otherDetails'] : '';
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $share_data, $ref, $bucket->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (!isset($response) || is_null($response)) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif (isset($response[0]['subscriptionStatus']) && $response[0]['subscriptionStatus'] === 'Success' && $response[0]['status'] == '0000') {

                    $updateOrder = BucketOrder::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response[0]['otherDetails'];
                    $updateOrder->save();
                    $msg = $response[0]['otherDetails'] . " SWIFTLINK REF: " . $ref;


                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $share_data, $ref, $bucket->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $data_cost, $plan . " Purchase successful", $response[0]['otherDetails']);
                }
            } elseif (!is_null($bucket->description) && $bucket->description == "ZOEPORTAL") {

                $network_id = $getSelectedProduct->zoe_network_id;
                $plan_id = $getSelectedProduct->zoe_plan_id;
                $prev_wallet_bal = $this->user->wallet;

                $wallet_bal = $this->user->wallet;

                $prev_data_wallet_bal = $this->user->$wallet_name;

                $data_wallet_bal = $this->user->$wallet_name - $data_cost;

                // $order_wallet_name_bal = $wallet_name . "_bal";
                // $prev_order_wallet_name_bal = "prev_" . $wallet_name . "_bal";
                // End of initialization

                $order = new BucketOrder();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->bucket_id = $bucket->id;
                $order->plan = $bucket->title . " " . $plan;
                $order->quantity = 1;
                $order->amount = $data_cost;
                $order->subtotal = $data_cost;
                $order->total = $data_cost;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->phone = $phone;
                $order->bucket_bal = $data_wallet_bal;
                $order->prev_bucket_bal = $prev_data_wallet_bal;
                $order->description = $bucket->title . " " . $plan;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->zoePortal($network_id, $phone, $plan_id);

                } catch (Exception $e) {
                    $response = null;
                }

                \Log::info("ZOE PORTAL RESPONSE");
                \Log::info($response);

                if (isset($response) && isset($response['Status']) && $response['Status'] != "successful") {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->subarena_id = !is_null($response['id']) ? $response['id'] : '';
                    //$order->response = $response['data']['gateway_response'];
                    $order->response = isset($response['api_response']) ? $response['api_response'] : "";
                    $order->save();

                    // $msg = $response['data']['gateway_response'];
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->zoe_plan_id, $ref, $bucket->telegram);

                    $msg = isset($response['api_response']) ? $response['api_response'] : "";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response) && isset($response['error'][0])) {
                    $updOrder = BucketOrder::where('ref', $ref)->first();
                    $updOrder->status = 4;
                    $updOrder->save();

                    //refund
                    $this->IncrementBucket($wallet_name, $this->user->id, $data_cost);


                    $order = new BucketOrder();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->bucket_id = $bucket->id;
                    $order->plan = $bucket->title . " " . $plan;
                    $order->quantity = 1;
                    $order->amount = $data_cost;
                    $order->subtotal = $data_cost;
                    $order->total = $data_cost;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->phone = $phone;
                    $order->bucket_bal = $prev_data_wallet_bal;
                    $order->prev_bucket_bal = $data_wallet_bal;
                    $order->description = $bucket->title . " " . $plan;
                    $order->status = 2;
                    $order->response = "Sorry an error occurred, kindly try again.";
                    //$order->response = $response['data']['gateway_response'];
                    // $order->subarena_id = !is_null($response['id']) ? $response['id'] : '';
                    $order->save();

                    // $msg = $response['data']['gateway_response'];
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->zoe_plan_id, $ref, $bucket->telegram);

                    $msg = isset($response['api_response']) ? $response['api_response'] : "";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (!isset($response) || is_null($response) || $response['Status'] == "pending" || $response['Status'] == "processing") {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif (isset($response) && $response['Status'] == "successful") {


                    $updateOrder = BucketOrder::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['api_response'];
                    $updateOrder->save();
                    $msg = $response['api_response'] . " ZoeDataHub Ref: " . $response['id'];


                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->zoe_plan_id, $ref, $bucket->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $data_cost, $plan . " Purchase successful", $response['api_response']);
                }
            } else {
                $prev_wallet_bal = $this->user->wallet;

                $wallet_bal = $this->user->wallet;

                $prev_data_wallet_bal = $this->user->$wallet_name;

                $data_wallet_bal = $this->user->$wallet_name - $data_cost;

                // $order_wallet_name_bal = $wallet_name . "_bal";
                // $prev_order_wallet_name_bal = "prev_" . $wallet_name . "_bal";
                // End of initialization

                $order = new BucketOrder();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->bucket_id = $bucket->id;
                $order->plan = $bucket->title . " " . $plan;
                $order->quantity = 1;
                $order->amount = $data_cost;
                $order->subtotal = $data_cost;
                $order->total = $data_cost;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->phone = $phone;
                $order->bucket_bal = $data_wallet_bal;
                $order->prev_bucket_bal = $prev_data_wallet_bal;
                $order->description = $bucket->title . " " . $plan;
                $order->status = 0;
                $order->save();
            }

        }
    }


    public function show($id)
    {
        $product = BucketOrder::find($id);

        if (is_null($product)) {
            return $this->sendError('Order not found.');
        }

        return $this->sendResponse(new BucketOrderResource($product), 'Order retrieved successfully.');
    }


    public function updateOrder($id, Request $request)
    {
        $pendingOrders = BucketOrder::where('status', 0)->where('id', $id)->first();
        if (is_null($pendingOrders)) {
            return $this->sendError('Order not found.');
        }

        $admin_user = User::find($this->user->id);
        \Log::info($admin_user);
        if ($admin_user->role == 1) {
            $amount = $pendingOrders->amount;

            $prev = User::find($pendingOrders->user_id)->wallet;

            $bal = $prev + $pendingOrders->subtotal;

            $wallet_name = $this->getBucketTitle($pendingOrders->bucket_id);

            $prev_data_wallet_bal = $this->user->$wallet_name;

            $data_wallet_bal = $this->user->$wallet_name + $pendingOrders->amount;




            if ($request->value == "confirm") {

                $pendingOrders->status = 1;
                $pendingOrders->save();

                return $this->sendResponse(" Confirmed successful", " Confirmed successful");
            } else if ($request->value == "cancelled") {
                $pendingOrders->status = 3;

                $pendingOrders->save();
                return $this->sendResponse(" cancelled successful", " cancelled successful");

            } else if ($request->value == "reverse") {
                if ($this->IncrementBucket($wallet_name, $pendingOrders->user_id, $pendingOrders->amount)) {
                    $pendingOrders->status = 2;
                    $pendingOrders->bucket_bal = $data_wallet_bal;
                    $pendingOrders->prev_bucket_bal = $prev_data_wallet_bal;


                    $pendingOrders->save();
                    return $this->sendResponse("Reversed successful", " Reversed successful");
                }
            }

        } else {
            return $this->sendError("Processing failed!");
        }



    }
    
    public function updateConfirmedOrder($id, Request $request)
    {
        $pendingOrders = BucketOrder::where(['status' => 1, 'id' => $id])->first();
        if (is_null($pendingOrders)) {
            return $this->sendError('Bucket Order not found.');
        }

        $admin_user = User::find($this->user->id);
        \Log::info($admin_user);
        if ($admin_user->role == 1) {
            $amount = $pendingOrders->amount;

            $wallet_name = $this->getBucketTitle($pendingOrders->bucket_id);

            $prev_data_wallet_bal = $this->user->$wallet_name;

            $data_wallet_bal = $this->user->$wallet_name + $pendingOrders->amount;


            if ($request->value == "confirm") {

                $pendingOrders->status = 1;
                $pendingOrders->save();

                return $this->sendResponse(" Confirmed successful", " Confirmed successful");
            } else if ($request->value == "cancelled") {
                $pendingOrders->status = 3;

                $pendingOrders->save();
                return $this->sendResponse(" cancelled successful", " cancelled successful");

            } else if ($request->value == "reverse") {
                if ($this->IncrementBucket($wallet_name, $pendingOrders->user_id, $pendingOrders->amount)) {
                    $pendingOrders->status = 2;
                    $pendingOrders->bucket_bal = $data_wallet_bal;
                    $pendingOrders->prev_bucket_bal = $prev_data_wallet_bal;


                    $pendingOrders->save();
                    return $this->sendResponse("Reversed successful", " Reversed successful");
                }
            }

        } else {
            return $this->sendError("Processing failed!");
        }



    }

    public function updateStatus(Request $request) {
        $request->validate([
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:bucket_orders,id',
            'status' => 'required|string'
        ]);

        $transaction_ids = $request->transaction_ids;
        
         $admin_user = User::find($this->user->id);
        if ($admin_user->role == 1) {

        if($request->status == "confirm"){

            foreach($transaction_ids as $id){
                $order = BucketOrder::find($id);
                $order->status = 1;
                $order->save();
            }


            return $this->sendResponse(count($transaction_ids). " Transactions confirmed successfully", count($transaction_ids). " Transactions confirmed successfully");

        }elseif($request->status == "reverse"){

            foreach($transaction_ids as $id){
                    $pendingOrder = BucketOrder::find($id);

                $wallet_name = $this->getBucketTitle($pendingOrder->bucket_id);

                $prev_data_wallet_bal = $this->user->$wallet_name;

                $data_wallet_bal = $this->user->$wallet_name + $pendingOrder->amount;

                $pendingOrder->status = 4;
                $pendingOrder->save();

                //refund
                $this->IncrementBucket($wallet_name, $pendingOrder->user_id, $pendingOrder->amount);

                $order = new BucketOrder();
                $order->ref = $pendingOrder->ref;
                $order->custom_reference = $pendingOrder->custom_reference;
                $order->user_id = $pendingOrder->user_id;
                $order->bucket_id = $pendingOrder->bucket_id;
                $order->plan = $pendingOrder->plan;
                $order->quantity = 1;
                $order->amount = $pendingOrder->amount;
                $order->subtotal = $pendingOrder->subtotal;
                $order->total = $pendingOrder->total;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $pendingOrder->data_size;
                $order->phone = $pendingOrder->phone;
                $order->bucket_bal = $data_wallet_bal;
                $order->prev_bucket_bal = $prev_data_wallet_bal;
                $order->description = $pendingOrder->plan;
                $order->status = 2;
                $order->save();
            }

            return $this->sendResponse(count($transaction_ids). " Transactions failed and reversed successfully", count($transaction_ids). " Transactions failed and reversed successfully");
        }
        
        }else{
            return $this->sendError("Processing failed!");
        }
    }

    public function getReport(Request $request)
    {
        \Log::info("its here");

        $start_date = Carbon::parse($request->start_date)->toDateTime()->format("Y-m-d H:i");
        $end_date = Carbon::parse($request->end_date)->toDateTime()->format("Y-m-d H:i");

        //\Log::info($start_date->format("Y-m-d"));


        if ($end_date < $start_date) {
            return $this->sendError('Invalid date range format', "Invalid date range format");
        }



        if (!empty($request->user_id)) {

            $result = DB::table('bucket_orders')
                ->join('users', 'users.id', '=', 'bucket_orders.user_id')
                ->selectRaw('sum(bucket_orders.amount) as sumtotal, sum(bucket_orders.data_size) as countDataSize, count(bucket_orders.id) as countPlan')
                ->where('users.id', $request->user_id)
                ->whereNot('bucket_orders.plan','like', '%Bucket Purchase of%')
                ->where('bucket_orders.status', $request->status)
                ->where('bucket_orders.bucket_id', $request->bucket_id)
                ->whereBetween('bucket_orders.created_at', [$start_date, $end_date])
                ->groupBy('users.id');

        } elseif ($request->status == null) {
            $result = DB::table('bucket_orders')
                ->selectRaw('sum(bucket_orders.amount) as sumtotal, sum(bucket_orders.data_size) as countDataSize, count(bucket_orders.id) as countPlan')
                ->whereNot('bucket_orders.plan','like', '%Bucket Purchase of%')
                ->whereBetween('orders.created_at', [$start_date, $end_date]);
        } elseif ($request->status == "Status" || $request->userlevel == "User Level" || $request->bucket_id == "Select Provider") {
            $result = DB::table('bucket_orders')
                ->selectRaw('sum(bucket_orders.amount) as sumtotal, sum(bucket_orders.data_size) as countDataSize, count(bucket_orders.id) as countPlan')
                ->whereNot('bucket_orders.plan','like', '%Bucket Purchase of%')
                ->whereBetween('bucket_orders.created_at', [$start_date, $end_date]);
        } else {
            $status = is_null($request->status) ? "" : $request->status;
            $userlevel = is_null($request->userlevel) ? "" : $request->userlevel;
            $bucket_id = is_null($request->bucket_id) ? "" : $request->bucket_id;


            $result = DB::table('bucket_orders')
                ->join('users', 'users.id', '=', 'bucket_orders.user_id')
                ->where('users.userlevel', $userlevel)
                ->where('bucket_orders.status', $status)
                ->where('bucket_orders.bucket_id', $bucket_id)
                ->whereNot('bucket_orders.plan','like', '%Bucket Purchase of%')
                ->selectRaw('sum(bucket_orders.amount) as sumtotal, sum(bucket_orders.data_size) as countDataSize, count(bucket_orders.id) as countPlan')
                ->whereBetween('bucket_orders.created_at', [$start_date, $end_date]);
        }




        $result = $result->get();


        return $this->sendResponse($result, 'User login successfully.');
    }
    
    public function getBucketTransaction(Request $request, $bucket_id)
    {

        $data = BucketOrder::where(['bucket_id' => $bucket_id, 'user_id' => $this->user->id])->with(['user:id,firstname,phone,email'])
            ->orderBy('created_at', 'desc')->paginate(20);


        $products = BucketOrderResource::collection($data);


        return $products;
    }

}
