<?php

namespace App\Http\Controllers\API;

use App\Traits\OrderTrait;
use Carbon\Carbon;
use App\Models\User;
use App\AppConstants;
use App\Models\Order;
use App\Models\Bucket;
use App\Models\BucketOrder;
use App\Models\Payment;
use App\Models\Category;
use App\Models\Dispatch;
use App\Models\AgentStock;
use App\Models\Subcategory;
use App\Traits\WalletTrait;
use Illuminate\Http\Request;
use App\Traits\TelegramTrait;
use App\Traits\ReferenceTrait;
use App\Traits\IntegrationsTrait;
use Illuminate\Support\Facades\DB;
use App\Providers\UploadImageClass;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PaymentResource;
use Illuminate\Support\Facades\Validator;
use App\Notifications\TransactionNotification;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExportOrder;
use App\Models\WaecPins;
use App\Models\NecoPins;
use App\Models\JambPins;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\HttpClientException;

use App\Services\BulkSMSService;
use App\Services\RewardService;

class OrderController extends BaseController
{

    use ReferenceTrait;
    use WalletTrait;
    use IntegrationsTrait;
    use TelegramTrait;
    use OrderTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {


        $search = $request->input('search');
        $user_id = $request->input('user_id');
        
        if (isset($request->search)) {
            $category = Category::where('title', $request->search)->first();
        }

        if ($request->has('search')) {

            if ($request->has('field') && $request->field != "Search By" && $request->has('status') && $request->status != "Select Status") {  // 3 active variable search
                $data = Order::with(['user:id,firstname,phone,email'])
                    ->when($request->field === 'type' && $category, function ($query) use ($category) {
                        $query->whereHas('subcategory', function ($q) use ($category) {
                            $q->where('category_id', $category->id);
                        });
                    }, function ($query) use ($request) {
                        if (!empty($request->search)) {
                            $query->where($request->field, 'like', '%' . $request->search . '%');
                        }
                    })
                    ->when(!empty($request->status), function ($query) use ($request) {
                        $query->where('status', $request->status);
                    })
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
                $products = OrderResource::collection($data);
            } else { 
                // 1 variable
                if ($request->has('field') && $request->field == "type") {
                   $data = Order::with(['user:id,firstname,phone,email'])
                        ->whereHas('subcategory', function ($query) use ($category) {
                            $query->where('category_id', $category->id);
                        })
                        ->orderBy('created_at', 'desc')
                        ->paginate(20);
                } else {
                    $data = Order::with(['user:id,firstname,phone,email'])
                        //->where('bal', '>', 0)->orWhere('prev_bal', '>', 0)
                        ->where($request->field, 'like', '%' . $search . '%')
                        ->orderBy('created_at', 'desc')->paginate(20);
                }
                $products = OrderResource::collection($data);
            }

            return $products;
        }

        if ($request->has('search_status')) {
            $search = $request->search_status;
            if ($request->field == 'userphone') {
                $data = Order::with(['user:id,firstname,phone,email'])
                    ->where('status', 0)
                    ->WhereHas(
                        'user',
                        function ($query) use ($search) {
                            return $query->where('phone', 'LIKE', '%' . $search . '%');
                        }
                    )->orderBy('created_at', 'desc')->paginate(20);
                $products = OrderResource::collection($data);
            } else {
                $data = Order::with(['user:id,firstname,phone,email'])
                    ->where('status', 0)
                    ->where($request->field, 'like', '%' . $search . '%')
                    ->orderBy('created_at', 'desc')->paginate(20);
                $products = OrderResource::collection($data);
            }
            return $products;
        }

        if ($request->has('section') && $request->section == 1) {
            \Log::info("here works too");

            $data = Order::with(['user:id,firstname,phone,email'])
                // ->where('bal', '>', 0)->orWhere('prev_bal', '>', 0)
                ->where('status', 0)->orderBy('created_at', 'desc')->paginate(20);
            $products = OrderResource::collection($data);

            return $products;
        }

        if ($request->has('search_user')) {
            $search = $request->search_user;
            if ($request->has('stats')) {
                if ($request->has('field') && $request->field != "Search By" && $request->has('status') && $request->status != "Select Status") {  // 3 active variable search
                    $data = Order::with(['user:id,firstname,phone,email'])
                        ->where('user_id', $user_id)
                        ->where($request->field, 'like', '%' . $search . '%')
                        ->where('status', $request->status)
                        ->orderBy('created_at', 'desc')->paginate(20);
                    $products = OrderResource::collection($data);
                } else { // 1 variable
                    $data = Order::with(['user:id,firstname,phone,email'])
                        ->where('user_id', $user_id)
                        ->where($request->field, 'like', '%' . $search . '%')
                        ->orderBy('created_at', 'desc')->paginate(20);
                    $products = OrderResource::collection($data);
                }
                // if ($request->field == 'userphone') {
                //     $data =  Order::with(['user:id,firstname,phone,email'])
                //         ->where('user_id', $user_id)
                //         //->where('status', '>', 0)
                //         ->WhereHas(
                //             'user',
                //             function ($query) use ($search) {
                //                 return $query->where('phone', 'LIKE', '%' .  $search . '%');
                //             }
                //         )->orderBy('created_at', 'desc')->paginate(20);
                //     $products = OrderResource::collection($data);
                // }elseif ($request->has('status') && $request->status !="Select Status") {
                //     $status = $request->status;
                //     $data =  Order::with(['user:id,firstname,phone,email'])
                //         ->where('user_id', $user_id)
                //         ->where('status',$status)->orderBy('created_at', 'desc')->paginate(20);
                //     $products = OrderResource::collection($data);
                // } else {
                //     $data =  Order::with(['user:id,firstname,phone,email'])
                //         ->where('user_id', $user_id)
                //         //->where('status', '>', 0)
                //         ->where($request->field, 'like', '%' . $search . '%')
                //         ->orderBy('created_at', 'desc')->paginate(20);
                //     $products = OrderResource::collection($data);
                // }
            } else {
                if ($request->has('field') && $request->field != "Search By" && $request->has('status') && $request->status != "Select Status") {  // 3 active variable search
                    $data = Order::with(['user:id,firstname,phone,email'])
                        ->where('user_id', $user_id)
                        ->where($request->field, 'like', '%' . $search . '%')
                        ->where('status', $request->status)
                        ->orderBy('created_at', 'desc')->paginate(20);
                    $products = OrderResource::collection($data);
                } else { // 1 variable
                    $data = Order::with(['user:id,firstname,phone,email'])
                        ->where('user_id', $user_id)
                        ->where($request->field, 'like', '%' . $search . '%')
                        ->orderBy('created_at', 'desc')->paginate(20);
                    $products = OrderResource::collection($data);
                }

                // if ($request->field == 'userphone') {
                //     $data =  Order::with(['user:id,firstname,phone,email'])
                //         ->where('user_id', $user_id)
                //         ->WhereHas(
                //             'user',
                //             function ($query) use ($search) {
                //                 return $query->where('phone', 'LIKE', '%' .  $search . '%');
                //             }
                //         )->orderBy('created_at', 'desc')->paginate(20);
                //     $products = OrderResource::collection($data);
                // }elseif ($request->has('status') && $request->status !="Select Status") {
                //     $status = $request->status;
                //     $data =  Order::with(['user:id,firstname,phone,email'])
                //         ->where('user_id', $user_id)
                //         ->where('status',$status)->orderBy('created_at', 'desc')->paginate(20);
                //     $products = OrderResource::collection($data);
                // } else {
                //     $data =  Order::with(['user:id,firstname,phone,email'])
                //         ->where('user_id', $user_id)
                //         ->where($request->field, 'like', '%' . $search . '%')
                //         ->orderBy('created_at', 'desc')->paginate(20);
                //     $products = OrderResource::collection($data);
                // }
            }
            // } else {

            //     $data =  Order::with(['user:id,firstname,phone,email'])
            //         //->where('bal', '>', 0)->orWhere('prev_bal', '>', 0)
            //         ->where('description', 'like', '%' . $search . '%')
            //         // ->orWhere('plan', 'like', '%' . $search . '%')
            //         ->orWhere('ref', 'like', '%' . $search . '%')
            //         ->orWhere('phone', 'like', '%' . $search . '%')
            //         ->orWhere('iuc', 'like', '%' . $search . '%')
            //         ->orWhere('meter', 'like', '%' . $search . '%')

            //         ->orWhereHas(
            //             'user',
            //             function ($query) use ($search) {
            //                 return $query->where('firstname', 'LIKE', '%' .  $search . '%')
            //                     ->orWhere('phone', 'LIKE', '%' .  $search . '%')
            //                     ->orWhere('email', 'LIKE', '%' .  $search . '%');
            //             }
            //         )->orderBy('created_at', 'desc')->paginate(20);
            //     $products = OrderResource::collection($data);
            // }
            return $products;
        }

        if ($request->has('user')) {
            $user_id = $request->user;

            if ($request->has('stats')) {
                $data = Order::with(['user:id,firstname,phone,email'])
                    //->where('bal', '>', 0)->orWhere('prev_bal', '>', 0)
                    ->where('user_id', $user_id)
                    //->where('status', '>', 0)
                    ->orderBy('created_at', 'desc')->paginate(20);
                $products = OrderResource::collection($data);
            } else {
                $data = Order::with(['user:id,firstname,phone,email'])
                    //->where('bal', '>', 0)->orWhere('prev_bal', '>', 0)
                    ->where('user_id', $user_id)->orderBy('created_at', 'desc')->paginate(20);
                $products = OrderResource::collection($data);
            }


            return $products;
        }

        $data = Order::with(['user:id,firstname,phone,email'])
            ->orderBy('created_at', 'desc')->paginate(20);

        // if (!is_null($request->limit)) {
        //     $query = $request->limit == 0 ?
        //         $query->take($request->limit)->get() : $query;
        // }






        // if ($search && $request->status == 0) {

        //     $data =  Order::with(['user:id,firstname,phone,email'])
        //         ->where('status', 0)
        //         ->orWhere('description', 'like', '%' . $search . '%')
        //         ->orWhere('plan', 'like', '%' . $search . '%')
        //         ->orWhere('ref', 'like', '%' . $search . '%')

        //         ->orWhereHas(
        //             'user',
        //             function ($query) use ($search) {
        //                 return $query->where('firstname', 'LIKE', '%' .  $search . '%')
        //                     ->orWhere('phone', 'LIKE', '%' .  $search . '%')
        //                     ->orWhere('email', 'LIKE', '%' .  $search . '%');
        //             }
        //         )

        //         ->paginate(10);
        // }

        // if ($search) {

        //     $data =  Order::where('description', 'like', '%' . $search . '%')
        //         ->orWhere('ref', 'like', '%' . $search . '%')
        //         ->paginate(10);
        // } else  if ($user_id) {
        //     $data =  Order::where('user_id', $user_id)->orderBy('created_at', 'desc')->paginate(10);
        // } else  if (!is_null($request->limit)) {

        //     $data = $request->limit == 0 ? Order::where('user_id', $this->user->id)->orderBy('created_at', 'desc')->paginate(10) :  Order::where('user_id', $this->user->id)->orderBy('created_at', 'desc')->take($request->limit)->get();
        // } else  if (!is_null($request->status)) {

        //     $data = Order::where('user_id', $this->user->id)->where('status', 0)->orderBy('created_at', 'desc')->paginate(5);
        // } else {
        //     $data = Order::orderBy('created_at', 'desc')->paginate(10);
        // }


        $products = OrderResource::collection($data);

        return $products;
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


        $subcategory_id = $request->subcategory_id;
        $subcategory = Subcategory::findOrFail($subcategory_id);

        if($subcategory->category->title == "Airtime"){
            if (!empty($request->user_id)) {

                $result = DB::table('orders')
                    ->join('users', 'users.id', '=', 'orders.user_id')
                    ->selectRaw('sum(orders.subtotal) as sumtotal, sum(orders.amount) as sumAmount, count(orders.id) as countPlan')
                    ->where('users.id', $request->user_id)
                    ->where('orders.status', $request->status)
                    ->where('orders.subcategory_id', $request->subcategory_id)
                    ->whereBetween('orders.created_at', [$start_date, $end_date])
                    ->groupBy('users.id');

            } elseif ($request->status == null) {
                $result = DB::table('orders')
                    ->selectRaw('sum(orders.subtotal) as sumtotal,  sum(orders.amount) as sumAmount,  count(orders.id) as countPlan')
                    ->whereBetween('orders.created_at', [$start_date, $end_date]);
            } elseif ($request->status == "Status" || $request->userlevel == "User Level" || $request->subcategory_id == "Select Provider") {
                $result = DB::table('orders')
                    ->selectRaw('sum(orders.subtotal) as sumtotal,  sum(orders.amount) as sumAmount, count(orders.id) as countPlan')
                    ->whereBetween('orders.created_at', [$start_date, $end_date]);
            } else {
                $status = is_null($request->status) ? "" : $request->status;
                $userlevel = is_null($request->userlevel) ? "" : $request->userlevel;
                $subcategory_id = is_null($request->subcategory_id) ? "" : $request->subcategory_id;


                $result = DB::table('orders')
                    ->join('users', 'users.id', '=', 'orders.user_id')
                    ->where('users.userlevel', $userlevel)
                    ->where('orders.status', $status)
                    ->where('orders.subcategory_id', $subcategory_id)
                    ->selectRaw('sum(orders.total) as sumtotal,  sum(orders.amount) as sumAmount, count(orders.id) as countPlan')
                    ->whereBetween('orders.created_at', [$start_date, $end_date]);
            }
        } else {
            if (!empty($request->user_id)) {

                $result = DB::table('orders')
                    ->join('users', 'users.id', '=', 'orders.user_id')
                    ->selectRaw('sum(orders.subtotal) as sumtotal, sum(orders.data_size) as countDataSize, count(orders.id) as countPlan')
                    ->where('users.id', $request->user_id)
                    ->whereNot('orders.plan', 'like', '%Bucket Purchase of%')
                    ->where('orders.status', $request->status)
                    ->where('orders.subcategory_id', $request->subcategory_id)
                    ->whereBetween('orders.created_at', [$start_date, $end_date])
                    ->groupBy('users.id');

            } elseif ($request->status == null) {
                $result = DB::table('orders')
                    ->selectRaw('sum(orders.subtotal) as sumtotal, sum(orders.data_size) as countDataSize, count(orders.id) as countPlan')
                    ->whereNot('orders.plan', 'like', '%Bucket Purchase of%')
                    ->whereBetween('orders.created_at', [$start_date, $end_date]);
            } elseif ($request->status == "Status" || $request->userlevel == "User Level" || $request->subcategory_id == "Select Provider") {
                $result = DB::table('orders')
                    ->selectRaw('sum(orders.subtotal) as sumtotal, sum(orders.data_size) as countDataSize, count(orders.id) as countPlan')
                    ->whereNot('orders.plan', 'like', '%Bucket Purchase of%')
                    ->whereBetween('orders.created_at', [$start_date, $end_date]);
            } else {
                $status = is_null($request->status) ? "" : $request->status;
                $userlevel = is_null($request->userlevel) ? "" : $request->userlevel;
                $subcategory_id = is_null($request->subcategory_id) ? "" : $request->subcategory_id;


                $result = DB::table('orders')
                    ->join('users', 'users.id', '=', 'orders.user_id')
                    ->where('users.userlevel', $userlevel)
                    ->where('orders.status', $status)
                    ->where('orders.subcategory_id', $subcategory_id)
                    ->selectRaw('sum(orders.subtotal) as sumtotal, sum(orders.data_size) as countDataSize, count(orders.id) as countPlan')
                    ->whereNot('orders.plan', 'like', '%Bucket Purchase of%')
                    ->whereBetween('orders.created_at', [$start_date, $end_date]);
            }
        }


        $result = $result->get();


        return $this->sendResponse($result, 'User login successfully.');
    } 
    
    public function getAccountingReport(Request $request)
    {
    \Log::info("Accounting report request received", $request->all());

    // ✅ Step 1: Validate required inputs
    $validator = Validator::make($request->all(), [
        'start_date' => 'required|date',
        'end_date' => 'required|date',
        'subcategory_id' => 'required|exists:subcategory,id',
    ]);

    if ($validator->fails()) {
        return $this->sendError('Validation Error', $validator->errors()->first());
    }

    // ✅ Step 2: Parse & format safely
    try {
        $start_date = Carbon::parse($request->start_date)->format('Y-m-d H:i');
        $end_date = Carbon::parse($request->end_date)->format('Y-m-d H:i');
    } catch (\Exception $e) {
        return $this->sendError('Invalid date format', 'Please provide valid start and end dates.');
    }

    // ✅ Step 3: Logical validation (end date should be after start date)
    if ($end_date < $start_date) {
        return $this->sendError('Invalid date range', 'End date cannot be earlier than start date.');
    }

    // ✅ Step 4: Find subcategory safely
    $subcategory = Subcategory::find($request->subcategory_id);
    if (!$subcategory) {
        return $this->sendError('Invalid Subcategory', 'The provided subcategory ID does not exist.');
    }

    // ✅ Step 5: Determine category type
    $isAirtime = $subcategory->category->title == "Airtime";

    // ✅ Step 6: Handle empty status / api_route gracefully
    $status = $request->status ?? '';
    $api_route = $request->api_route ?? '';

    // ✅ Step 7: Build query dynamically
    $query = DB::table('orders')
        ->join('users', 'users.id', '=', 'orders.user_id')
        ->whereBetween('orders.created_at', [$start_date, $end_date]);

    if ($status && $status != 'Status') {
        $query->where('orders.status', $status);
    }
    if ($api_route && $api_route != 'Api Route') {
        $query->where('orders.api_route', $api_route);
    }
    if ($subcategory) {
        $query->where('orders.subcategory_id', $subcategory->id);
    }

    // ✅ Step 8: Select fields based on category
    if ($isAirtime) {
        $query->selectRaw('
            SUM(orders.subtotal) as amountPaid,
            SUM(orders.amount) as sumAmount,
            COUNT(orders.id) as countPlan
        ');
    } else {
        $query->selectRaw('
            SUM(orders.subtotal) as amountPaid,
            SUM(orders.cost_price) as costPrice,
            SUM(orders.data_size) as dataSize,
            SUM(orders.amount) as sumAmount,
            COUNT(orders.id) as countPlan
        ');
    }

    // ✅ Step 9: Execute query
    $result = $query->get();

    // ✅ Step 10: Handle empty results
    if ($result->isEmpty()) {
        return $this->sendError('No records found', 'No data found for the selected filters.');
    }

    return $this->sendResponse($result, 'Accounting report generated successfully.');
    }

    protected function processReferralRewards(Order $order)
    {
        try {
            RewardService::processSuccessfulOrder($order);
        } catch (\Throwable $e) {
            /*
             * A reward error should not turn a successfully delivered
             * customer purchase into a failed purchase.
             */
            \Log::error('Referral reward processing failed', [
                'order_id' => $order->id,
                'order_ref' => $order->ref,
                'user_id' => $order->user_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Keep transport failures uniform and leave the transaction pending for
     * webhook/requery reconciliation instead of treating connectivity as a
     * confirmed provider failure.
     */
    protected function sendProviderConnectionPending(
        \Throwable $exception,
        $ref,
        $customReference
    ) {
        \Log::warning('Provider request could not be completed; transaction left pending.', [
            'reference' => $ref,
            'custom_reference' => $customReference,
            'request_path' => request()->path(),
            'exception' => get_class($exception),
            'error' => $exception->getMessage(),
        ]);

        return $this->sendError2(
            $ref,
            $customReference,
            'Transaction pending',
            'Transaction pending'
        );
    }


    public function purchaseAirtime(Request $request)
    {
        $amount = $request->amount;
        $phone = $request->phonenumber;
        
        if($phone == "07036028966" || $phone == "09037303701" || $phone == "+2347036028966" || $phone == "2347036028966" || $phone == "+2349037303701" || $phone == "2349037303701" || $phone == "+2348086655443" || $phone == "08086655443" || $phone == "09034928264" || $phone == "+2349034928264" || $phone == "08063861669" || $phone == "+2348063861669"){
            return $this->sendResponse("Error","Error");
        }
        // $ref = $this->referenceCode(); //work on code length;
        $custom_reference = $request->custom_reference != null ? $request->custom_reference : null;


        $subcategory = Subcategory::find($request->subcategory_id);

        if (!is_null($subcategory->description) && $subcategory->description == "JONET") {
            $ref = $this->jonetReferenceCode();
        } elseif (!is_null($subcategory->description) && $subcategory->description == "AUTOPILOT") {
            $ref = $this->autoPilotReferenceCode();
        } elseif (!is_null($subcategory->description) && $subcategory->description == "AUTOPILOTAWUF") {
            $ref = $this->autoPilotReferenceCode();
        } else {
            $ref = $this->referenceCode();
        }

        $amount = intVal($amount);
        $productCollection = json_decode($subcategory->products);
        //$getSelectedProduct = $productCollection->first();
        $discount = $this->getUserLevel($productCollection, $this->user->userlevel);

        $network = explode(' ', $subcategory->title)[0];
        $amountActual = $amount - (($discount / 100) * $amount);
 

        if (is_null($phone)) {
            return $this->sendError('Phone Number is required', 'Phone Number is required');
        }
        
        if (!preg_match('/^(0\d{10}|\+234\d{10}|234\d{10})$/', $request->phonenumber)) {
            return $this->sendError(
                'Invalid phone number format',
                'Phone number must be 11 digits starting with 0, or start with +234 / 234 followed by 10 digits.'
            );
        } 
        
        

        if (!$request->ported && !$this->isValidPhoneProvider(strtolower($network), $phone)) {
            return $this->sendError('This is not an ' . $network . ' Phone Number', 'This is not an ' . $network . ' Phone Number');
        }


        // if (!$this->isValidPhoneProvider(strtolower($network), $phone)) {
        //     return $this->sendError('This is not an ' . $network . ' Phone Number', 'This is not an ' . $network . ' Phone Number',);
        // }

        if ($subcategory->status == 0) {
            return $this->sendError('Product not available right now', 'Product not available right');
        } else {
            
            $gate = (string) $request->header('X-Gate-Token');
            $expectedGate = (string) config('app.GATE_TOKEN');
            
            $isPalmPayRequest = $expectedGate !== ''
                && hash_equals($expectedGate, $gate);
            
            if (!$isPalmPayRequest && !$this->isDebited($amountActual)) {
                return $this->sendError(
                    'Insufficient Balance for this transaction',
                    'Insufficient Balance for this transaction'
                );
            }

            $prev = $this->user->wallet;

            $bal = $this->user->wallet - $amountActual;


            if (!is_null($subcategory->description) && $subcategory->description == "SMEPLUG") {

                $network_id = $this->translateSMEPlugAirtimeNetwork($subcategory->title);



                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " N" . $amount;
                $order->amount = $amount;
                $order->quantity = 1;
                $order->subtotal = $amountActual;
                $order->phone = $phone;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->bal = $bal;
                $order->prev_bal = $prev;

                $order->total = $amountActual;
                $order->description = $subcategory->title . " N" . $amount;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->SMEPlugAirtimeApi($network_id, $amount, $phone, $ref);
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                if (!isset($response) || is_null($response)) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } else {



                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response->data->msg;
                    $updateOrder->save();
                    $msg = $response->data->msg . " SMEPLUG Ref: " . $response->data->reference;
                    $this->processReferralRewards($updateOrder);

                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $subcategory->pins, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $subcategory->title . " Purchase successful", $subcategory->title . " Purchase successful");
                }
            }

            if (!is_null($subcategory->description) && $subcategory->description == "AUTOPILOT") {

                $network_id = $this->parseAutoPilotNetworkID(strtolower($subcategory->title));



                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " N" . $amount;
                $order->amount = $amount;
                $order->quantity = 1;
                $order->subtotal = $amountActual;
                $order->phone = $phone;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->bal = $bal;
                $order->prev_bal = $prev;

                $order->total = $amountActual;
                $order->description = $subcategory->title . " N" . $amount;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->autoPilotAirtime($network_id, "VTU", $amount, $phone, $ref);
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                \Log::info("AUTOPILOT AIRTIME");
                \Log::info(print_r($response, true));

                if (!isset($response) || is_null($response) || $response['code'] == 201) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif ($response['code'] == 424) {
                    $response_msg = null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );

                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $subcategory->pins, $ref, $subcategory->telegram);

                    return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                } elseif ($response['code'] == 200) {



                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['data']['message'];
                    $updateOrder->save();
                    $msg = $response['data']['message'] . " Autopilot Ref: " . $response['data']['reference'];
                    $this->processReferralRewards($updateOrder);

                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $subcategory->pins, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $subcategory->title . " Purchase successful", $subcategory->title . " Purchase successful");
                } else {
                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
                }
            }
            
            if (!is_null($subcategory->description) && $subcategory->description == "SIMSERVER") {

                $network_id = $this->parseAutoPilotNetworkID(strtolower($subcategory->title));



                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " N" . $amount;
                $order->amount = $amount;
                $order->quantity = 1;
                $order->subtotal = $amountActual;
                $order->phone = $phone;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->bal = $bal;
                $order->prev_bal = $prev;

                $order->total = $amountActual;
                $order->description = $subcategory->title . " N" . $amount;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->simServer('6274', $amount, $phone, $ref);
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                \Log::info("SIMSERVER AIRTIME");
                \Log::info(print_r($response, true));

                if (!isset($response) || is_null($response) || $response['text_status'] == 'PENDING') {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif (isset($response['data']) && isset($response['data']['text_status']) && $response['data']['text_status'] == 'FAILED') {
                    $response_msg = $response['data']['true_response'];
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );

                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $subcategory->pins, $ref, $subcategory->telegram);

                    return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                } elseif (isset($response) && isset($response['text_status']) && $response['text_status'] == 'FAILED') {
                    $response_msg = $response['data']['true_response'];
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );

                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $subcategory->pins, $ref, $subcategory->telegram);

                    return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                } elseif (isset($response['data']) && isset($response['data']['text_status']) &&  $response['data']['text_status'] == 'COMPLETED') {



                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['data']['true_response'];
                    $updateOrder->save();
                    $msg = $response['data']['true_response'] . " SIMSERVER Ref: " . $response['data']['recharge_id'];

                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $subcategory->pins, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $subcategory->title . " Purchase successful", $subcategory->title . " Purchase successful");
                } else {
                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
                }
            }
            
            if (!is_null($subcategory->description) && $subcategory->description == "AUTOSYNCAIRTIME") {

                $network_id = $this->parseAutoSyncNetworkID(strtolower($subcategory->title));



                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " N" . $amount;
                $order->amount = $amount;
                $order->quantity = 1;
                $order->subtotal = $amountActual; 
                $order->phone = $phone;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->bal = $bal;
                $order->prev_bal = $prev;

                $order->total = $amountActual;
                $order->description = $subcategory->title . " N" . $amount;
                $order->status = 0;
                $order->save();
                
                \Log::info("AUTOSYNC AIRTIME START");

                try {
                    $response = $this->autoSyncPortalAirtime($ref,$phone,$network_id, $amount, "2102");
                    \Log::info("AUTOSYNC AIRTIME TRY BLOCK");
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                \Log::info("AUTOSYNC AIRTIME");
                \Log::info(print_r($response, true));

                if (!isset($response) || is_null($response)) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif(isset($response['status']) && $response['status'] == "error"){
                    $response_msg = $response['message'] ?? null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                  return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                    
                } elseif (isset($response['data']) && $response['data']['transaction']['status'] == "successful") {


                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['data']['transaction']['details'];
                    $updateOrder->save();
                    $msg = $response['data']['transaction']['details'] . " Autosync Ref: " . $response['data']['transaction']['reference'];

                    //$this->sendTelegramMessage($amount, $phone, $subcategory->pins, $subcategory->pins, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $subcategory->title . " Purchase successful", $subcategory->title . " Purchase successful");
                } else {
                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
                }
            }
            
            if (!is_null($subcategory->description) && $subcategory->description == "VTPASS") {

                $network_id = strtolower($subcategory->title);



                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " N" . $amount;
                $order->amount = $amount;
                $order->quantity = 1;
                $order->subtotal = $amountActual;
                $order->phone = $phone;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->bal = $bal;
                $order->prev_bal = $prev;

                $order->total = $amountActual;
                $order->description = $subcategory->title . " N" . $amount;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->VtPassAirtime($network_id, $amount, $phone, $ref);
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                \Log::info("VTPASS AIRTIME");
                \Log::info(print_r($response, true));

                if (!isset($response) || is_null($response) || $response['code'] == '099') {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif ($response['code'] == '016'|| $response['code'] == "040" || $response['code'] == "013" || $response['code'] == "019") {
                    $response_msg = "Transaction failed: ".$response['code'];
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );

                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $subcategory->pins, $ref, $subcategory->telegram);

                    return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                }  elseif (isset($response) && $response['code'] == '000') {

                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['response_description'];
                    $updateOrder->save();
                    $msg = $response['response_description'] . " VTPASS Ref: " . $ref;

                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $subcategory->pins, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $subcategory->title . " Purchase successful", $subcategory->title . " Purchase successful");
                } else {
                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
                }
            }

            if (is_null($subcategory->description)) {
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " N" . $amount;
                $order->amount = $amount;
                $order->quantity = 1;
                $order->subtotal = $amountActual;
                $order->phone = $phone;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->bal = $bal;
                $order->prev_bal = $prev;

                $order->total = $amountActual;
                $order->description = $subcategory->title . " N" . $amount;
                $order->status = 1;
                $order->save();

                // $channelText = $subcategory->pins;
                // $channelText = str_replace(['AMOUNT', 'PHONE', 'REF'], [$amount, $phone, $ref], $channelText);

                $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $subcategory->pins, $ref, $subcategory->telegram);

                return $this->sendResponse2($ref, $custom_reference, $amountActual, $subcategory->title . " Purchase successful", $subcategory->title . " Purchase successful");

            }


        }
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
    
    public function addCountryCode($number)
    {
        // Check if the number already starts with +234
        if (substr($number, 0, 4) === "234") {
            return $number; // Return as is
        }

        // Remove leading zero if present
        if ($number[0] === "0") {
            $number = substr($number, 1);
        }

        // Add +234 in front of the number
        return "234" . $number;
    }
    
    public function purchaseTalkmore(Request $request)
    {
        $subcategory = Subcategory::where('id', $request->subcategory_id)
            ->where('category_id', 13)
            ->first();

        if (is_null($subcategory)) {
            return $this->sendError(
                'Invalid Talkmore plan',
                'Please select a valid Talkmore plan.'
            );
        }

        return $this->purchaseData($request);
    }
    
        

    public function purchaseData(Request $request)
    {
        
        
        $phone1 = $request->phonenumber;

        if($phone1 == "07036028966" || $phone1 == "09037303701" || $phone1 == "+2347036028966" || $phone1 == "2347036028966" || $phone1 == "+2349037303701" || $phone1 == "2349037303701" || $phone1 == "+2348086655443" || $phone1 == "08086655443" || $phone1 == "09034928264" || $phone1 == "+2349034928264" || $phone1 == "08063861669" || $phone1 == "+2348063861669" ){
            return $this->sendResponse("Error","Error");
        }
        
        $subcategory = Subcategory::find($request->subcategory_id);
       
        $bucket_title = $subcategory->title;
        
        $bucket = Bucket::where('title',$bucket_title)->first();

        if(!is_null($bucket) && $bucket->status == 1){
            $wallet_name =  $this->getBucketTitle($bucket->id);
            \Log::info($this->user->$wallet_name);

            $wallet_status = $wallet_name."_status"; 
            \Log::info($this->user->$wallet_status);
            
            //return $this->sendError("Bucket is not available", "Bucket is not available");
            
            if($this->user->$wallet_name != 0 && $this->user->$wallet_status == 1) {
                return $this->purchaseBucketData($request);
            }
        }

        
        
        $plan = $request->plan_id;
        $amount = $request->amount;
        
        
        if (!preg_match('/^(0\d{10}|\+234\d{10}|234\d{10})$/', $request->phonenumber)) {
            return $this->sendError(
                'Invalid phone number format',
                'Phone number must be 11 digits starting with 0, or start with +234 / 234 followed by 10 digits.'
            );
        } 


        $phone = $this->formatPhoneNumber($request->phonenumber);
        
        
        $ref = $this->referenceCode();
        $custom_reference = $request->custom_reference != null ? $request->custom_reference : null;
        $subcategory = Subcategory::find($request->subcategory_id);

        if (!is_null($subcategory->description) && $subcategory->description == "JONET") {
            $ref = $this->jonetReferenceCode();
        } elseif (!is_null($subcategory->description) && $subcategory->description == "AUTOPILOT") {
            $ref = $this->autoPilotReferenceCode();
        } elseif (!is_null($subcategory->description) && $subcategory->description == "AUTOPILOTAWUF") {
            $ref = $this->autoPilotReferenceCode();
        } elseif (!is_null($subcategory->description) && $subcategory->description == "TBCHPORTAL") {
            $ref = $this->autoPilotReferenceCode();
        } else {
            $ref = $this->referenceCode();
        }
        
    
        // $amountJson =  ;
        $amount = intVal($amount);

        $productCollection = collect(json_decode($subcategory->products));
        $getSelectedProduct = $productCollection->where('plan', $plan)->first();
        $amountActual = $this->getUserLevel($getSelectedProduct, $this->user->userlevel);

        $network = explode(' ', $subcategory->title)[0];
        
        
        
        if (is_null($request->phonenumber)) {
            return $this->sendError('Phone Number is required', 'Phone Number is required');
        }
        
        if (strlen($request->phonenumber) < 11) {
            return $this->sendError('Phone Number cannot be less than 11 digits', 'Phone Number cannot be less than 11 digits');
        }

        if (!$request->ported && !$this->isValidPhoneProvider(strtolower($network), $phone)) {
            return $this->sendError('This is not an ' . $network . ' Phone Number', 'This is not an ' . $network . ' Phone Number');
        }

        if ($subcategory->status == 0) {
            return $this->sendError('Product not available right now', 'Product not available right');
        } elseif ($subcategory->status == 1 && is_null($subcategory->description)) {
            if (!$this->isDebited($amountActual)) {
                return $this->sendError('Insufficient Balance for this transaction', 'Insufficient Balance for this transaction');
            }
            $prev = $this->user->wallet;

            $bal = $this->user->wallet - $amountActual;
            $order = new Order();
            $order->ref = $ref;
            $order->custom_reference = $custom_reference;
            $order->user_id = $this->user->id;
            $order->subcategory_id = $subcategory->id;
            $order->plan = $subcategory->title . " " . $plan . " at N" . $amountActual;
            $order->amount = $amountActual;
            $order->quantity = 1;
            $order->subtotal = $amountActual;
            $order->total = $amountActual;
            $order->channel = is_null($request->channel) ? 'Web' : 'App';
            $order->data_size = $getSelectedProduct->data_size;
            $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
            $order->phone = $phone;
            $order->bal = $bal;
            $order->prev_bal = $prev;
            $order->description = $subcategory->title . " " . $plan .
                " at N" . $amountActual;
            $order->status = 1;
            $order->save();


            $this->processReferralRewards($order);
           
            $this->sendTelegramMessage($amountActual, $phone, "0000", $getSelectedProduct->code, $ref, "-1002351880817");

            return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $plan . " Purchase successful");
        } else {
            $gate = (string) $request->header('X-Gate-Token');
            $expectedGate = (string) config('app.GATE_TOKEN');
            
            $isPalmPayRequest = $expectedGate !== ''
                && hash_equals($expectedGate, $gate);
            
            if (!$isPalmPayRequest && !$this->isDebited($amountActual)) {
                return $this->sendError(
                    'Insufficient Balance for this transaction',
                    'Insufficient Balance for this transaction'
                );
            }

            

            $prev = $this->user->wallet;

            $bal = $this->user->wallet - $amountActual;
            
            

            if (!is_null($subcategory->description) && $subcategory->description == "SMEPLUG") {

                      
                    $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                // $plan_id = $getSelectedProduct->code;
                    $plan_id = $getSelectedProduct->smeplug_id;
                    
                    
                    $order = new Order();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->subcategory_id = $subcategory->id;
                    $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
                    $order->amount = $amountActual;
                    $order->quantity = 1;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                    $order->subtotal = $amountActual;
                    $order->total = $amountActual;
                    $order->phone = $phone;
                    $order->bal = $bal;
                    $order->prev_bal = $prev;
                    $order->description = $subcategory->title . " " . $plan .
                        " at N" . $amountActual;
                    $order->plan_id = $plan;
                    $order->api_route = $subcategory->description;
                    $order->status = 0;
                    $order->save();
                
    
                try {
                    $response = $this->SMEPlugApi($network_id, $plan_id, $phone, $ref);
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }
                
                \Log::info("SMEPLUG PURCHASE DATA");
                \Log::info($phone);
                \Log::info($ref);
                \Log::info($subcategory->title);

                
                \Log::info("SMEPLUG RESPONSE");
                
                \Log::info(print_r($response,true));
                
                
                if(!isset($response) || is_null($response) || !isset($response->status) || is_null($response->status) || empty($response->status)){
                     return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
                    
                }elseif(isset($response->status) && $response->status == true && isset($response->data->current_status) && $response->data->current_status == "processing"){
                     return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
                    
                }elseif(isset($response->msg) && $response->msg == "Plan cannot be dispensed via wallet at this time"){
                    $response_msg = isset($response->msg) ? $response->msg : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );

                    
                    return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");

                }elseif(isset($response->data->current_status) && $response->data->current_status == "failed"){
                    //$response_msg = isset($response->data->msg) ? $response->data->msg : null;
                    $response_msg = $subcategory->id === 52 ? "Transaction failed" : ($response->data->msg ?? null);
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );

                    
                    return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");

                }elseif(isset($response->status) && $response->status == true && isset($response->data->current_status) && $response->data->current_status == "success") {

    
                        
    
                        if (stripos($response->data->msg, "not successful") > 0) {
                            $updateOrder = Order::where('ref', $ref)->first();
    
                            $updateOrder->status = 0;
                            $updateOrder->response = $response->data->msg;
                            $updateOrder->save();
                            $msg = $response->data->msg . " SMEPLUG Ref: " . $response->data->reference;
    
                        } else {
                            
                            $updateOrder = Order::where('ref', $ref)->first();
                           
                            $updateOrder->status = 1;
                            $updateOrder->response = $this->normalizeBundleMessage($response->data->msg); 
                            $updateOrder->save();
                            $msg = $response->data->msg . " SMEPLUG Ref: " . $response->data->reference;
                            
                            $this->processReferralRewards($updateOrder);
    
                        }
    
                          $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->smeplug_id, $ref, $subcategory->telegram, $msg);
    
    
                        return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $subcategory->id == 52 ? $this->normalizeBundleMessage($response->data->msg) : $response->data->msg);
                    }
                
            }

            elseif (!is_null($subcategory->description) && $subcategory->description == "SIMSERVER") {

                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->ss_product_code;
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->SIMServerBuy($plan_id, 1, $phone, $ref);
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                if (!isset($response) || is_null($response)) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } else if ($response['status'] == false) {
                    $response_msg =  null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    

                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->ss_product_code, $ref, $subcategory->telegram);


                    return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                } else {

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['data']['true_response'];
                    $updateOrder->save();
                    $msg = $response['data']['true_response'] . " SIMSERVER Ref: " . $response['data']['true_response'];
                    
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->ss_product_code, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['data']['true_response']);
                }
            }


            elseif (!is_null($subcategory->description) && $subcategory->description == "EGMS") {

                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->egms_plan_id;
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->EGMSPurchase($plan_id, 1, $phone, $ref);
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                if (!isset($response) || is_null($response)) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif ($response['status'] != "ok") {
                    $response_msg = null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->egms_plan_id, $ref, $subcategory->telegram);


                    return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                } else {

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['message'];
                    $updateOrder->save();
                    $msg = $response['message'] . " EGMS Ref: " . $response['message'];
                    
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->egms_plan_id, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['message']);
                }
            }

            elseif (!is_null($subcategory->description) && $subcategory->description == "AIRTIMENIGERIA") {

                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->aa_package_code;
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->purchaseAirtimeNigeria($plan_id, $amount, $phone, $ref);
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                \Log::info("AIRTIME NIGERIA");
                \Log::info(print_r($response, true));

                if (!isset($response) || is_null($response)) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif ($response['details']['order_status'] == "failed" || $response['status'] == 'failed') {
                    $response_msg = null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->aa_package_code, $ref, $subcategory->telegram);
                    $resp_msg = "Data is not available, please try again later";


                    return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed", "Transaction failed");
                } else {

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['details']['gateway_response'];
                    $updateOrder->save();
                    $msg = $response['details']['gateway_response'] . " AIRTIMENIGERIA Ref: " . $response['details']['reference'];
                    
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->aa_package_code, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['details']['gateway_response']);
                }
            }

            elseif (!is_null($subcategory->description) && $subcategory->description == "DATABAY") {

                $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->databaycode;
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->dataBayBuyData($plan_id, $phone);
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                if (!isset($response) || is_null($response)) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif ($response['success'] == false) {
                    $response_msg = null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->databaycode, $ref, $subcategory->telegram);


                    return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                } else {

                   
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['payload']['networkResponseMessage'];
                    $updateOrder->save();
                    $msg = $response['payload']['networkResponseMessage'] . " DATABAY Ref: " . $response['payload']['reference'];
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->databaycode, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['payload']['networkResponseMessage']);
                }
            }

            elseif (!is_null($subcategory->description) && $subcategory->description == "OGADAM") {

                $network_id = $this->parseOgaDamsNetworkID(strtolower($network));
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->ogadam_id;
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->ogaDamBuyData($network_id, $plan_id, $phone, $ref);

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                \Log::info("OGADAMS RESPONSE");
                \Log::info(print_r($response, true));

                // if (isset($response) and $response['data']['msg'] == "Unable to establish connection at the moment. Please try again later!. Your new balance is ₦NA.")
                if (!isset($response) || is_null($response)) {
                    $response_msg = isset($response['data']) ? explode(".", $response['data']['msg'])[0] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    

                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->ogadam_id, $ref, $subcategory->telegram);


                    return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");

                } elseif ($response['code'] == 424) {
                    if (!is_null($response['data']) && !is_null($response['data']['msg']) && stripos($response['data']['msg'] ,"Activation of Corporate_Data_Gifting was not successful") !== false) {
                        // $updateOrder = Order::where('ref', $ref)->first();
                        // $updateOrder->status = 0;
                        // $updateOrder->response = explode(".", $response['data']['msg'])[0];
                        // $updateOrder->save();
                        // $msg = $response['data']['msg'] . " OGADAM Ref: " . explode(".", $response['data']['msg'])[0];


                        // return $this->sendError2($ref, $custom_reference,"Transaction pending", "Transaction pending");

                        $response_msg = isset($response['data']) ? explode(".", $response['data']['msg'])[0] : null;
                    
                        $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                        
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->ogadam_id, $ref, $subcategory->telegram);


                        return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                    } elseif (!is_null($response['data']) && !is_null($response['data']['msg']) && stripos($response['data']['msg'], "not successful") > 0) {
                        if ($subcategory->title == "MTN SME") {
                            //         $updateOrder = Order::where('ref', $ref)->first();
                            //     $updateOrder->status = 0;
                            //     $updateOrder->response = explode(".",$response['data']['msg'])[0];
                            //     $updateOrder->save();
                            //     $msg = $response['data']['msg'] . " OGADAM Ref: " . explode(".",$response['data']['msg'])[0];


                            //    // $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->code, $ref, $subcategory->telegram, $msg);

                            //     // return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", explode(".",$response['data']['msg'])[0]);
                            //     return $this->sendError2($ref, $custom_reference,"Transaction pending", "Transaction pending");
                            
                            $response_msg = isset($response['data']) ? explode(".", $response['data']['msg'])[0] : null;
                    
                            $this->refundUser(
                                $ref,
                                $response_msg,
                                $amountActual,
                                [
                                    'bal' => $prev,
                                    'prev_bal' => $bal
                                ]
                            );
                    
                           
                            $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->ogadam_id, $ref, $subcategory->telegram);


                            return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");

                        } else {
                            $updateOrder = Order::where('ref', $ref)->first();
                            $updateOrder->status = 0;
                            $updateOrder->response = explode(".", $response['data']['msg'])[0];
                            $updateOrder->save();
                            $msg = $response['data']['msg'] . " OGADAM Ref: " . explode(".", $response['data']['msg'])[0];


                            $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->ogadam_id, $ref, $subcategory->telegram, $msg);

                            // return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", explode(".",$response['data']['msg'])[0]);
                            return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                        }


                    } elseif (!is_null($response['data']) && !is_null($response['data']['msg']) && $response['data']['msg'] == "There was an error while processing the request.") {
                        if ($subcategory->title == "MTN SME" || $subcategory->id == 2 || $subcategory->id == 47) {
                            //     $updateOrder = Order::where('ref', $ref)->first();
                            // $updateOrder->status = 1;
                            // $updateOrder->response = explode(".",$response['data']['msg'])[0];
                            // $updateOrder->save();
                            // $msg = $response['data']['msg'] . " OGADAM Ref: " . explode(".",$response['data']['msg'])[0];


                            // $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->code, $ref, $subcategory->telegram, $msg);

                            // return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", explode(".",$response['data']['msg'])[0]);
                            
                            $response_msg = isset($response['data']) ? explode(".", $response['data']['msg'])[0] : null;
                    
                            $this->refundUser(
                                $ref,
                                $response_msg,
                                $amountActual,
                                [
                                    'bal' => $prev,
                                    'prev_bal' => $bal
                                ]
                            );
                            
                            
                            $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->ogadam_id, $ref, $subcategory->telegram);


                            return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                        }

                    } elseif (!is_null($response['data']) && !is_null($response['data']['msg']) && $response['data']['msg'] == "Sorry! The system is temporarily unable to process your request. Please try after sometime") {
                        if ($subcategory->title == "MTN SME") {
                            //     $updateOrder = Order::where('ref', $ref)->first();
                            // $updateOrder->status = 1;
                            // $updateOrder->response = explode(".",$response['data']['msg'])[0];
                            // $updateOrder->save();
                            // $msg = $response['data']['msg'] . " OGADAM Ref: " . explode(".",$response['data']['msg'])[0];


                            // //$this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->code, $ref, $subcategory->telegram, $msg);

                            // return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", explode(".",$response['data']['msg'])[0]);
                            $response_msg = isset($response['data']) ? explode(".", $response['data']['msg'])[0] : null;
                    
                            $this->refundUser(
                                $ref,
                                $response_msg,
                                $amountActual,
                                [
                                    'bal' => $prev,
                                    'prev_bal' => $bal
                                ]
                            );
                            
                            $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->ogadam_id, $ref, $subcategory->telegram);


                            return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                        }
                    } elseif (!is_null($response['data']) && !is_null($response['data']['msg']) && $response['data']['msg'] == "An error occured. Try again later!") {
                        if ($subcategory->title == "MTN SME" || $subcategory->id == 2 || $subcategory->id == 47) {  
                            //     $updateOrder = Order::where('ref', $ref)->first();
                            // $updateOrder->status = 1;
                            // $updateOrder->response = explode(".",$response['data']['msg'])[0];
                            // $updateOrder->save();
                            // $msg = $response['data']['msg'] . " OGADAM Ref: " . explode(".",$response['data']['msg'])[0];


                            // //$this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->code, $ref, $subcategory->telegram, $msg);

                            // return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", explode(".",$response['data']['msg'])[0]);
                            $response_msg = isset($response['data']) ? explode(".", $response['data']['msg'])[0] : null;
                    
                            $this->refundUser(
                                $ref,
                                $response_msg,
                                $amountActual,
                                [
                                    'bal' => $prev,
                                    'prev_bal' => $bal
                                ]
                            );
                            
                            $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->ogadam_id, $ref, $subcategory->telegram);


                            return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                        }
                    } elseif (!is_null($response['data']) && !is_null($response['data']['msg']) && $response['data']['msg'] == "An error occurred. Try again later!") {
                        if ($subcategory->title == "MTN SME" || $subcategory->id == 2 || $subcategory->id == 47) {  
                            //     $updateOrder = Order::where('ref', $ref)->first();
                            // $updateOrder->status = 1;
                            // $updateOrder->response = explode(".",$response['data']['msg'])[0];
                            // $updateOrder->save();
                            // $msg = $response['data']['msg'] . " OGADAM Ref: " . explode(".",$response['data']['msg'])[0];


                            // //$this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->code, $ref, $subcategory->telegram, $msg);

                            // return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", explode(".",$response['data']['msg'])[0]);
                            $response_msg = isset($response['data']) ? explode(".", $response['data']['msg'])[0] : null;
                    
                            $this->refundUser(
                                $ref,
                                $response_msg,
                                $amountActual,
                                [
                                    'bal' => $prev,
                                    'prev_bal' => $bal
                                ]
                            );
                            
                            $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->ogadam_id, $ref, $subcategory->telegram);


                            return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                        }
                    } else {
                        $response_msg = isset($response['data']) ? explode(".", $response['data']['msg'])[0] : null;
                    
                        $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                        
                        
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->ogadam_id, $ref, $subcategory->telegram);
                        $resp_msg = explode(".", $response['data']['msg'])[0];


                        return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed", "Transaction failed");
                    }
                } else {
                    if (!is_null($response['data']) && !is_null($response['data']['msg']) && $response['data']['msg'] == "Activation of Corporate_Data_Gifting was not successful. Please try again.") {
                        // $updateOrder = Order::where('ref', $ref)->first();
                        // $updateOrder->status = 0;
                        // $updateOrder->response = explode(".", $response['data']['msg'])[0];
                        // $updateOrder->save();
                        // $msg = $response['data']['msg'] . " OGADAM Ref: " . explode(".", $response['data']['msg'])[0];


                        // return $this->sendError2($ref, $custom_reference,"Transaction pending", "Transaction pending");
                        $response_msg = isset($response['data']) ? explode(".", $response['data']['msg'])[0] : null;
                    
                        $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                        
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->ogadam_id, $ref, $subcategory->telegram);


                        return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                    } elseif (!is_null($response['data']) && !is_null($response['data']['msg']) && stripos($response['data']['msg'], "not successful") > 0) {
                        if ($subcategory->title == "MTN SME") {
                            //         $updateOrder = Order::where('ref', $ref)->first();
                            //     $updateOrder->status = 0;
                            //     $updateOrder->response = explode(".",$response['data']['msg'])[0];
                            //     $updateOrder->save();
                            //     $msg = $response['data']['msg'] . " OGADAM Ref: " . explode(".",$response['data']['msg'])[0];


                            //    // $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->code, $ref, $subcategory->telegram, $msg);

                            //     // return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", explode(".",$response['data']['msg'])[0]);
                            //     return $this->sendError2($ref, $custom_reference,"Transaction pending", "Transaction pending");
                                $response_msg = isset($response['data']) ? explode(".", $response['data']['msg'])[0] : null;
                                
                                $this->refundUser(
                                    $ref,
                                    $response_msg,
                                    $amountActual,
                                    [
                                        'bal' => $prev,
                                        'prev_bal' => $bal
                                    ]
                                );
                            
                            $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->ogadam_id, $ref, $subcategory->telegram);


                            return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");

                        } else {
                            $updateOrder = Order::where('ref', $ref)->first();
                            $updateOrder->status = 0;
                            $updateOrder->response = explode(".", $response['data']['msg'])[0];
                            $updateOrder->save();
                            $msg = $response['data']['msg'] . " OGADAM Ref: " . explode(".", $response['data']['msg'])[0];


                            $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->ogadam_id, $ref, $subcategory->telegram, $msg);

                            // return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", explode(".",$response['data']['msg'])[0]);
                            return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                        }


                    } elseif (!is_null($response['data']) && !is_null($response['data']['msg']) && $response['data']['msg'] == "There was an error while processing the request.") {
                        if ($subcategory->title == "MTN SME" || $subcategory->id == 2 || $subcategory->id == 47) {
                            //     $updateOrder = Order::where('ref', $ref)->first();
                            // $updateOrder->status = 1;
                            // $updateOrder->response = explode(".",$response['data']['msg'])[0];
                            // $updateOrder->save();
                            // $msg = $response['data']['msg'] . " OGADAM Ref: " . explode(".",$response['data']['msg'])[0];


                            // $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->code, $ref, $subcategory->telegram, $msg);

                            // return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", explode(".",$response['data']['msg'])[0]);
                            
                            $response_msg = isset($response['data']) ? explode(".", $response['data']['msg'])[0] : null;
                    
                            $this->refundUser(
                                $ref,
                                $response_msg,
                                $amountActual,
                                [
                                    'bal' => $prev,
                                    'prev_bal' => $bal
                                ]
                            );
                            
                            $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->ogadam_id, $ref, $subcategory->telegram);


                            return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                        }

                    } elseif (!is_null($response['data']) && !is_null($response['data']['msg']) && $response['data']['msg'] == "An error occured. Try again later!") {
                        if ($subcategory->title == "MTN SME" || $subcategory->id == 2 || $subcategory->id == 47) {
                            //     $updateOrder = Order::where('ref', $ref)->first();
                            // $updateOrder->status = 1;
                            // $updateOrder->response = explode(".",$response['data']['msg'])[0];
                            // $updateOrder->save();
                            // $msg = $response['data']['msg'] . " OGADAM Ref: " . explode(".",$response['data']['msg'])[0];


                            // //$this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->code, $ref, $subcategory->telegram, $msg);

                            // return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", explode(".",$response['data']['msg'])[0]);
                            $response_msg = isset($response['data']) ? explode(".", $response['data']['msg'])[0] : null;
                    
                            $this->refundUser(
                                $ref,
                                $response_msg,
                                $amountActual,
                                [
                                    'bal' => $prev,
                                    'prev_bal' => $bal
                                ]
                            );
                            
                            
                            $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->ogadam_id, $ref, $subcategory->telegram);


                            return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                        }
                    } elseif (!is_null($response['data']) && !is_null($response['data']['msg']) && $response['data']['msg'] == "An error occurred. Try again later!") {
                        if ($subcategory->title == "MTN SME" || $subcategory->id == 2 || $subcategory->id == 47) {
                            //     $updateOrder = Order::where('ref', $ref)->first();
                            // $updateOrder->status = 1;
                            // $updateOrder->response = explode(".",$response['data']['msg'])[0];
                            // $updateOrder->save();
                            // $msg = $response['data']['msg'] . " OGADAM Ref: " . explode(".",$response['data']['msg'])[0];


                            // //$this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->code, $ref, $subcategory->telegram, $msg);

                            // return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", explode(".",$response['data']['msg'])[0]);
                            $response_msg = isset($response['data']) ? explode(".", $response['data']['msg'])[0] : null;
                    
                            $this->refundUser(
                                $ref,
                                $response_msg,
                                $amountActual,
                                [
                                    'bal' => $prev,
                                    'prev_bal' => $bal
                                ]
                            );
                            
                            $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->ogadam_id, $ref, $subcategory->telegram);


                            return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                        }
                    } else {
                        
                        $updateOrder = Order::where('ref', $ref)->first();
                        $updateOrder->status = 1;
                        $updateOrder->response = $response['data']['msg'];
                        $updateOrder->save();
                        $msg = $response['data']['msg'] . " OGADAM Ref: " . $response['data']['msg'];
                        $this->processReferralRewards($updateOrder);


                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->ogadam_id, $ref, $subcategory->telegram, $msg);

                        return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['data']['msg']);
                    }


                }
            }


            elseif (!is_null($subcategory->description) && $subcategory->description == "AIRTELEDUSITE") {

                $network_id = $this->parseOgaDamsNetworkID(strtolower($network));
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->airtel_id;
                $request_body = $getSelectedProduct->airtel_plan;
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->AirtelEduBuyData($phone, $request_body);
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }
                
                \Log::info("AIRTEL EDUSUITE");
                \Log::info($response);


                if (isset($response)) {
                    $dstatus = $response['data'][0]['status'];
                    $desc = $response['data'][0]['description'];
                    $new_desc = explode('.', $desc)[0];
                }



                if (!isset($response) || is_null($response) || !isset($dstatus) || is_null($dstatus) || $dstatus == "error" || $desc == "Dear customer, your purchase was not successful. Please try again." || $dstatus == "InsufficientBalance") {
                    \Log::info("it failed Airtel");
                    $response_msg = null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->airtel_id, $ref, $subcategory->telegram);


                    return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");

                } elseif ($new_desc == "Invalid character after parsing property name") {
                    \Log::info("it was successful Airtel");

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $desc;
                    $updateOrder->save();
                    $msg = $desc . " AIRTEL EDUSUITE Ref: " . $desc;
                    
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->airtel_id, $ref, $subcategory->telegram);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $desc);

                    // return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } else {
                    \Log::info("it was successful Airtel");

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $desc;
                    $updateOrder->save();
                    $msg = $desc . " AIRTEL EDUSUITE Ref: " . $desc;
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->airtel_id, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $desc);
                }
            }


            elseif (!is_null($subcategory->description) && $subcategory->description == "GONGOZ") {

                $network_id = $this->parseOgaDamsNetworkID(strtolower($network));
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->gongzo_id;
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->gongozconceptData($network_id, $plan_id, $phone);
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                if (!isset($response) || is_null($response)) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif ($response['Status'] != 'successful') {
                    
                    $response_msg = null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->gongzo_id, $ref, $subcategory->telegram);


                    return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                } else {

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['apiresponse'];
                    $updateOrder->save();
                    $msg = $response['apiresponse'] . " GONGOZ Ref: " . $response['ident'];
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->gongzo_id, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['apiresponse']);
                }
            }


            elseif (!is_null($subcategory->description) && $subcategory->description == "AYINLAK") {

                $network_id = $this->ayinlakNetworkID(strtolower($network));
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->ayinlak_id;
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->ayinlakconnectData($network_id, $plan_id, $phone);
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }
                
                \Log::info("Ayinlak");
                \Log::info($response);

                if (!isset($response) || is_null($response)) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif ($response['Status'] != 'successful') {
                    $response_msg = null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->ayinlak_id, $ref, $subcategory->telegram);


                    return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");
                } elseif (isset($response['error'][0])) {
                    $response_msg = null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->ayinlak_id, $ref, $subcategory->telegram);

                    $resp_msg = "Data not available at the moment, check back later.";


                    return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed", "Transaction failed");
                } else {

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['api_response'];
                    $updateOrder->save();
                    $msg = $response['api_response'] . " AYINLAK Ref: " . $response['ident'];
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->ayinlak_id, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['api_response']);
                }
            }

            elseif (!is_null($subcategory->description) && $subcategory->description == "JONET") {

                $network_id = $this->parseOgaDamsNetworkID(strtolower($network));
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->jonet_code;
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->jonetData($plan_id, $phone, $ref);
                    \Log::info("JONET RESPONSE");
                    \Log::info(print_r($response, true));

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                if (!isset($response) || is_null($response) || $response['status'] == "Processing" || $response['responseCode'] == "201" || $response['responseCode'] == "202" || $response['responseCode'] == "203" || $response['responseCode'] == "JO105") {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif ($response['status'] == 'Failed' || $response['responseCode'] == "002" || $response['responseCode'] == "101" || $response['responseCode'] == "110" || $response['responseCode'] == "102" || $response['responseCode'] == "103" || $response['responseCode'] == "104" || $response['responseCode'] == "105" || $response['responseCode'] == "108" || $response['responseCode'] == "109" || $response['responseCode'] == "106" || $response['responseCode'] == "107" || $response['responseCode'] == "JO101" || $response['responseCode'] == "JO102" || $response['responseCode'] == "JO103" || $response['responseCode'] == "JO104" || $response['responseCode'] == "JO106" || $response['responseCode'] == "JO107" || $response['responseCode'] == "JO109" || $response['responseCode'] == "JO110" || $response['responseCode'] == "JO119") {
                    $response_msg = null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->jonet_code, $ref, $subcategory->telegram);

                    $resp_msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed", "Transaction failed");
                } else {

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['server_response'];
                    $updateOrder->save();
                    $msg = $response['server_response'] . " Jonet Ref: " . $response['customer_id'];
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->jonet_code, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['server_response']);
                }
            }

            elseif (!is_null($subcategory->description) && $subcategory->description == "AUTOPILOT") {

                $network_id = $this->parseAutoPilotNetworkID(strtolower($network));
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->autopilot_code;
                $datatype = $getSelectedProduct->autopilot_datatype;
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->autoPilotData($network_id, $datatype, $plan_id, $phone, $ref);
                    \Log::info("AUTOPILOT RESPONSE");
                    \Log::info(print_r($response, true));

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                if (!isset($response) || is_null($response) || $response['code'] == 201  || $response['code'] == 500 ) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif ($response['code'] == 424) {
                    $response_msg = isset($response['data']['message']) ? $response['data']['message'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                                    
                    $msg = $response['data']['message'];
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->autopilot_code, $ref, $subcategory->telegram, $msg);

                    $resp_msg = "Data is not available,please try again later";



                    return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } else {

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['data']['message'];
                    $updateOrder->save();
                    $msg = $response['data']['message'] . " Autopilot Ref: " . $response['data']['reference'];
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->autopilot_code, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['data']['message']);
                }
            }

            elseif (!is_null($subcategory->description) && $subcategory->description == "AUTOPILOTAWUF") {

                $network_id = $this->parseAutoPilotNetworkID(strtolower($network));
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->autopilot_code;
                $datatype = $getSelectedProduct->autopilot_datatype;
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->autoPilotAwuf($network_id, $datatype, $plan_id, $phone, $ref);
                    \Log::info("AUTOPILOTAwuf RESPONSE");
                    \Log::info(print_r($response, true));

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                if (!isset($response) || is_null($response) || $response['code'] == 201  || $response['code'] == 500) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif ($response['code'] == 424) {
                    $response_msg = isset($response['data']['message']) ? $response['data']['message'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    $msg = $response['data']['message'];
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->autopilot_code, $ref, $subcategory->telegram, $msg);

                    $resp_msg = "Data is not available,please try again later";


                      if($response['data']['message'] == "You may have exhaust all sender number limit"){
                            Subcategory::find(48)->update(['status' => 0]);
                      }




                    return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
                } else {

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['data']['message'];
                    $updateOrder->save();

                    $msg1 = $response['data']['message'];

                    $msg = $msg1 . " Autopilot Ref: " . $response['data']['reference'];
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->autopilot_code, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $msg1);
                }
            }

            elseif (!is_null($subcategory->description) && $subcategory->description == "AUTOSYNCAWUF") {
                
                $network_id = $this->parseAutoPilotNetworkID(strtolower($network));
                
                $amaka_network_id = $this->parseOgaDamsNetworkID(strtolower($network));
                $plan_id = isset($getSelectedProduct->amakasub_plan_id) ? $getSelectedProduct->amakasub_plan_id : null;
                
                $sim_server_network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                $sim_server_plan_id = isset($getSelectedProduct->ss_product_code) ? $getSelectedProduct->ss_product_code : null;
                
                $afri_network_id = isset($getSelectedProduct->afri_network_id) ? $getSelectedProduct->afri_network_id : null;
                $afri_plan_id = isset($getSelectedProduct->afri_plan_id) ? $getSelectedProduct->afri_plan_id : null;
                
                $product_id = $getSelectedProduct->product_id;

                $variation_code = $getSelectedProduct->variation_code;
   
                $pin = $getSelectedProduct->pin;
               
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amountActual;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();
                
                
                if($subcategory->id == 2 && strtolower($getSelectedProduct->plan) == strtolower("GET 1GB FOR 1 DAY(works for Debtors)")){
                    
                    try {
                        $response = $this->amakasubPortal($amaka_network_id, $phone, $plan_id, $ref);
    
                    } catch (\Throwable $e) {
                        return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                    }
    
                    \Log::info("AMAKASUB PORTAL RESPONSE UNDER AUTOSYNCAWUF");
                    \Log::info($response);
    
                    if (isset($response) && isset($response['status']) && $response['status'] == "error") {
                      
                        $response_msg = isset($response['message']) ? $response['message'] : '';
                        
                         $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                       
    
                        $msg = isset($response['message']) ? $response['message'] : '';
                        
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->amakasub_plan_id, $ref, $subcategory->telegram);
    
                        return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
    
                    } elseif (!isset($response) || is_null($response) || $response['status'] == "pending" || $response['status'] == "processing") {
    
                        return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
    
                    } elseif (isset($response) && $response['status'] == "success") {
    
                        
                        $updateOrder = Order::where('ref', $ref)->first();
                        $updateOrder->status = 1;
                        $expiryDateTime = now()->addDay();

                        $msg = sprintf(
                            'Activation of 1GB Smart Daily Bundle was successful and will expire on %s.',
                            $expiryDateTime->format('d/m/Y h:i A')
                        );
                        $updateOrder->response = isset($msg) ? $msg : '';
                        $updateOrder->save();
                        $msg = $msg;
                        $this->processReferralRewards($updateOrder);
    
    
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->amakasub_plan_id, $ref, $subcategory->telegram, $msg);
    
                        return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $msg);
                    }
                
                }elseif($subcategory->id == 2 && strtolower($getSelectedProduct->plan) == strtolower("GET 2.5GB FOR 1 DAY")){
                   try {
                        $response = $this->afriPortal($afri_network_id, $phone, $afri_plan_id,$ref);
                    } catch (\Throwable $e) {
                        return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                    }
    
                    \Log::info("AFRI PORTAL RESPONSE");
                    \Log::info($response);
    
                    if (isset($response) && isset($response['Status']) && $response['Status'] == "failed") {
                      
                        $response_msg = isset($response['api_response']) ? $response['api_response'] : null;
                        
                         $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                       
    
                        $msg = isset($response['api_response']) ? $response['api_response'] : null ;
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->afri_plan_id, $ref, $subcategory->telegram);
    
                        return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
    
                    } elseif (isset($response) && isset($response['error'][0])) {
                        $response_msg = isset($response['api_response']) ? $response['api_response'] : null ;
                        
                         $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                        
                        $msg = isset($response['api_response']) ? $response['api_response'] : null ;
                        
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->afri_plan_id, $ref, $subcategory->telegram);
    
    
                        return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
    
                    } elseif (!isset($response) || is_null($response) || $response['Status'] == "pending" || $response['Status'] == "processing") {
    
                        return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
    
                    } elseif (isset($response) && $response['Status'] == "successful") {
    
                        
                        $updateOrder = Order::where('ref', $ref)->first();
                        $updateOrder->status = 1;
                        $updateOrder->response = isset($response['api_response']) ? $response['api_response'] : null ;
                        $updateOrder->save();
                        $msg = isset($response['api_response']) ? $response['api_response'] : null ;
                        $this->processReferralRewards($updateOrder);
    
    
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->afri_plan_id, $ref, $subcategory->telegram, $msg);
    
                        return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $msg);
                    }
                
                }elseif($subcategory->id == 2 && strtolower($getSelectedProduct->plan) == strtolower("GET 5GB FOR 14 DAYS")){
                    try {
                        $response = $this->afriPortal($afri_network_id, $phone, $afri_plan_id,$ref);
                    } catch (\Throwable $e) {
                        return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                    }
    
                    \Log::info("AFRI PORTAL RESPONSE");
                    \Log::info($response);
    
                    if (isset($response) && isset($response['Status']) && $response['Status'] == "failed") {
                      
                        $response_msg = isset($response['api_response']) ? $response['api_response'] : null;
                        
                         $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                       
    
                        $msg = isset($response['api_response']) ? $response['api_response'] : null ;
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->afri_plan_id, $ref, $subcategory->telegram);
    
                        return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
    
                    } elseif (isset($response) && isset($response['error'][0])) {
                        $response_msg = isset($response['api_response']) ? $response['api_response'] : null ;
                        
                         $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                        
                        $msg = isset($response['api_response']) ? $response['api_response'] : null ;
                        
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->afri_plan_id, $ref, $subcategory->telegram);
    
    
                        return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
    
                    } elseif (!isset($response) || is_null($response) || $response['Status'] == "pending" || $response['Status'] == "processing") {
    
                        return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
    
                    } elseif (isset($response) && $response['Status'] == "successful") {
    
                        
                        $updateOrder = Order::where('ref', $ref)->first();
                        $updateOrder->status = 1;
                        $updateOrder->response = isset($response['api_response']) ? $response['api_response'] : null ;
                        $updateOrder->save();
                        $msg = isset($response['api_response']) ? $response['api_response'] : null ;
                        
                        $this->processReferralRewards($updateOrder);
    
    
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->afri_plan_id, $ref, $subcategory->telegram, $msg);
    
                        return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $msg);
                    }
                
                }else{

                    try {
                        $response = $this->autoSyncPortal($ref,$phone,$product_id,$variation_code,$pin);
    
                    } catch (\Throwable $e) {
                        return $this->sendProviderConnectionPending(
                            $e,
                            $ref,
                            $custom_reference
                        );
                    }
    
    
                    \Log::info("AUTOSYNCAwuf RESPONSE");
                    \Log::info(print_r($response, true));
    
                    if (!isset($response) || is_null($response)) {
    
                        return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
    
                    }elseif (isset($response['data']) && isset($response['data']['transaction']) && $response['data']['transaction']['status'] == "pending"){
                        return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
                    }elseif (isset($response['data']) && isset($response['data']['transaction']) && $response['data']['transaction']['status'] == "failed") {
                        $response_msg = isset($response['data']['transaction']['details']) ? $response['data']['transaction']['details'] : null;
                        
                        $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                        
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->variation_code, $ref, $subcategory->telegram);
    
                        $resp_msg = $response_msg;
    
                        
                        return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed: " . $resp_msg, "Transaction failed: " . $resp_msg);
                    }elseif (isset($response['data']) && isset($response['data']['transaction']) && $response['data']['transaction']['details'] == "No Memo..") {
                        $response_msg = isset($response['data']['transaction']['details']) ? $response['data']['transaction']['details'] : null;
                        
                        $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                        
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->variation_code, $ref, $subcategory->telegram);
    
                        $resp_msg = "Data is not available,please try again later";
    
    
                        return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed: " . $resp_msg, "Transaction failed: " . $resp_msg);
                    }elseif(isset($response['status']) && $response['status'] == "error"){
                        $response_msg = isset($response['message']) ? $response['message'] : null;
                        
                        $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                        
    
                        $msg = $response['message'];
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->variation_code, $ref, $subcategory->telegram, $msg);
    
                        $resp_msg = "Data is not available,please try again later";
    
                        //   if($response['data']['message'] == "You may have exhaust all sender number limit"){
                        //         Subcategory::find(48)->update(['status' => 0]);
                        //   }
    
    
    
                        return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
                    }elseif (isset($response['data']) && isset($response['data']['transaction']) && $response['data']['transaction']['status'] == "successful") {
    
                        
                        $updateOrder = Order::where('ref', $ref)->first();
                        $updateOrder->status = 1;
                        $updateOrder->response = $response['data']['transaction']['details'];
                        $updateOrder->save();
                        $msg1 = $response['data']['transaction']['details'];
                        $msg = $msg1 . " AutoSync Ref: " . $response['data']['transaction']['reference'];
                        $this->processReferralRewards($updateOrder);
    
    
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->variation_code, $ref, $subcategory->telegram, $msg);
    
                        return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $msg1);
                    }
            
                }
                }

            elseif (!is_null($subcategory->description) && $subcategory->description == "TBCHPORTAL") {

                $network_id = $this->parseTBCHPortalID(strtolower($network));
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->tbch_code;
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->tbchPortal($network_id, $plan_id, $phone, $ref);

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                \Log::info("TBCH PORTAL RESPONSE");
                \Log::info(print_r($response, true));

                if ($response['code'] == 109 || $response['code'] == 110 || $response['code'] == 111 || $response['code'] == 112 || $response['code'] == 113 || $response['code'] == 114 || $response['code'] == 115 || $response['code'] == 116 || $response['code'] == 120 || $response['code'] == 121 || $response['code'] == 122 || $response['code'] == 310 || $response['code'] == 311 || $response['code'] == 401 || $response['code'] == 402 || $response['code'] == 601) {
                    $response_msg = isset($response['message']) ? $response['message'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    

                    $msg = $response['message'];
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->tbch_code, $ref, $subcategory->telegram, $msg);

                    $resp_msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (!isset($response) || is_null($response) || $response['data']['status'] == "Pending" || $response['data']['status'] == "Processing") {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif ($response['code'] == "00" && $response['data']['status'] == "Successful") {

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['data']['gateway_response'];
                    $updateOrder->save();
                    $msg = $response['data']['gateway_response'] . " TbchPortal Ref: " . $response['data']['customer_ref'];
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->tbch_code, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['data']['gateway_response']);
                }
            }

            elseif (!is_null($subcategory->description) && $subcategory->description == "SUBARENA") {

                $network_id = $getSelectedProduct->subarena_network_id;
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->subarena_plan_id;
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amountActual;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->subArenaPortal($network_id, $phone,$plan_id);

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                \Log::info("SUBARENA PORTAL RESPONSE");
                \Log::info($response);

                 if (isset($response) && isset($response['Status']) && $response['Status'] != "successful") {
                     $response_msg = null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    
                   // $msg = $response['data']['gateway_response'];
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->subarena_plan_id, $ref, $subcategory->telegram);

                    $msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response) && isset($response['error'][0])) {
                    $response_msg = null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    

                   // $msg = $response['data']['gateway_response'];
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->subarena_plan_id, $ref, $subcategory->telegram);

                    $msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (!isset($response) || is_null($response) || $response['Status'] == "pending" || $response['Status'] == "processing") {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif (isset($response) && $response['Status'] == "successful") {

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['api_response'];
                    $updateOrder->save();
                    $msg = $response['api_response'] . " Subarena Ref: " . $response['id'];
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->subarena_plan_id, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['api_response']);
                }
            }

            elseif (!is_null($subcategory->description) && $subcategory->description == "TOPUPACCESS") {

                // $network_id = $getSelectedProduct->subarena_network_id;
                // $plan_id = $getSelectedProduct->code;
                $topup_access_pin = $getSelectedProduct->topup_access_pin;
                $plan_id = $getSelectedProduct->topup_access_id;

                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amountActual;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->topupAccessPortal($plan_id, $topup_access_pin,$phone);

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                \Log::info("TOPUP ACCESS PORTAL RESPONSE");
                \Log::info($response);

                if (isset($response) && isset($response['status']) && $response['code'] != 200 && $response['status'] == "failed") {
                    $response_msg = null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    
                   // $msg = $response['data']['gateway_response'];
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->topup_access_id, $ref, $subcategory->telegram);

                    $msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response) && isset($response['status']) && $response['code'] == 200 && $response['status'] == "reversed") {
                    $response_msg = isset($response['remark']) ? $response['remark'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                   

                    $msg = $response['remark'];
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->topup_access_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (!isset($response) || is_null($response)) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif (isset($response) && $response['code'] == 200 && $response['status'] == "success") {

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['description'];
                    $updateOrder->save();
                    $msg = $response['description'] . " TopupAccess Ref: " . $response['reference'];
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->topup_access_id, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['description']);
                }
            }
            elseif (!is_null($subcategory->description) && $subcategory->description == "MTNNG") {

                // $network_id = $getSelectedProduct->subarena_network_id;
                // $plan_id = $getSelectedProduct->code;
                $pin = $getSelectedProduct->mtn_pin;
                $share_id = $getSelectedProduct->mtn_share_id;

                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amountActual;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->buyDataSme($phone,$share_id,$pin);

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                \Log::info("MTN PORTAL RESPONSE");
                \Log::info($response);

                if (isset($response['message']) && $response['message'] == "Session Not Found" && $response['status'] == 401) {
                    $response_msg = !is_null($response['message']) ? $response['message'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    

                    $msg = !is_null($response['message']) ? $response['message'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->mtn_share_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['message']) && $response['message'] == "Valid session" && $response['status'] == 1) {
                    $response_msg = !is_null($response['message']) ? $response['message'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    

                    $msg = !is_null($response['message']) ? $response['message'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->mtn_share_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (!isset($response['message']) && count($response) == 1 && isset($response['version']) && $response['version'] == "Patch") {
                    $response_msg = !is_null($response['version']) ? $response['version'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                   

                    $msg = !is_null($response['version']) ? $response['version'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->mtn_share_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response) && count($response) == 1 && is_null($response[0])) {
                    $response_msg = "Null response returned";
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    

                    $msg = "Null response returned";
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->mtn_share_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response[0]['RespDescription']) && stripos($response[0]['RespDescription'], 'activation') !== false || stripos($response[0]['RespDescription'],"do not have an active") !== false || stripos($response[0]['RespDescription'],"purchase a data share bundle") !== false || stripos($response[0]['RespDescription'],"temporarily unable to process your request") !== false || stripos($response[0]['RespDescription'],"issue with the token") !== false || stripos($response[0]['RespDescription'],"internal processing error") !== false || stripos($response[0]['RespDescription'],"was not successful") !== false || stripos($response[0]['RespDescription'],"invalid share pin") !== false || stripos($response[0]['RespDescription'],"no healthy upstream") !== false || stripos($response[0]['RespDescription'],"error while processing the request") !== false || stripos($response[0]['RespDescription'],"not Active on CLM") !== false || stripos($response[0]['RespDescription'],"unable to process your request") !== false || stripos($response[0]['RespDescription'],"internal server error") !== false || stripos($response[0]['RespDescription'],"Datashare is insufficient") !== false || stripos($response[0]['RespDescription'],"upstream connect error") !== false || stripos($response[0]['RespDescription'],"Connection refused") !== false || stripos($response[0]['RespDescription'],"connect timeout") !== false || stripos($response[0]['RespDescription'],"Empty response from server") !== false || stripos($response[0]['RespDescription'],"unknown error occurred") !== false) {
                    $response_msg = !is_null($response[0]['RespDescription']) ? $response[0]['RespDescription'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                   

                    $msg = !is_null($response[0]['RespDescription']) ? $response[0]['RespDescription'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->mtn_share_id, $ref, $subcategory->telegram, $msg);

                    // $msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (stripos($response[0]['RespDescription'], 'other technical') !== false ) {
                    $response_msg = !is_null($response[0]['RespDescription']) ? $response[0]['RespDescription'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                   
                    $msg = !is_null($response[0]['RespDescription']) ? $response[0]['RespDescription'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->mtn_share_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response[0]['Status']) && $response[0]['Status'] == "FAILURE") {
                    $response_msg = !is_null($response[0]['RespDescription']) ? $response[0]['RespDescription'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                   

                    $msg = !is_null($response[0]['RespDescription']) ? $response[0]['RespDescription'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->mtn_share_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (!isset($response) || is_null($response)) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif (isset($response[0]['Status']) && $response[0]['Status'] === 'SUCCESS' && $response[0]['ResponseCode'] == '0') {

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response[0]['ResponseData']['ProductBuy']['Notification'];
                    $updateOrder->save();
                    $msg = $response[0]['ResponseData']['ProductBuy']['Notification'] . " MTNNG Ref: " . $response[0]['RequestId'] . " SWIFTLINK REF: " . $ref;
                    $this->processReferralRewards($updateOrder);

                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->mtn_share_id, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response[0]['ResponseData']['ProductBuy']['Notification']);
                }
            }
            
            elseif (!is_null($subcategory->description) && $subcategory->description == "MTNAPP") {

                // $network_id = $getSelectedProduct->subarena_network_id;
                // $plan_id = $getSelectedProduct->code;
                $pin = $getSelectedProduct->mtn_pin;
                $share_id = $getSelectedProduct->mtn_share_id;

                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amountActual;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->buyDataSmeApp($phone,$share_id,$pin);

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                \Log::info("MTN PORTAL APP RESPONSE");
                \Log::info($response);
                if (!isset($response) || is_null($response)) {
                    $response_msg = null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    
                    $msg = '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->mtn_share_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['message']) && $response['message'] == "Session Not Found" && $response['status'] == "1112") {
                    $response_msg = !is_null($response['message']) ? $response['message'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    

                    $msg = !is_null($response['message']) ? $response['message'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->mtn_share_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['message']) && $response['message'] == "Valid session" && $response['status'] == "1112") {
                    $response_msg = !is_null($response['message']) ? $response['message'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    
                    $msg = !is_null($response['message']) ? $response['message'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->mtn_share_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
                    

                } elseif (isset($response) && !is_array($response)) {
                    if (stripos($response, 'activation') !== false || stripos($response,"do not have an active") !== false || stripos($response,"purchase a data share bundle") !== false || stripos($response,"temporarily unable to process your request") !== false || stripos($response,"issue with the token") !== false || stripos($response,"internal processing error") !== false || stripos($response,"was not successful") !== false || stripos($response,"invalid share pin") !== false || stripos($response,"no healthy upstream") !== false || stripos($response,"error while processing the request") !== false || stripos($response,"not Active on CLM") !== false || stripos($response,"unable to process your request") !== false || stripos($response,"internal server error") !== false || stripos($response,"Datashare is insufficient") !== false || stripos($response,"upstream connect error") !== false || stripos($response,"Connection refused") !== false || stripos($response,"connect timeout") !== false || stripos($response,"Empty response from server") !== false || stripos($response,"unknown error occurred") !== false || stripos($response,"barred due to") !== false || stripos($response,"you are not sending to valid") !== false || stripos($response,"you don't have sufficient") !== false || stripos($response,"connect timeout") !== false || stripos($response,"failed to connect") !== false || stripos($response,"other technical") !== false) {
                    $response_msg = !is_null($response) ? $response : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                   

                    $msg = !is_null($response) ? $response : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->mtn_share_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
                    }

                } elseif (isset($response['message'])) {
                    if(stripos($response['message'], 'activation') !== false || stripos($response['message'],"do not have an active") !== false || stripos($response['message'],"purchase a data share bundle") !== false || stripos($response['message'],"temporarily unable to process your request") !== false || stripos($response['message'],"issue with the token") !== false || stripos($response['message'],"internal processing error") !== false || stripos($response['message'],"was not successful") !== false || stripos($response['message'],"invalid share pin") !== false || stripos($response['message'],"no healthy upstream") !== false || stripos($response['message'],"error while processing the request") !== false || stripos($response['message'],"not Active on CLM") !== false || stripos($response['message'],"unable to process your request") !== false || stripos($response['message'],"internal server error") !== false || stripos($response['message'],"Datashare is insufficient") !== false || stripos($response['message'],"upstream connect error") !== false || stripos($response['message'],"Connection refused") !== false || stripos($response['message'],"connect timeout") !== false || stripos($response['message'],"Empty response from server") !== false || stripos($response['message'],"unknown error occurred") !== false || stripos($response['message'],"barred") !== false  || stripos($response['message'],"exception occured") !== false  || stripos($response['message'],"meant to happen") !== false || stripos($response['message'],"Other Technical Error") !== false || stripos($response['message'],"phone number field is not blank") !== false) {
                    $response_msg = !is_null($response['message']) ? $response['message'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    

                    $msg = !is_null($response['message']) ? $response['message'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->mtn_share_id, $ref, $subcategory->telegram, $msg);

                    // $msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
                    }

                } elseif (isset($response['message']) && stripos($response['message'], 'Other Technical Error') !== false ) {
                    $response_msg = !is_null($response['message']) ? $response['message'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    
                    $msg = !is_null($response['message']) ? $response['message'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->mtn_share_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
                    

                } elseif (isset($response['message']) && stripos($response['message'], 'phone number field is not blank') !== false ) {
                    $response_msg = !is_null($response['message']) ? "recipient's number is barred due to NIN. Ask the recipient to link number at https://nin.mtn.ng/nin/ or visit any MTN store for assistance." : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    
                    $msg = !is_null($response['message']) ? "recipient's number is barred due to NIN. Ask the recipient to link number at https://nin.mtn.ng/nin/ or visit any MTN store for assistance." : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->mtn_share_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
                    
                } elseif (isset($response['status']) && $response['status'] == "1112") {
                    $response_msg = !is_null($response['message']) ? $response['message'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                   

                    $msg = !is_null($response['message']) ? $response['message'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->mtn_share_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['status']) && $response['status'] == "3016") {
                     $response_msg = !is_null($response['message']) ? $response['message'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    $msg = !is_null($response['message']) ? $response['message'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->mtn_share_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['status']) && $response['status'] == "3001") {
                    $response_msg = !is_null($response['message']) ? $response['message'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    $msg = !is_null($response['message']) ? $response['message'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->mtn_share_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['statusCode']) && $response['statusCode'] == '0000') {

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['data']['notification'];
                    $updateOrder->save();
                    $msg = $response['data']['notification'] . " MTNNG Ref: " . $response['transactionId'] . " SWIFTLINK REF: " . $ref;
                    $this->processReferralRewards($updateOrder);

                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->mtn_share_id, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['data']['notification']);
                }
            }
            
            elseif (!is_null($subcategory->description) && $subcategory->description == "MTNDIRECT") {
                if($subcategory->id == 50 && $plan == "GET 2.5GB for 1 Day") {
                    $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                    $plan_id = $getSelectedProduct->smeplug_id;
                    
                    $order = new Order();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->subcategory_id = $subcategory->id;
                    $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
                    $order->amount = $amountActual;
                    $order->quantity = 1;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                    $order->api_route = $subcategory->description;
                    $order->subtotal = $amountActual;
                    $order->total = $amountActual;
                    $order->phone = $phone;
                    $order->bal = $bal;
                    $order->prev_bal = $prev;
                    $order->description = $subcategory->title . " " . $plan .
                        " at N" . $amountActual;
                    $order->status = 0;
                    $order->save();
                
    
                try {
                    $response = $this->SMEPlugApi($network_id, $plan_id, $phone, $ref);
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }
                
                \Log::info("SMEPLUG PURCHASE DATA");
                \Log::info($phone);
                \Log::info($ref);
                \Log::info($subcategory->title);

                
                \Log::info("SMEPLUG RESPONSE");
                
                \Log::info(print_r($response,true));
                
                
                if(!isset($response) || is_null($response) || !isset($response->status) || is_null($response->status) || empty($response->status)){
                     return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
                    
                }elseif(isset($response->status) && $response->status == true && isset($response->data->current_status) && $response->data->current_status == "processing"){
                     return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
                    
                }elseif(isset($response->msg) && $response->msg == "Plan cannot be dispensed via wallet at this time"){
                    $response_msg = isset($response['msg']) ? $response['msg'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    
                    return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");

                    
                }elseif(isset($response->status) && $response->status == true && isset($response->data->current_status) && $response->data->current_status == "success") {

    
                        
    
                        if (stripos($response->data->msg, "not successful") > 0) {
                            $updateOrder = Order::where('ref', $ref)->first();
    
                            $updateOrder->status = 0;
                            $updateOrder->response = $response->data->msg;
                            $updateOrder->save();
                            $msg = $response->data->msg . " SMEPLUG Ref: " . $response->data->reference;
    
                        } else {
                            $updateOrder = Order::where('ref', $ref)->first();
    
                            $updateOrder->status = 1;
                            $updateOrder->response = $response->data->msg;
                            $updateOrder->save();
                            $msg = $response->data->msg . " SMEPLUG Ref: " . $response->data->reference;
                            $this->processReferralRewards($updateOrder);
    
                        }
    
                          $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->smeplug_id, $ref, $subcategory->telegram, $msg);
    
    
                        return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response->data->msg);
                    }
                }
                elseif($subcategory->id == 50 && $plan == "GET 20GB for 7 Days") {
                    $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                    $plan_id = $getSelectedProduct->smeplug_id;
                    
                    $order = new Order();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->subcategory_id = $subcategory->id;
                    $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
                    $order->amount = $amountActual;
                    $order->quantity = 1;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                    $order->api_route = $subcategory->description;
                    $order->subtotal = $amountActual;
                    $order->total = $amountActual;
                    $order->phone = $phone;
                    $order->bal = $bal;
                    $order->prev_bal = $prev;
                    $order->description = $subcategory->title . " " . $plan .
                        " at N" . $amountActual;
                    $order->status = 0;
                    $order->save();
                
    
                try {
                    $response = $this->SMEPlugApi($network_id, $plan_id, $phone, $ref);
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }
                
                \Log::info("SMEPLUG PURCHASE DATA");
                \Log::info($phone);
                \Log::info($ref);
                \Log::info($subcategory->title);

                
                \Log::info("SMEPLUG RESPONSE");
                
                \Log::info(print_r($response,true));
                
                
                if(!isset($response) || is_null($response) || !isset($response->status) || is_null($response->status) || empty($response->status)){
                     return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
                    
                }elseif(isset($response->status) && $response->status == true && isset($response->data->current_status) && $response->data->current_status == "processing"){
                     return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
                    
                }elseif(isset($response->msg) && $response->msg == "Plan cannot be dispensed via wallet at this time"){
                    $response_msg = isset($response['msg']) ? $response['msg'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    
                    return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");

                    
                }elseif(isset($response->status) && $response->status == true && isset($response->data->current_status) && $response->data->current_status == "success") {

    
                        
    
                        if (stripos($response->data->msg, "not successful") > 0) {
                            $updateOrder = Order::where('ref', $ref)->first();
    
                            $updateOrder->status = 0;
                            $updateOrder->response = $response->data->msg;
                            $updateOrder->save();
                            $msg = $response->data->msg . " SMEPLUG Ref: " . $response->data->reference;
    
                        } else {
                            $updateOrder = Order::where('ref', $ref)->first();
    
                            $updateOrder->status = 1;
                            $updateOrder->response = $response->data->msg;
                            $updateOrder->save();
                            $msg = $response->data->msg . " SMEPLUG Ref: " . $response->data->reference;
                            $this->processReferralRewards($updateOrder);
    
                        }
    
                          $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->smeplug_id, $ref, $subcategory->telegram, $msg);
    
    
                        return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response->data->msg);
                    }
                }
                elseif($subcategory->id == 25 && $plan == "GET 2.5GB for 1 Day") {
                    $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                    $plan_id = $getSelectedProduct->smeplug_id;
                    
                    $order = new Order();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->subcategory_id = $subcategory->id;
                    $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
                    $order->amount = $amountActual;
                    $order->quantity = 1;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                    $order->api_route = $subcategory->description;
                    $order->subtotal = $amountActual;
                    $order->total = $amountActual;
                    $order->phone = $phone;
                    $order->bal = $bal;
                    $order->prev_bal = $prev;
                    $order->description = $subcategory->title . " " . $plan .
                        " at N" . $amountActual;
                    $order->status = 0;
                    $order->save();
                
    
                try {
                    $response = $this->SMEPlugApi($network_id, $plan_id, $phone, $ref);
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }
                
                \Log::info("SMEPLUG PURCHASE DATA");
                \Log::info($phone);
                \Log::info($ref);
                \Log::info($subcategory->title);

                
                \Log::info("SMEPLUG RESPONSE");
                
                \Log::info(print_r($response,true));
                
                
                    if(!isset($response) || is_null($response) || !isset($response->status) || is_null($response->status) || empty($response->status)){
                     return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
                    
                }elseif(isset($response->status) && $response->status == true && isset($response->data->current_status) && $response->data->current_status == "processing"){
                     return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
                    
                }elseif(isset($response->msg) && $response->msg == "Plan cannot be dispensed via wallet at this time"){
                    $response_msg = isset($response['msg']) ? $response['msg'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    
                    return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");

                    
                }elseif(isset($response->status) && $response->status == true && isset($response->data->current_status) && $response->data->current_status == "success") {

    
                        
    
                        if (stripos($response->data->msg, "not successful") > 0) {
                            $updateOrder = Order::where('ref', $ref)->first();
    
                            $updateOrder->status = 0;
                            $updateOrder->response = $response->data->msg;
                            $updateOrder->save();
                            $msg = $response->data->msg . " SMEPLUG Ref: " . $response->data->reference;
    
                        } else {
                            $updateOrder = Order::where('ref', $ref)->first();
    
                            $updateOrder->status = 1;
                            $updateOrder->response = $response->data->msg;
                            $updateOrder->save();
                            $msg = $response->data->msg . " SMEPLUG Ref: " . $response->data->reference;
                            $this->processReferralRewards($updateOrder);
    
                        }
    
                          $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->smeplug_id, $ref, $subcategory->telegram, $msg);
    
    
                        return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response->data->msg);
                    }
                }
                elseif($subcategory->id == 25 && $plan == "GET 20GB for 7 Days") {
                    $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                    $plan_id = $getSelectedProduct->smeplug_id;
                    
                    $order = new Order();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->subcategory_id = $subcategory->id;
                    $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
                    $order->amount = $amountActual;
                    $order->quantity = 1;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                    $order->api_route = $subcategory->description;
                    $order->subtotal = $amountActual;
                    $order->total = $amountActual;
                    $order->phone = $phone;
                    $order->bal = $bal;
                    $order->prev_bal = $prev;
                    $order->description = $subcategory->title . " " . $plan .
                        " at N" . $amountActual;
                    $order->status = 0;
                    $order->save();
                
    
                try {
                    $response = $this->SMEPlugApi($network_id, $plan_id, $phone, $ref);
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }
                
                \Log::info("SMEPLUG PURCHASE DATA");
                \Log::info($phone);
                \Log::info($ref);
                \Log::info($subcategory->title);

                
                \Log::info("SMEPLUG RESPONSE");
                
                \Log::info(print_r($response,true));
                
                
                    if(!isset($response) || is_null($response) || !isset($response->status) || is_null($response->status) || empty($response->status)){
                     return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
                    
                }elseif(isset($response->status) && $response->status == true && isset($response->data->current_status) && $response->data->current_status == "processing"){
                     return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
                    
                }elseif(isset($response->msg) && $response->msg == "Plan cannot be dispensed via wallet at this time"){
                    $response_msg = isset($response['msg']) ? $response['msg'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");

                    
                }elseif(isset($response->status) && $response->status == true && isset($response->data->current_status) && $response->data->current_status == "success") {

    
                        
    
                        if (stripos($response->data->msg, "not successful") > 0) {
                            $updateOrder = Order::where('ref', $ref)->first();
    
                            $updateOrder->status = 0;
                            $updateOrder->response = $response->data->msg;
                            $updateOrder->save();
                            $msg = $response->data->msg . " SMEPLUG Ref: " . $response->data->reference;
    
                        } else {
                            $updateOrder = Order::where('ref', $ref)->first();
    
                            $updateOrder->status = 1;
                            $updateOrder->response = $response->data->msg;
                            $updateOrder->save();
                            $msg = $response->data->msg . " SMEPLUG Ref: " . $response->data->reference;
                            $this->processReferralRewards($updateOrder);
    
                        }
    
                          $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->smeplug_id, $ref, $subcategory->telegram, $msg);
    
    
                        return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response->data->msg);
                    }
                }
                else{
                    // $network_id = $getSelectedProduct->subarena_network_id;
                // $plan_id = $getSelectedProduct->code;
                $product_id = $getSelectedProduct->product_id;

                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amountActual;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->buyDirectApp($phone, $product_id);

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                \Log::info("MTN PORTAL DIRECT APP RESPONSE");
                \Log::info($response);
                if (!isset($response) || is_null($response)) {

                    $response_msg =  null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    
                    $msg = '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->product_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['message']) && $response['message'] == "Session Not Found" && $response['statusCode'] == "1112") {
                    $response_msg = !is_null($response['error']) ? $response['error'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    

                    $msg = !is_null($response['error']) ? $response['error'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->product_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['message']) && $response['message'] == "Valid session" && $response['statusCode'] == "1112") {
                    $response_msg = !is_null($response['error']) ? $response['error'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    

                    $msg = !is_null($response['error']) ? $response['error'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->product_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);


                } elseif (isset($response) && !is_array($response)) {
                    if (stripos($response, 'activation') !== false || stripos($response,"do not have an active") !== false || stripos($response,"purchase a data share bundle") !== false || stripos($response,"temporarily unable to process your request") !== false || stripos($response,"issue with the token") !== false || stripos($response,"internal processing error") !== false || stripos($response,"was not successful") !== false || stripos($response,"invalid share pin") !== false || stripos($response,"no healthy upstream") !== false || stripos($response,"error while processing the request") !== false || stripos($response,"not Active on CLM") !== false || stripos($response,"unable to process your request") !== false || stripos($response,"internal server error") !== false || stripos($response,"Datashare is insufficient") !== false || stripos($response,"upstream connect error") !== false || stripos($response,"Connection refused") !== false || stripos($response,"connect timeout") !== false || stripos($response,"Empty response from server") !== false || stripos($response,"unknown error occurred") !== false || stripos($response,"barred due to") !== false || stripos($response,"you are not sending to valid") !== false || stripos($response,"you don't have sufficient") !== false || stripos($response,"connect timeout") !== false || stripos($response,"failed to connect") !== false || stripos($response,"other technical") !== false) {
                    $response_msg = !is_null($response) ? $response : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                   

                    $msg = !is_null($response) ? $response : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->product_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
                    }

                } elseif (isset($response['message']) && stripos($response['message'], 'Other Technical Error') !== false ) {
                    $response_msg = !is_null($response['error']) ? $response['error'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    
                    $msg = !is_null($response['error']) ? $response['error'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->product_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);


                } elseif (isset($response['message']) && stripos($response['message'], 'phone number field is not blank') !== false ) {
                    $response_msg = !is_null($response['error']) ? "recipient's number is barred due to NIN. Ask the recipient to link number at https://nin.mtn.ng/nin/ or visit any MTN store for assistance." : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    

                    $msg = !is_null($response['error']) ? "recipient's number is barred due to NIN. Ask the recipient to link number at https://nin.mtn.ng/nin/ or visit any MTN store for assistance." : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->product_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['message']) && stripos($response['message'], 'Transaction not successful..') !== false ) {
                    $response_msg = !is_null($response['error']) ? $response['error'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                   
                    $msg = !is_null($response['error']) ? $response['error'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->product_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['message']) && stripos($response['message'], 'Activation of Bundle was not successful') !== false ) {
                    $response_msg = !is_null($response['error']) ? $response['error'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                   
                    $msg = !is_null($response['error']) ? $response['error'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->product_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['message']) && stripos($response['message'], 'Eligibility Check Failed') !== false ) {
                    $response_msg = !is_null($response['error']) ? $response['error'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                   
                    $msg = !is_null($response['error']) ? $response['error'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->product_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['message']) && stripos($response['message'], 'Invalid Phone Number') !== false ) {
                    $response_msg = !is_null($response['error']) ? $response['error'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                   
                    $msg = !is_null($response['error']) ? $response['error'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->product_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                }
                  elseif (isset($response['message']) && stripos($response['message'], 'You are not eligible for this offer..') !== false ) {
                    $response_msg = !is_null($response['error']) ? $response['error'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    
                    $msg = !is_null($response['error']) ? $response['error'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->product_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                }
                  elseif (isset($response['message']) && stripos($response['message'], 'Price changed or MTN removed the plan') !== false ) {
                    $response_msg = !is_null($response['error']) ? $response['error'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    
                    $msg = !is_null($response['error']) ? $response['error'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->product_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                }
                  elseif (isset($response['status']) && $response['status'] == "1112") {
                    $response_msg = !is_null($response['error']) ? $response['error'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    $msg = !is_null($response['error']) ? $response['error'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->product_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['status']) && $response['status'] == "3016") {
                    $response_msg = !is_null($response['error']) ? $response['error'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                   
                    $msg = !is_null($response['error']) ? $response['error'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->product_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['status']) && $response['status'] == "3001") {
                    $response_msg = !is_null($response['error']) ? $response['error'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    
                    $msg = !is_null($response['error']) ? $response['error'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->product_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['status']) && $response['status'] == "3002") {
                    $response_msg = !is_null($response['error']) ? $response['error'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    
                    $msg = !is_null($response['error']) ? $response['error'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->product_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['status']) && $response['status'] == "3004") {
                    $response_msg = !is_null($response['error']) ? $response['error'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    $msg = !is_null($response['error']) ? $response['error'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->product_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif(isset($response['error']) && $response['error'] == "System is trying to process the request"){
                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['error'];
                    $updateOrder->save();
                    $msg = $response['error'];
                    
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->product_id, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['error']);
                } elseif (isset($response['statusCode']) && $response['statusCode'] == "3856") {
                    
                    $response_msg = !is_null($response['error']) ? $response['error'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    
                    $msg = !is_null($response['error']) ? $response['error'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->product_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['statusCode']) && $response['statusCode'] == "4003") {
                    $response_msg = !is_null($response['error']) ? $response['error'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    $msg = !is_null($response['error']) ? $response['error'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->product_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['statusCode']) && $response['statusCode'] == "4002") {
                    $response_msg = !is_null($response['error']) ? $response['error'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    $msg = !is_null($response['error']) ? $response['error'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->product_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                }
                
                elseif (isset($response['status']) && $response['status'] == 4204) {
                    $response_msg = !is_null($response['message']) ? $response['message'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    $order->response = !is_null($response['message']) ? $response['message'] : '';
                    $order->save();

                    $msg = !is_null($response['message']) ? $response['message'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->product_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                }
                elseif (isset($response['statusCode']) && $response['statusCode'] == "6001") {
                    $response_msg = !is_null($response['error']) ? $response['error'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    $msg = !is_null($response['error']) ? $response['error'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->product_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                }elseif (isset($response['statusCode']) && $product_id == "RACT_NG_Data_2158" && $response['statusCode'] == "3005") {

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = "Subscription successful";
                    $updateOrder->save();
                    $msg = "230MB Daily Plan" . " MTNNG Ref: " . "RACT_NG_Data_2158" . " SWIFTLINK REF: " . $ref;
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->product_id, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", "230MB Daily Plan Successful");
                }
                 elseif (isset($response['statusCode']) && $response['statusCode'] == "3005") {
                    $response_msg = !is_null($response['error']) ? $response['error'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    $msg = !is_null($response['error']) ? $response['error'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->product_id, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                }
                
                elseif (isset($response['statusCode']) && $response['statusCode'] == '0000') {

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['subscriptionDescription'];
                    $updateOrder->save();
                    $msg = $response['subscriptionDescription'] . " MTNNG Ref: " . $response['transactionId'] . " SWIFTLINK REF: " . $ref;
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->product_id, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['subscriptionDescription']);
                }
                }
                
            }
            
            
            // elseif (!is_null($subcategory->description) && $subcategory->description == "MTNCG") {

            //     $share_data = explode(" ",$getSelectedProduct->plan)[0];

            //     $order = new Order();
            //     $order->ref = $ref;
            //     $order->custom_reference = $custom_reference;
            //     $order->user_id = $this->user->id;
            //     $order->subcategory_id = $subcategory->id;
            //     $order->plan = $subcategory->title . " " . $plan . " at N" . $amountActual;
            //     $order->amount = $amountActual;
            //     $order->quantity = 1;
            //     $order->channel = is_null($request->channel) ? 'Web' : 'App';
            //     $order->data_size = $getSelectedProduct->data_size;
            //     $order->subtotal = $amountActual;
            //     $order->total = $amountActual;
            //     $order->phone = $phone;
            //     $order->bal = $bal;
            //     $order->prev_bal = $prev;
            //     $order->description = $subcategory->title . " " . $plan .
            //         " at N" . $amountActual;
            //     $order->status = 0;
            //     $order->save();

            //     try {
            //         $response = $this->buyCG($phone,$share_data);

            //     } catch (Exception $e) {
            //         $response = null;
            //     }

            //     \Log::info("MTN PORTAL CG RESPONSE");
            //     \Log::info($response);

            //     if (isset($response['message']) && $response['message'] == "Session Not Found" && $response['status'] == 401) {
            //         $updOrder = Order::where('ref', $ref)->first();
            //         $updOrder->status = 4;
            //         $updOrder->save();

            //         //refund
            //         $this->isCredited($amountActual, $this->user->id);

            //         $order = new Order();
            //         $order->ref = $ref;
            //         $order->custom_reference = $custom_reference;
            //         $order->user_id = $this->user->id;
            //         $order->subcategory_id = $subcategory->id;
            //         $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
            //         $order->amount = $amountActual;
            //         $order->quantity = 1;
            //         $order->channel = is_null($request->channel) ? 'Web' : 'App';

            //         $order->subtotal = $amountActual;
            //         $order->total = $amountActual;
            //         $order->phone = $phone;
            //         $order->bal = $prev;
            //         $order->prev_bal = $bal;
            //         $order->description = $subcategory->title . " " . $plan .
            //             " at N" . $amountActual;
            //         $order->status = 2;
            //         $order->response = !is_null($response['message']) ? $response['message'] : '';
            //         $order->save();

            //         $msg = !is_null($response['message']) ? $response['message'] : '';
            //         $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $share_data, $ref, $subcategory->telegram, $msg);

            //         //$msg = "Data is not available,please try again later";


            //         return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

            //     } elseif (isset($response['message']) && $response['message'] == "Valid session" && $response['status'] == 1) {
            //         $updOrder = Order::where('ref', $ref)->first();
            //         $updOrder->status = 4;
            //         $updOrder->save();

            //         //refund
            //         $this->isCredited($amountActual, $this->user->id);

            //         $order = new Order();
            //         $order->ref = $ref;
            //         $order->custom_reference = $custom_reference;
            //         $order->user_id = $this->user->id;
            //         $order->subcategory_id = $subcategory->id;
            //         $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
            //         $order->amount = $amountActual;
            //         $order->quantity = 1;
            //         $order->channel = is_null($request->channel) ? 'Web' : 'App';

            //         $order->subtotal = $amountActual;
            //         $order->total = $amountActual;
            //         $order->phone = $phone;
            //         $order->bal = $prev;
            //         $order->prev_bal = $bal;
            //         $order->description = $subcategory->title . " " . $plan .
            //             " at N" . $amountActual;
            //         $order->status = 2;
            //         $order->response = !is_null($response['message']) ? $response['message'] : '';
            //         $order->save();

            //         $msg = !is_null($response['message']) ? $response['message'] : '';
            //         $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $share_data, $ref, $subcategory->telegram, $msg);

            //         //$msg = "Data is not available,please try again later";


            //         return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

            //     } elseif (!isset($response['message']) && count($response) == 1 && isset($response['version']) && $response['version'] == "Patch") {
            //         $updOrder = Order::where('ref', $ref)->first();
            //         $updOrder->status = 4;
            //         $updOrder->save();

            //         //refund
            //         $this->isCredited($amountActual, $this->user->id);

            //         $order = new Order();
            //         $order->ref = $ref;
            //         $order->custom_reference = $custom_reference;
            //         $order->user_id = $this->user->id;
            //         $order->subcategory_id = $subcategory->id;
            //         $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
            //         $order->amount = $amountActual;
            //         $order->quantity = 1;
            //         $order->channel = is_null($request->channel) ? 'Web' : 'App';

            //         $order->subtotal = $amountActual;
            //         $order->total = $amountActual;
            //         $order->phone = $phone;
            //         $order->bal = $prev;
            //         $order->prev_bal = $bal;
            //         $order->description = $subcategory->title . " " . $plan .
            //             " at N" . $amountActual;
            //         $order->status = 2;
            //         $order->response = !is_null($response['version']) ? $response['version'] : '';
            //         $order->save();

            //         $msg = !is_null($response['version']) ? $response['version'] : '';
            //         $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $share_data, $ref, $subcategory->telegram, $msg);

            //         //$msg = "Data is not available,please try again later";


            //         return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

            //     } elseif (isset($response) && count($response) == 1 && is_null($response[0])) {
            //         $updOrder = Order::where('ref', $ref)->first();
            //         $updOrder->status = 4;
            //         $updOrder->save();

            //         //refund
            //         $this->isCredited($amountActual, $this->user->id);

            //         $order = new Order();
            //         $order->ref = $ref;
            //         $order->custom_reference = $custom_reference;
            //         $order->user_id = $this->user->id;
            //         $order->subcategory_id = $subcategory->id;
            //         $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
            //         $order->amount = $amountActual;
            //         $order->quantity = 1;
            //         $order->channel = is_null($request->channel) ? 'Web' : 'App';

            //         $order->subtotal = $amountActual;
            //         $order->total = $amountActual;
            //         $order->phone = $phone;
            //         $order->bal = $prev;
            //         $order->prev_bal = $bal;
            //         $order->description = $subcategory->title . " " . $plan .
            //             " at N" . $amountActual;
            //         $order->status = 2;
            //         $order->response = "Null response returned";
            //         $order->save();

            //         $msg = "Null response returned";
            //         $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $share_data, $ref, $subcategory->telegram, $msg);

            //         //$msg = "Data is not available,please try again later";


            //         return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

            //     } elseif (isset($response[0]['RespDescription']) && stripos($response[0]['RespDescription'], 'activation') !== false || stripos($response[0]['RespDescription'],"do not have an active") !== false || stripos($response[0]['RespDescription'],"purchase a data share bundle") !== false || stripos($response[0]['RespDescription'],"temporarily unable to process your request") !== false || stripos($response[0]['RespDescription'],"issue with the token") !== false || stripos($response[0]['RespDescription'],"internal processing error") !== false || stripos($response[0]['RespDescription'],"was not successful") !== false || stripos($response[0]['RespDescription'],"invalid share pin") !== false || stripos($response[0]['RespDescription'],"no healthy upstream") !== false || stripos($response[0]['RespDescription'],"error while processing the request") !== false || stripos($response[0]['RespDescription'],"not Active on CLM") !== false || stripos($response[0]['RespDescription'],"unable to process your request") !== false || stripos($response[0]['RespDescription'],"internal server error") !== false || stripos($response[0]['RespDescription'],"Datashare is insufficient") !== false || stripos($response[0]['RespDescription'],"upstream connect error") !== false || stripos($response[0]['RespDescription'],"Connection refused") !== false || stripos($response[0]['RespDescription'],"connect timeout") !== false || stripos($response[0]['RespDescription'],"Empty response from server") !== false || stripos($response[0]['RespDescription'],"unknown error occurred") !== false || stripos($response[0]['RespDescription'],"barred due to") !== false || stripos($response[0]['RespDescription'],"you are not sending to valid") !== false || stripos($response[0]['RespDescription'],"you don't have sufficient") !== false || stripos($response[0]['RespDescription'],"connect timeout") !== false || stripos($response[0]['RespDescription'],"failed to connect") !== false) {
            //         $updOrder = Order::where('ref', $ref)->first();
            //         $updOrder->status = 4;
            //         $updOrder->save();

            //         //refund
            //         $this->isCredited($amountActual, $this->user->id);

            //         $order = new Order();
            //         $order->ref = $ref;
            //         $order->custom_reference = $custom_reference;
            //         $order->user_id = $this->user->id;
            //         $order->subcategory_id = $subcategory->id;
            //         $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
            //         $order->amount = $amountActual;
            //         $order->quantity = 1;
            //         $order->channel = is_null($request->channel) ? 'Web' : 'App';

            //         $order->subtotal = $amountActual;
            //         $order->total = $amountActual;
            //         $order->phone = $phone;
            //         $order->bal = $prev;
            //         $order->prev_bal = $bal;
            //         $order->description = $subcategory->title . " " . $plan .
            //             " at N" . $amountActual;
            //         $order->status = 2;
            //         $order->response = !is_null($response[0]['RespDescription']) ? $response[0]['RespDescription'] : '';
            //         $order->save();

            //         $msg = !is_null($response[0]['RespDescription']) ? $response[0]['RespDescription'] : '';
            //         $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $share_data, $ref, $subcategory->telegram, $msg);

            //         // $msg = "Data is not available,please try again later";


            //         return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

            //     } elseif (stripos($response[0]['RespDescription'], 'other technical') !== false ) {
            //         $updOrder = Order::where('ref', $ref)->first();
            //         $updOrder->status = 4;
            //         $updOrder->save();

            //         //refund
            //         $this->isCredited($amountActual, $this->user->id);

            //         $order = new Order();
            //         $order->ref = $ref;
            //         $order->custom_reference = $custom_reference;
            //         $order->user_id = $this->user->id;
            //         $order->subcategory_id = $subcategory->id;
            //         $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
            //         $order->amount = $amountActual;
            //         $order->quantity = 1;
            //         $order->channel = is_null($request->channel) ? 'Web' : 'App';

            //         $order->subtotal = $amountActual;
            //         $order->total = $amountActual;
            //         $order->phone = $phone;
            //         $order->bal = $prev;
            //         $order->prev_bal = $bal;
            //         $order->description = $subcategory->title . " " . $plan .
            //             " at N" . $amountActual;
            //         $order->status = 2;
            //         $order->response = !is_null($response[0]['RespDescription']) ? $response[0]['RespDescription'] : '';
            //         $order->save();

            //         $msg = !is_null($response[0]['RespDescription']) ? $response[0]['RespDescription'] : '';
            //         $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $share_data, $ref, $subcategory->telegram, $msg);

            //         //$msg = "Data is not available,please try again later";


            //         return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

            //     } elseif (isset($response[0]['Status']) && $response[0]['Status'] == "FAILURE") {
            //         $updOrder = Order::where('ref', $ref)->first();
            //         $updOrder->status = 4;
            //         $updOrder->save();

            //         //refund
            //         $this->isCredited($amountActual, $this->user->id);

            //         $order = new Order();
            //         $order->ref = $ref;
            //         $order->custom_reference = $custom_reference;
            //         $order->user_id = $this->user->id;
            //         $order->subcategory_id = $subcategory->id;
            //         $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
            //         $order->amount = $amountActual;
            //         $order->quantity = 1;
            //         $order->channel = is_null($request->channel) ? 'Web' : 'App';

            //         $order->subtotal = $amountActual;
            //         $order->total = $amountActual;
            //         $order->phone = $phone;
            //         $order->bal = $prev;
            //         $order->prev_bal = $bal;
            //         $order->description = $subcategory->title . " " . $plan .
            //             " at N" . $amountActual;
            //         $order->status = 2;
            //         $order->response = !is_null($response[0]['RespDescription']) ? $response[0]['RespDescription'] : '';
            //         $order->save();

            //         $msg = !is_null($response[0]['RespDescription']) ? $response[0]['RespDescription'] : '';
            //         $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $share_data, $ref, $subcategory->telegram, $msg);

            //         //$msg = "Data is not available,please try again later";


            //         return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

            //     } elseif (!isset($response) || is_null($response)) {

            //         return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

            //     } elseif (isset($response[0]['Status']) && $response[0]['Status'] === 'SUCCESS' && $response[0]['ResponseCode'] == '0') {

            //         if ($this->user->bonusIsAwardable()) {

            //             $subcategory = Subcategory::find(23);
            //             $productCollection = json_decode($subcategory->products);
            //             $bonus = $this->getUserLevel($productCollection, $this->user->userlevel);
            //             $amountadwarded = $this->adwardBonus($this->user->referrer()->id, $bonus, $amountActual);


            //             $getbal = User::find($this->user->referrer()->id);
            //             $order = new Order();
            //             $order->ref = $this->referenceCode();
            //             $order->user_id = $this->user->referrer()->id;
            //             $order->subcategory_id = 23;
            //             $order->plan = $subcategory->title . " " . $bonus . "%";
            //             $order->amount = $amountadwarded;
            //             $order->quantity = 1;
            //             $order->subtotal = $amountadwarded;
            //             $order->total = $amountadwarded;
            //             $order->bal = $getbal->wallet;
            //             $order->channel = is_null($request->channel) ? 'Web' : 'App';
            //             $order->prev_bal = $getbal->wallet - $bonus;
            //             $order->description = $bonus . "% " . $subcategory->title . "   for Referring " . $this->user->lastname;
            //             $order->status = 1;
            //             $order->save();
            //         }
            //         $updateOrder = Order::where('ref', $ref)->first();
            //         $updateOrder->status = 1;
            //         $updateOrder->response = $response[0]['ResponseData']['ProductBuy']['Notification'];
            //         $updateOrder->save();
            //         $msg = $response[0]['ResponseData']['ProductBuy']['Notification'] . " MTNNG Ref: " . $response[0]['RequestId'] . " SWIFTLINK REF: " . $ref;


            //         $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $share_data, $ref, $subcategory->telegram, $msg);

            //         return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response[0]['ResponseData']['ProductBuy']['Notification']);
            //     }
            // }
            elseif (!is_null($subcategory->description) && $subcategory->description == "MTNCG") {

                $share_data = $getSelectedProduct->data_size;

                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amountActual;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->buyCG($phone,$share_data);

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                \Log::info("MTN PORTAL CG RESPONSE");
                \Log::info($response);

                if (isset($response['msg']) || is_null($response)) {
                    $response_msg = "Sorry an error occurred, kindly try again";
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    

                    $msg = "Sorry an error occurred, kindly try again.";
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $share_data, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response) && isset($response['message']) && $response['message'] == "Unauthorized") {
                    $response_msg = "Sorry an error occurred, kindly try again";
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                   
                    $msg = "Sorry an error occurred, kindly try again.";
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $share_data, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response) && !is_array($response)) {
                    if (stripos($response, 'activation') !== false || stripos($response,"do not have an active") !== false || stripos($response,"purchase a data share bundle") !== false || stripos($response,"temporarily unable to process your request") !== false || stripos($response,"issue with the token") !== false || stripos($response,"internal processing error") !== false || stripos($response,"was not successful") !== false || stripos($response,"invalid share pin") !== false || stripos($response,"no healthy upstream") !== false || stripos($response,"error while processing the request") !== false || stripos($response,"not Active on CLM") !== false || stripos($response,"unable to process your request") !== false || stripos($response,"internal server error") !== false || stripos($response,"Datashare is insufficient") !== false || stripos($response,"upstream connect error") !== false || stripos($response,"Connection refused") !== false || stripos($response,"connect timeout") !== false || stripos($response,"Empty response from server") !== false || stripos($response,"unknown error occurred") !== false || stripos($response,"barred due to") !== false || stripos($response,"you are not sending to valid") !== false || stripos($response,"you don't have sufficient") !== false || stripos($response,"connect timeout") !== false || stripos($response,"failed to connect") !== false || stripos($response,"other technical") !== false) {
                    $response_msg = !is_null($response) ? $response : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    

                    $msg = !is_null($response) ? $response : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $share_data, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
                    }

                } elseif (isset($response[0]['otherDetails']) && stripos($response[0]['otherDetails'], 'activation') !== false || stripos($response[0]['otherDetails'],"do not have an active") !== false || stripos($response[0]['otherDetails'],"purchase a data share bundle") !== false || stripos($response[0]['otherDetails'],"temporarily unable to process your request") !== false || stripos($response[0]['otherDetails'],"issue with the token") !== false || stripos($response[0]['otherDetails'],"internal processing error") !== false || stripos($response[0]['otherDetails'],"was not successful") !== false || stripos($response[0]['otherDetails'],"invalid share pin") !== false || stripos($response[0]['otherDetails'],"no healthy upstream") !== false || stripos($response[0]['otherDetails'],"error while processing the request") !== false || stripos($response[0]['otherDetails'],"not Active on CLM") !== false || stripos($response[0]['otherDetails'],"unable to process your request") !== false || stripos($response[0]['otherDetails'],"internal server error") !== false || stripos($response[0]['otherDetails'],"Datashare is insufficient") !== false || stripos($response[0]['otherDetails'],"upstream connect error") !== false || stripos($response[0]['otherDetails'],"Connection refused") !== false || stripos($response[0]['otherDetails'],"connect timeout") !== false || stripos($response[0]['otherDetails'],"Empty response from server") !== false || stripos($response[0]['otherDetails'],"unknown error occurred") !== false || stripos($response[0]['otherDetails'],"barred due to") !== false || stripos($response[0]['otherDetails'],"you are not sending to valid") !== false || stripos($response[0]['otherDetails'],"you don't have sufficient") !== false || stripos($response[0]['otherDetails'],"connect timeout") !== false || stripos($response[0]['otherDetails'],"failed to connect") !== false) {
                    $response_msg = !is_null($response[0]['otherDetails']) ? $response[0]['otherDetails'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
  

                    $msg = !is_null($response[0]['otherDetails']) ? $response[0]['otherDetails'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $share_data, $ref, $subcategory->telegram, $msg);

                    // $msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (stripos($response[0]['otherDetails'], 'other technical') !== false ) {
                    $response_msg = !is_null($response[0]['otherDetails']) ? $response[0]['otherDetails'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
  
                   

                    $msg = !is_null($response[0]['otherDetails']) ? $response[0]['otherDetails'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $share_data, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response[0]['subscriptionStatus']) && $response[0]['subscriptionStatus'] == "Failed") {
                    $response_msg = !is_null($response[0]['otherDetails']) ? $response[0]['otherDetails'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
  
                   
                    $msg = !is_null($response[0]['otherDetails']) ? $response[0]['otherDetails'] : '';
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $share_data, $ref, $subcategory->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (!isset($response) || is_null($response)) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif (isset($response[0]['subscriptionStatus']) && $response[0]['subscriptionStatus'] === 'Success' && $response[0]['status'] == '0000') {

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response[0]['otherDetails'];
                    $updateOrder->save();
                    $msg = $response[0]['otherDetails'] . " SWIFTLINK REF: " . $ref;
                    
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $share_data, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response[0]['otherDetails']);
                }
            }
            
            elseif (!is_null($subcategory->description) && $subcategory->description == "ZOEPORTAL") {

                $network_id = $getSelectedProduct->zoe_network_id;
                $plan_id = $getSelectedProduct->zoe_plan_id;
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amountActual;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->zoePortal($network_id, $phone,$plan_id);

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                \Log::info("ZOE PORTAL RESPONSE");
                \Log::info($response);

                 if (isset($response) && isset($response['Status']) && $response['Status'] != "successful") {
                    $response_msg = isset($response['api_response']) ? $response['api_response']  : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
  
                   

                   // $msg = $response['data']['gateway_response'];
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->zoe_plan_id, $ref, $subcategory->telegram);

                    $msg = isset($response['api_response']) ? $response['api_response'] : "";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response) && isset($response['error'][0])) {
                    $response_msg = null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                   // $msg = $response['data']['gateway_response'];
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->zoe_plan_id, $ref, $subcategory->telegram);

                     $msg = isset($response['api_response']) ? $response['api_response'] : "";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (!isset($response) || is_null($response) || $response['Status'] == "pending" || $response['Status'] == "processing") {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif (isset($response) && $response['Status'] == "successful") {

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['api_response'];
                    $updateOrder->save();
                    $msg = $response['api_response'] . " ZoeDataHub Ref: " . $response['id'];
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->zoe_plan_id, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['api_response']);
                }
            }
            
            elseif (!is_null($subcategory->description) && $subcategory->description == "KORAPORTAL") {

                $network_id = $getSelectedProduct->kora_network_id;
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->kora_plan_id;
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amountActual;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->koraPortal($network_id, $phone,$plan_id);

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                \Log::info("KORA PORTAL RESPONSE");
                \Log::info($response);

                 if (isset($response) && isset($response['Status']) && $response['Status'] == "failed") {
                    $response_msg = isset($response['api_response']) ? $response['api_response']  : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    

                   // $msg = $response['data']['gateway_response'];
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->kora_plan_id, $ref, $subcategory->telegram);

                    $msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response) && isset($response['error'][0])) {
                    $response_msg = isset($response['api_response']) ? $response['api_response']  : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    
                   // $msg = $response['data']['gateway_response'];
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->kora_plan_id, $ref, $subcategory->telegram);

                    $msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (!isset($response) || is_null($response) || $response['Status'] == "pending" || $response['Status'] == "processing") {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif (isset($response) && $response['Status'] == "successful") {

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response =  !is_null($response['api_response']) ? $response['api_response'] : '';
                    $updateOrder->save();
                    $msg = $response['api_response'];
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->kora_plan_id, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['api_response']);
                }
            }
            
            elseif (!is_null($subcategory->description) && $subcategory->description == "ROYALPORTAL") {

                $network_id = $getSelectedProduct->royal_network_id;
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->royal_plan_id;
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amountActual;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->royalPortal($network_id, $phone,$plan_id);

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                \Log::info("ROYAL PORTAL RESPONSE");
                \Log::info($response);

                 if (isset($response) && isset($response['response']) && $response['response']['status'] == "failed") {
                    $response_msg = isset($response['rtr']) ? $response['rtr']  : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                  

                   // $msg = $response['data']['gateway_response'];
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->royal_plan_id, $ref, $subcategory->telegram);

                    $msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (!isset($response) || is_null($response) || $response['response']['status'] == "pending" || $response['response']['status'] == "processing") {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif (isset($response) && $response['response']['status'] == "successful") {

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response =  !is_null($response['rtr']) ? $response['rtr'] : '';
                    $updateOrder->save();
                    $msg = $response['rtr'];
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->royal_plan_id, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['rtr']);
                }
            }
            
            elseif (!is_null($subcategory->description) && $subcategory->description == "SUBPORTAL") {

                $network_id = $getSelectedProduct->sub_network_id;
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->sub_plan_id;
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amountActual;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->subPortal($network_id, $phone,$plan_id);

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                \Log::info("SUBPORTAL RESPONSE");
                \Log::info($response);

                 if (isset($response) && isset($response['Status']) && $response['Status'] == "failed") {
                    $response_msg = isset($response['api_response']) ? $response['api_response']  : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                   

                   // $msg = $response['data']['gateway_response'];
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->sub_plan_id, $ref, $subcategory->telegram);

                    $msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response) && isset($response['error'][0])) {
                    $response_msg = isset($response['api_response']) ? $response['api_response']  : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                   // $msg = $response['data']['gateway_response'];
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->sub_plan_id, $ref, $subcategory->telegram);

                    $msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (!isset($response) || is_null($response) || $response['Status'] == "pending" || $response['Status'] == "processing") {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif (isset($response) && $response['Status'] == "successful") {

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response =  !is_null($response['api_response']) ? $response['api_response'] : '';
                    $updateOrder->save();
                    $msg = $response['api_response'];
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->sub_plan_id, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['api_response']);
                }
            }
            
            elseif (!is_null($subcategory->description) && $subcategory->description == "DATAMALL") {

                $network_id = $getSelectedProduct->datamall_network_id;
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->datamall_plan_id;
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amountActual;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->dataMallPortal($network_id, $phone,$plan_id);

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                \Log::info("DATAMALL PORTAL RESPONSE");
                \Log::info($response);

                 if (isset($response) && isset($response['Status']) && $response['Status'] == "failed") {
                    $response_msg = isset($response['api_response']) ? $response['api_response']  : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                   // $msg = $response['data']['gateway_response'];
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->datamall_plan_id, $ref, $subcategory->telegram);

                    $msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response) && isset($response['error'][0])) {
                    $response_msg = isset($response['api_response']) ? $response['api_response']  : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                   // $msg = $response['data']['gateway_response'];
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->datamall_plan_id, $ref, $subcategory->telegram);

                    $msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (!isset($response) || is_null($response) || $response['Status'] == "pending" || $response['Status'] == "processing") {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif (isset($response) && $response['Status'] == "successful") {

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response =  !is_null($response['api_response']) ? $response['api_response'] : '';
                    $updateOrder->save();
                    $msg = $response['api_response'];
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->datamall_plan_id, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['api_response']);
                }
            }
            
            elseif (!is_null($subcategory->description) && $subcategory->description == "GTECHPORTAL") {

                $network_id = $getSelectedProduct->gtech_network_id;
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->gtech_plan_id;
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amountActual;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->gtechPortal($network_id, $phone,$plan_id);

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                \Log::info("GTECH PORTAL RESPONSE");
                \Log::info($response);

                 if (isset($response) && isset($response['Status']) && $response['Status'] == "failed") {
                    $response_msg = isset($response['api_response']) ? $response['api_response']  : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    

                   // $msg = $response['data']['gateway_response'];
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->gtech_plan_id, $ref, $subcategory->telegram);

                    $msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response) && isset($response['error'][0])) {
                    $response_msg = isset($response['api_response']) ? $response['api_response']  : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                   // $msg = $response['data']['gateway_response'];
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->gtech_plan_id, $ref, $subcategory->telegram);

                    $msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (!isset($response) || is_null($response) || $response['Status'] == "pending" || $response['Status'] == "processing") {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif (isset($response) && $response['Status'] == "successful") {

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response =  !is_null($response['api_response']) ? $response['api_response'] : '';
                    $updateOrder->save();
                    $msg = $response['api_response'];
                    
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->gtech_plan_id, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['api_response']);
                }
            }
            
            elseif (!is_null($subcategory->description) && $subcategory->description == "WISPER") {
                if($subcategory->id == 2 && $plan == "GET 5GB for 30 days"){
                    $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                    // $plan_id = $getSelectedProduct->code;
                    $plan_id = $getSelectedProduct->smeplug_id;
                    $order = new Order();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->subcategory_id = $subcategory->id;
                    $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
                    $order->amount = $amountActual;
                    $order->quantity = 1;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                    $order->api_route = $subcategory->description;
                    $order->subtotal = $amountActual;
                    $order->total = $amountActual;
                    $order->phone = $phone;
                    $order->bal = $bal;
                    $order->prev_bal = $prev;
                    $order->description = $subcategory->title . " " . $plan .
                        " at N" . $amountActual;
                    $order->status = 0;
                    $order->save();

                try {
                    $response = $this->SMEPlugApi($network_id, $plan_id, $phone, $ref);
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }
                
                \Log::info("SMEPLUG DATA RESPONSE WISPER");
                
                \Log::info(print_r($response,true));


                if(!isset($response) || is_null($response) || !isset($response->status) || is_null($response->status) || empty($response->status)){
                     return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
                    
                }elseif(isset($response->status) && $response->status == true && isset($response->data->current_status) && $response->data->current_status == "processing"){
                     return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
                    
                }elseif(isset($response->msg) && $response->msg == "Plan cannot be dispensed via wallet at this time"){ 
                    $response_msg = isset($response->msg) ? $response->msg  : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    
                    return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");

                    
                }elseif(isset($response->data) && $response->data->current_status == "failed"){ 
                    $response_msg = isset($response->data->msg) ? $response->data->msg  : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    
                    return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");

                    
                }elseif(isset($response->status) && $response->status == true && isset($response->data->current_status) && $response->data->current_status == "success") {

                    if (stripos($response->data->msg, "not successful") > 0) {
                        $updateOrder = Order::where('ref', $ref)->first();

                        $updateOrder->status = 0;
                        $updateOrder->response = $response->data->msg;
                        $updateOrder->save();
                        $msg = $response->data->msg . " SMEPLUG Ref: " . $response->data->reference;

                    } else {
                        $updateOrder = Order::where('ref', $ref)->first();

                        $updateOrder->status = 1;
                        $updateOrder->response = $response->data->msg;
                        $updateOrder->save();
                        $msg = $response->data->msg . " SMEPLUG Ref: " . $response->data->reference;
                        $this->processReferralRewards($updateOrder);

                    }

                    //   $telegram = $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->code, $ref, $subcategory->telegram, $msg);


                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response->data->msg);
                }
                
                } elseif($subcategory->id == 2 && $plan == "GET 3GB for 30 days"){
                    $network_id = $this->parseSMEPlugNetworkID(strtolower($network));
                    // $plan_id = $getSelectedProduct->code;
                    $plan_id = $getSelectedProduct->smeplug_id;
                    $order = new Order();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->subcategory_id = $subcategory->id;
                    $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
                    $order->amount = $amountActual;
                    $order->quantity = 1;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $getSelectedProduct->data_size;
                    $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                    $order->api_route = $subcategory->description;
                    $order->subtotal = $amountActual;
                    $order->total = $amountActual;
                    $order->phone = $phone;
                    $order->bal = $bal;
                    $order->prev_bal = $prev;
                    $order->description = $subcategory->title . " " . $plan .
                        " at N" . $amountActual;
                    $order->status = 0;
                    $order->save();

                try {
                    $response = $this->SMEPlugApi($network_id, $plan_id, $phone, $ref);
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }
                
                \Log::info("SMEPLUG DATA RESPONSE WISPER");
                
                \Log::info(print_r($response,true));


                if(!isset($response) || is_null($response) || !isset($response->status) || is_null($response->status) || empty($response->status)){
                     return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
                    
                }elseif(isset($response->status) && $response->status == true && isset($response->data->current_status) && $response->data->current_status == "processing"){
                     return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
                    
                }elseif(isset($response->msg) && $response->msg == "Plan cannot be dispensed via wallet at this time"){ 
                    $response_msg = isset($response->data->msg) ? $response->data->msg  : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");

                    
                }elseif(isset($response->data) && $response->data->current_status == "failed"){ 
                    $response_msg = isset($response->data->msg) ? $response->data->msg  : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    return $this->sendError2($ref, $custom_reference, "Transaction failed", "Transaction failed");

                    
                }elseif(isset($response->status) && $response->status == true && isset($response->data->current_status) && $response->data->current_status == "success") {

                   

                    if (stripos($response->data->msg, "not successful") > 0) {
                        $updateOrder = Order::where('ref', $ref)->first();

                        $updateOrder->status = 0;
                        $updateOrder->response = $response->data->msg;
                        $updateOrder->save();
                        $msg = $response->data->msg . " SMEPLUG Ref: " . $response->data->reference;

                    } else {
                        $updateOrder = Order::where('ref', $ref)->first();

                        $updateOrder->status = 1;
                        $updateOrder->response = $response->data->msg;
                        $updateOrder->save();
                        $msg = $response->data->msg . " SMEPLUG Ref: " . $response->data->reference;
                        $this->processReferralRewards($updateOrder);

                    }

                    //   $telegram = $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->code, $ref, $subcategory->telegram, $msg);


                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response->data->msg);
                }
                
                } else{
                $network = $getSelectedProduct->wisper_network_id;
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->wisper_plan_id;
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amountActual;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->wisperPortal($network, $phone, $plan_id);

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                \Log::info("WISPER PORTAL RESPONSE");
                \Log::info($response);

                if (isset($response) && isset($response['status']) && $response['status'] == "failed") {
                    $response_msg = !is_null($response['message']) ? $response['message'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    

                    // $msg = $response['data']['gateway_response'];
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->wisper_plan_id, $ref, $subcategory->telegram);

                    $msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (!isset($response) || is_null($response) || $response['status'] == "pending" || $response['status'] == "processing") {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif (isset($response) && $response['status'] == "success") {

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $actual_message = "Y'ello! You have gifted ". $getSelectedProduct->wisper_size . " to ".$this->addCountryCode($phone)." Share link https://mtnapp.page.link/myMTNNGApp with". $this->addCountryCode($phone) ." to download the new MyMTN app for exciting offers.";
                    $updateOrder->response = $actual_message;
                    $updateOrder->save();
                    $msg = $actual_message;
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->wisper_plan_id, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $actual_message);
                } else {
                    $updOrder = Order::where('ref', $ref)->first();
                    $updOrder->status = 0;
                    $updOrder->ref = isset($response) ? $response->transaction_ref : $ref;
                    $updOrder->save();

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                }
                }
            }
            
            elseif (!is_null($subcategory->description) && $subcategory->description == "INTEGRITYPORTAL") {
                
                // $plan_id = $getSelectedProduct->code;
                $product_code = $getSelectedProduct->integrity_product_code;
                //$network_id = $this->parseAutoPilotNetworkID(strtolower($network));
                //$plan_id = isset($getSelectedProduct->vtuplug_product_id) ? $getSelectedProduct->vtuplug_product_id : null;
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amountActual;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();
                

                try {
                    $response = $this->integrityPortal($product_code, $phone, $ref);

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                \Log::info("INTEGRITY PORTAL RESPONSE");
                \Log::info($response);

                if (isset($response) && isset($response['text_status']) && $response['text_status'] == "FAILED") {
                    $response_msg = !is_null($response['data']['true_response']) ? $response['data']['true_response'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    

                    // $msg = $response['data']['gateway_response'];
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->integrity_product_code, $ref, $subcategory->telegram);

                    $msg = !is_null($response['data']['true_response']) ? $response['data']['true_response'] : '';


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (!isset($response) || is_null($response) || $response['text_status'] == "PENDING" || $response['text_status'] == "PROCESSING") {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif (isset($response) && $response['text_status'] == "COMPLETED") {

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = !is_null($response['data']['true_response']) ? $response['data']['true_response'] : '';
                    $updateOrder->save();
                    $msg = !is_null($response['data']['true_response']) ? $response['data']['true_response'] : '';
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->integrity_product_code, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['data']['true_response']);
                } else {
                    $updOrder = Order::where('ref', $ref)->first();
                    $updOrder->status = 0;
                    $updOrder->save();

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                }
                
            
            }
            
            elseif (!is_null($subcategory->description) && $subcategory->description == "OLAADEPORTAL") {

                $network_id = $getSelectedProduct->olaade_network_id;
                // $plan_id = $getSelectedProduct->code;
                $plan_id = $getSelectedProduct->olaade_plan_id;
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amountActual;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();

                try {
                    $response = $this->olaadePortal($network_id, $phone, $plan_id);

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                \Log::info("OLAADE PORTAL RESPONSE");
                \Log::info($response);

                if (isset($response) && isset($response['Status']) && $response['Status'] == "failed") {
                  
                    $response_msg = !is_null($response['api_response']) ? $response['api_response'] : '';
                    
                     $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                   

                    $msg = $response['api_response'];
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->olaade_plan_id, $ref, $subcategory->telegram);

                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response) && isset($response['error'][0])) {
                    $response_msg = !is_null($response['api_response']) ? $response['api_response'] : '';
                    
                     $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    $msg = $response['api_response'];
                    
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->olaade_plan_id, $ref, $subcategory->telegram);


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (!isset($response) || is_null($response) || $response['Status'] == "pending" || $response['Status'] == "processing") {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif (isset($response) && $response['Status'] == "successful") {

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = !is_null($response['api_response']) ? $response['api_response'] : '';
                    $updateOrder->save();
                    $msg = $response['api_response'];
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->olaade_plan_id, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['api_response']);
                }
            }
            
            elseif (!is_null($subcategory->description) && $subcategory->description == "VTUPLUGPORTAL") {

                $network_id = $this->parseAutoPilotNetworkID(strtolower($network));
                $plan_id = $getSelectedProduct->vtuplug_product_id;
                
                $product_id = $getSelectedProduct->product_id;
                $variation_code = $getSelectedProduct->variation_code;
                $pin = $getSelectedProduct->pin;
                
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amountActual;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();
                
                if($subcategory->id == 2 && strtolower($getSelectedProduct->plan) == strtolower("GET 5GB for 14 days")){
                      try {
                        $response = $this->autoSyncPortal($ref,$phone,$product_id,$variation_code,$pin);
    
                    } catch (\Throwable $e) {
                        return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                    }
    
    
                    \Log::info("AUTOSYNCAwuf RESPONSE UNDER VTUPLUGPORTAL");
                    \Log::info(print_r($response, true));
    
                    if (!isset($response) || is_null($response)) {
    
                        return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
    
                    }elseif (isset($response['data']) && isset($response['data']['transaction']) && $response['data']['transaction']['status'] == "pending"){
                        return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
                    }elseif(isset($response['status']) && $response['status'] == "error"){
                        $response_msg = isset($response['message']) ? $response['message'] : null;
                        
                        $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                        
    
                        $msg = $response['message'];
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->variation_code, $ref, $subcategory->telegram, $msg);
    
                        $resp_msg = $response_msg;
    
                        //   if($response['data']['message'] == "You may have exhaust all sender number limit"){
                        //         Subcategory::find(48)->update(['status' => 0]);
                        //   }
    
    
    
                        return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
                    } elseif (isset($response['data']) && isset($response['data']['transaction']) && $response['data']['transaction']['status'] == "failed") {
                        $response_msg = isset($response['data']['transaction']['details']) ? $response['data']['transaction']['details'] : null;
                        
                        $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                        
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->variation_code, $ref, $subcategory->telegram);
    
                        $resp_msg = "Data is not available,please try again later";
    
                        
                        return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed: " . $resp_msg, "Transaction failed: " . $resp_msg);
                    }  elseif (isset($response['data']) && isset($response['data']['transaction']) && $response['data']['transaction']['details'] == "No Memo..") {
                        $response_msg = isset($response['data']['transaction']['details']) ? $response['data']['transaction']['details'] : null;
                        
                        $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                        
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->variation_code, $ref, $subcategory->telegram);
    
                        $resp_msg = "Data is not available,please try again later";
    
    
                        return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed: " . $resp_msg, "Transaction failed: " . $resp_msg);
                    } else {
    
                        
                        $updateOrder = Order::where('ref', $ref)->first();
                        $updateOrder->status = 1;
                        $updateOrder->response = $response['data']['transaction']['details'];
                        $updateOrder->save();
                        $msg1 = $response['data']['transaction']['details'];
                        $msg = $msg1 . " AutoSync Ref: " . $response['data']['transaction']['reference'];
                        $this->processReferralRewards($updateOrder);
    
    
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->variation_code, $ref, $subcategory->telegram, $msg);
    
                        return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $msg1);
                    }
                
                }else{

                    try {
                        $response = $this->vtuplugPortal($ref, $network_id, $phone, $plan_id);
    
                    } catch (\Throwable $e) {
                        return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                    }
    
                    \Log::info("VTUPLUG PORTAL RESPONSE");
                    \Log::info($response);
    
                    if (isset($response) && isset($response['status']) && $response['status'] == "fail") {
                      
                        $response_msg = !is_null($response['message']) ? $response['message'] : '';
                        
                         $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                       
    
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->vtuplug_product_id, $ref, $subcategory->telegram);
    
                        return $this->sendError3($ref, $custom_reference, $response_msg, "Transaction failed: " . $response_msg, "Transaction failed: " . $response_msg);
    
                    } elseif (!isset($response) || is_null($response) || $response['status'] == "pending" || $response['status'] == "processing") {
    
                        return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
    
                    } elseif ($response['status'] == "success" || $response['status'] == "successful") {
    
                        
                        $updateOrder = Order::where('ref', $ref)->first();
                        $updateOrder->status = 1;
                        $updateOrder->response = !is_null($response['message']) ? $response['message'] : '';
                        $updateOrder->save();
                        $msg = $response['message'];
                        $this->processReferralRewards($updateOrder);
    
    
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->vtuplug_product_id, $ref, $subcategory->telegram, $msg);
    
                        return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['message']);
                    }
                
                }
            
            }
            
            elseif (!is_null($subcategory->description) && $subcategory->description == "NAIJASUBPORTAL") {

                $network_id = isset($getSelectedProduct->naijasub_network_id) ? $getSelectedProduct->naijasub_network_id : null;
                // $plan_id = $getSelectedProduct->code;
                $plan_id = isset($getSelectedProduct->naijasub_plan_id) ? $getSelectedProduct->naijasub_plan_id : null;
                
                $product_id = $getSelectedProduct->product_id;
                $variation_code = $getSelectedProduct->variation_code;
                $pin = $getSelectedProduct->pin;
                
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amountActual;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();
                
                if($subcategory->id == 2 && strtolower($getSelectedProduct->plan) == strtolower("GET 5GB for 14 days")){
                      try {
                        $response = $this->autoSyncPortal($ref,$phone,$product_id,$variation_code,$pin);
    
                    } catch (\Throwable $e) {
                        return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                    }
    
    
                    \Log::info("AUTOSYNCAwuf RESPONSE UNDER NAIJASUBPORTAL");
                    \Log::info(print_r($response, true));
    
                    if (!isset($response) || is_null($response)) {
    
                        return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
    
                    }elseif (isset($response['data']) && isset($response['data']['transaction']) && $response['data']['transaction']['status'] == "pending"){
                        return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
                    }elseif(isset($response['status']) && $response['status'] == "error"){
                        $response_msg = isset($response['message']) ? $response['message'] : null;
                        
                        $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                        
    
                        $msg = $response['message'];
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->variation_code, $ref, $subcategory->telegram, $msg);
    
                        $resp_msg = $response_msg;
    
                        //   if($response['data']['message'] == "You may have exhaust all sender number limit"){
                        //         Subcategory::find(48)->update(['status' => 0]);
                        //   }
    
    
    
                        return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
                    } elseif (isset($response['data']) && isset($response['data']['transaction']) && $response['data']['transaction']['status'] == "failed") {
                        $response_msg = isset($response['data']['transaction']['details']) ? $response['data']['transaction']['details'] : null;
                        
                        $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                        
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->variation_code, $ref, $subcategory->telegram);
    
                        $resp_msg = "Data is not available,please try again later";
    
                        
                        return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed: " . $resp_msg, "Transaction failed: " . $resp_msg);
                    }  elseif (isset($response['data']) && isset($response['data']['transaction']) && $response['data']['transaction']['details'] == "No Memo..") {
                        $response_msg = isset($response['data']['transaction']['details']) ? $response['data']['transaction']['details'] : null;
                        
                        $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                        
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->variation_code, $ref, $subcategory->telegram);
    
                        $resp_msg = "Data is not available,please try again later";
    
    
                        return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed: " . $resp_msg, "Transaction failed: " . $resp_msg);
                    } else {
    
                        
                        $updateOrder = Order::where('ref', $ref)->first();
                        $updateOrder->status = 1;
                        $updateOrder->response = $response['data']['transaction']['details'];
                        $updateOrder->save();
                        $msg1 = $response['data']['transaction']['details'];
                        $msg = $msg1 . " AutoSync Ref: " . $response['data']['transaction']['reference'];
                        $this->processReferralRewards($updateOrder);
    
    
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->variation_code, $ref, $subcategory->telegram, $msg);
    
                        return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $msg1);
                    }
                
                }else{

                    try {
                        $response = $this->naijasubPortal($network_id, $phone, $plan_id,$ref);
    
                    } catch (\Throwable $e) {
                        return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                    }
    
                    \Log::info("NAIJASUB PORTAL RESPONSE");
                    \Log::info($response);
    
                    if (isset($response) && isset($response['Status']) && $response['Status'] == "failed") {
                      
                        $response_msg = !is_null($response['api_response']) ? preg_replace(
                    '/Transaction Sent For Processing \.\s*(.*?)\s*Beneficiary :/',
                    'Transaction Sent For $1 Failed' . "\n" . 'Beneficiary :',
                    $response['api_response']
                ) : '';
                        
                         $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                       
    
                        $msg = preg_replace(
                    '/Transaction Sent For Processing \.\s*(.*?)\s*Beneficiary :/',
                    'Transaction Sent For $1 Failed' . "\n" . 'Beneficiary :',
                    $response['api_response']
                );;
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->naijasub_plan_id, $ref, $subcategory->telegram);
    
                        return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
    
                    } elseif (isset($response) && isset($response['error'][0])) {
                        $response_msg = !is_null($response['api_response']) ? preg_replace(
                            '/Transaction Sent For Processing \.\s*(.*?)\s*Beneficiary :/',
                            'Transaction Sent For $1 Failed' . "\n" . 'Beneficiary :',
                            $response['api_response']
                        ) : '';
                        
                         $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                        
                        $msg = preg_replace(
                    '/Transaction Sent For Processing \.\s*(.*?)\s*Beneficiary :/',
                    'Transaction Sent For $1 Failed' . "\n" . 'Beneficiary :',
                    $response['api_response']
                );
                        
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->naijasub_plan_id, $ref, $subcategory->telegram);
    
    
                        return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
    
                    } elseif (!isset($response) || is_null($response) || $response['Status'] == "pending" || $response['Status'] == "processing") {
    
                        return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
    
                    } elseif (isset($response) && $response['Status'] == "successful") {
    
                        
                        $updateOrder = Order::where('ref', $ref)->first();
                        $updateOrder->status = 1;
                        $updateOrder->response = !is_null($response['api_response']) ? preg_replace(
                                '/Transaction Sent For Processing \.\s*(.*?)\s*Beneficiary :/',
                                'Transaction Sent For $1 is Successful' . "\n" . 'Beneficiary :',
                                $response['api_response']
                            ) : '';
                        $updateOrder->save();
                        $msg = preg_replace(
                            '/Transaction Sent For Processing \.\s*(.*?)\s*Beneficiary :/',
                            'Transaction Sent For $1 is Successful' . "\n" . 'Beneficiary :',
                            $response['api_response']
                        );
                        $this->processReferralRewards($updateOrder);
    
    
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->naijasub_plan_id, $ref, $subcategory->telegram, $msg);
    
                        return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $msg);
                    }
                
            }
            }
            
            elseif (!is_null($subcategory->description) && $subcategory->description == "AMAKASUBPORTAL") {

                $network_id = $this->parseOgaDamsNetworkID(strtolower($network));
                // $plan_id = $getSelectedProduct->code;
                $plan_id = isset($getSelectedProduct->amakasub_plan_id) ? $getSelectedProduct->amakasub_plan_id : null;
                
                $product_id = $getSelectedProduct->product_id;
                $variation_code = $getSelectedProduct->variation_code;
                $pin = $getSelectedProduct->pin;
                
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amountActual;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();
                
                if($subcategory->id == 2 && strtolower($getSelectedProduct->plan) == strtolower("GET 5GB for 14 days")){
                      try {
                        $response = $this->autoSyncPortal($ref,$phone,$product_id,$variation_code,$pin);
    
                    } catch (\Throwable $e) {
                        return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                    }
    
    
                    \Log::info("AUTOSYNCAwuf RESPONSE UNDER NAIJASUBPORTAL");
                    \Log::info(print_r($response, true));
    
                    if (!isset($response) || is_null($response)) {
    
                        return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
    
                    }elseif (isset($response['data']) && isset($response['data']['transaction']) && $response['data']['transaction']['status'] == "pending"){
                        return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
                    }elseif(isset($response['status']) && $response['status'] == "error"){
                        $response_msg = isset($response['message']) ? $response['message'] : null;
                        
                        $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                        
    
                        $msg = $response['message'];
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->variation_code, $ref, $subcategory->telegram, $msg);
    
                        $resp_msg = $response_msg;
    
                        //   if($response['data']['message'] == "You may have exhaust all sender number limit"){
                        //         Subcategory::find(48)->update(['status' => 0]);
                        //   }
    
    
    
                        return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
                    } elseif (isset($response['data']) && isset($response['data']['transaction']) && $response['data']['transaction']['status'] == "failed") {
                        $response_msg = isset($response['data']['transaction']['details']) ? $response['data']['transaction']['details'] : null;
                        
                        $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                        
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->variation_code, $ref, $subcategory->telegram);
    
                        $resp_msg = "Data is not available,please try again later";
    
                        
                        return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed: " . $resp_msg, "Transaction failed: " . $resp_msg);
                    }  elseif (isset($response['data']) && isset($response['data']['transaction']) && $response['data']['transaction']['details'] == "No Memo..") {
                        $response_msg = isset($response['data']['transaction']['details']) ? $response['data']['transaction']['details'] : null;
                        
                        $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                        
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->variation_code, $ref, $subcategory->telegram);
    
                        $resp_msg = "Data is not available,please try again later";
    
    
                        return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed: " . $resp_msg, "Transaction failed: " . $resp_msg);
                    } else {
    
                        
                        $updateOrder = Order::where('ref', $ref)->first();
                        $updateOrder->status = 1;
                        $updateOrder->response = $response['data']['transaction']['details'];
                        $updateOrder->save();
                        $msg1 = $response['data']['transaction']['details'];
                        $msg = $msg1 . " AutoSync Ref: " . $response['data']['transaction']['reference'];
                        $this->processReferralRewards($updateOrder);
    
    
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->variation_code, $ref, $subcategory->telegram, $msg);
    
                        return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $msg1);
                    }
                
                }else{

                    try {
                        $response = $this->amakasubPortal($network_id, $phone, $plan_id, $ref);
    
                    } catch (\Throwable $e) {
                        return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                    }
    
                    \Log::info("AMAKASUB PORTAL RESPONSE");
                    \Log::info($response);
    
                    if (isset($response) && isset($response['status']) && $response['status'] == "error") {
                      
                        $response_msg = isset($response['message']) ? $response['message'] : '';
                        
                         $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                       
    
                        $msg = preg_replace(
                    '/Transaction Sent For Processing \.\s*(.*?)\s*Beneficiary :/',
                    'Transaction Sent For $1 Failed' . "\n" . 'Beneficiary :',
                    $response['api_response']
                );;
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->naijasub_plan_id, $ref, $subcategory->telegram);
    
                        return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
    
                    } elseif (!isset($response) || is_null($response) || $response['status'] == "pending" || $response['status'] == "processing") {
    
                        return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
    
                    } elseif (isset($response) && $response['status'] == "success") {
    
                        
                        $updateOrder = Order::where('ref', $ref)->first();
                        $updateOrder->status = 1;
                        $updateOrder->response = isset($response['message']) ? $response['message'] : '';
                        $updateOrder->save();
                        $msg = isset($response['message']) ? $response['message'] : '';
                        $this->processReferralRewards($updateOrder);
    
    
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->amakasub_plan_id, $ref, $subcategory->telegram, $msg);
    
                        return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $msg);
                    }
                
            }
            }
            
            elseif (!is_null($subcategory->description) && $subcategory->description == "COOLSUBPORTAL") {

                $network_id = isset($getSelectedProduct->coolsub_network_id) ? $getSelectedProduct->coolsub_network_id : null;
                // $plan_id = $getSelectedProduct->code;
                $plan_id = isset($getSelectedProduct->coolsub_plan_id) ? $getSelectedProduct->coolsub_plan_id : null;
                
                $product_id = $getSelectedProduct->product_id;
                $variation_code = $getSelectedProduct->variation_code;
                $pin = $getSelectedProduct->pin;
                
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amountActual;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();
         

                    try {
                        $response = $this->coolsubPortal($network_id, $phone, $plan_id,$ref);
    
                    } catch (\Throwable $e) {
                        return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                    }
    
                    \Log::info("COOLSUB PORTAL RESPONSE");
                    \Log::info($response);
    
                    if (isset($response) && isset($response['Status']) && $response['Status'] == "failed") {
                      
                        $response_msg = isset($response['api_response']) ? $response['api_response'] : null;
                        
                         $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                       
    
                        $msg = isset($response['api_response']) ? $response['api_response'] : null ;
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->coolsub_plan_id, $ref, $subcategory->telegram);
    
                        return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
    
                    } elseif (isset($response) && isset($response['error'][0])) {
                        $response_msg = isset($response['api_response']) ? $response['api_response'] : null ;
                        
                         $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                        
                        $msg = isset($response['api_response']) ? $response['api_response'] : null ;
                        
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->coolsub_plan_id, $ref, $subcategory->telegram);
    
    
                        return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
    
                    } elseif (!isset($response) || is_null($response) || $response['Status'] == "pending" || $response['Status'] == "processing") {
    
                        return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
    
                    } elseif (isset($response) && $response['Status'] == "successful") {
    
                        
                        $updateOrder = Order::where('ref', $ref)->first();
                        $updateOrder->status = 1;
                        $updateOrder->response = isset($response['api_response']) ? $response['api_response'] : null ;
                        $updateOrder->save();
                        $msg = isset($response['api_response']) ? $response['api_response'] : null ;
                        $this->processReferralRewards($updateOrder);
    
    
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->coolsub_plan_id, $ref, $subcategory->telegram, $msg);
    
                        return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $msg);
                    }
                
            
            }
            
            elseif (!is_null($subcategory->description) && $subcategory->description == "ZOEDATAPORTAL") {
                
                // $plan_id = $getSelectedProduct->code;
                $product_code = isset($getSelectedProduct->zoe_data_plan_id) ? $getSelectedProduct->zoe_data_plan_id : null;
                $network_id = $this->parseTBCHPortalID(strtolower($network));
                $plan_id = isset($getSelectedProduct->tbch_code) ? $getSelectedProduct->tbch_code : null;
                
                //$network_id = $this->parseAutoPilotNetworkID(strtolower($network));
                //$plan_id = isset($getSelectedProduct->vtuplug_product_id) ? $getSelectedProduct->vtuplug_product_id : null;
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amountActual;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();
                
                if($subcategory->id == 45 && strtolower($getSelectedProduct->plan) == strtolower("GET 3GB for 7 Days")){
                    try {
                    $response = $this->tbchPortal($network_id, $plan_id, $phone, $ref);
    
                    } catch (\Throwable $e) {
                        return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                    }
    
                    \Log::info("TBCH PORTAL RESPONSE");
                    \Log::info(print_r($response, true));
    
                    if ($response['code'] == 109 || $response['code'] == 110 || $response['code'] == 111 || $response['code'] == 112 || $response['code'] == 113 || $response['code'] == 114 || $response['code'] == 115 || $response['code'] == 116 || $response['code'] == 120 || $response['code'] == 121 || $response['code'] == 122 || $response['code'] == 310 || $response['code'] == 311 || $response['code'] == 401 || $response['code'] == 402 || $response['code'] == 601) {
                        $response_msg = isset($response['message']) ? $response['message'] : null;
                        
                        $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                        
    
                        $msg = $response['message'];
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->tbch_code, $ref, $subcategory->telegram, $msg);
    
                        $resp_msg = "Data is not available,please try again later";
    
    
                        return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
    
                    } elseif (!isset($response) || is_null($response) || $response['data']['status'] == "Pending" || $response['data']['status'] == "Processing") {
    
                        return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
    
                    } elseif ($response['code'] == "00" && $response['data']['status'] == "Successful") {
    
                        
                        $updateOrder = Order::where('ref', $ref)->first();
                        $updateOrder->status = 1;
                        $updateOrder->response = $response['data']['gateway_response'];
                        $updateOrder->save();
                        $msg = $response['data']['gateway_response'] . " TbchPortal Ref: " . $response['data']['customer_ref'];
                        $this->processReferralRewards($updateOrder);
    
    
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->tbch_code, $ref, $subcategory->telegram, $msg);
    
                        return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['data']['gateway_response']);
                }
                
                }elseif($subcategory->id == 45 && strtolower($getSelectedProduct->plan) == strtolower("GET 1GB for 14 Days")){
                    try {
                    $response = $this->tbchPortal($network_id, $plan_id, $phone, $ref);
    
                    } catch (\Throwable $e) {
                        return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                    }
    
                    \Log::info("TBCH PORTAL RESPONSE");
                    \Log::info(print_r($response, true));
    
                    if ($response['code'] == 109 || $response['code'] == 110 || $response['code'] == 111 || $response['code'] == 112 || $response['code'] == 113 || $response['code'] == 114 || $response['code'] == 115 || $response['code'] == 116 || $response['code'] == 120 || $response['code'] == 121 || $response['code'] == 122 || $response['code'] == 310 || $response['code'] == 311 || $response['code'] == 401 || $response['code'] == 402 || $response['code'] == 601) {
                        $response_msg = isset($response['message']) ? $response['message'] : null;
                        
                        $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                        
    
                        $msg = $response['message'];
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->tbch_code, $ref, $subcategory->telegram, $msg);
    
                        $resp_msg = "Data is not available,please try again later";
    
    
                        return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
    
                    } elseif (!isset($response) || is_null($response) || $response['data']['status'] == "Pending" || $response['data']['status'] == "Processing") {
    
                        return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
    
                    } elseif ($response['code'] == "00" && $response['data']['status'] == "Successful") {
    
                        
                        $updateOrder = Order::where('ref', $ref)->first();
                        $updateOrder->status = 1;
                        $updateOrder->response = $response['data']['gateway_response'];
                        $updateOrder->save();
                        $msg = $response['data']['gateway_response'] . " TbchPortal Ref: " . $response['data']['customer_ref'];
                        
                        $this->processReferralRewards($updateOrder);
    
    
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->tbch_code, $ref, $subcategory->telegram, $msg);
    
                        return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['data']['gateway_response']);
                }
                    
                }elseif($subcategory->id == 45 && strtolower($getSelectedProduct->plan) == strtolower("GET 2GB for 14 Days")){
                    try {
                    $response = $this->tbchPortal($network_id, $plan_id, $phone, $ref);
    
                    } catch (\Throwable $e) {
                        return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                    }
    
                    \Log::info("TBCH PORTAL RESPONSE");
                    \Log::info(print_r($response, true));
    
                    if ($response['code'] == 109 || $response['code'] == 110 || $response['code'] == 111 || $response['code'] == 112 || $response['code'] == 113 || $response['code'] == 114 || $response['code'] == 115 || $response['code'] == 116 || $response['code'] == 120 || $response['code'] == 121 || $response['code'] == 122 || $response['code'] == 310 || $response['code'] == 311 || $response['code'] == 401 || $response['code'] == 402 || $response['code'] == 601) {
                        $response_msg = isset($response['message']) ? $response['message'] : null;
                        
                        $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                        
    
                        $msg = $response['message'];
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->tbch_code, $ref, $subcategory->telegram, $msg);
    
                        $resp_msg = "Data is not available,please try again later";
    
    
                        return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
    
                    } elseif (!isset($response) || is_null($response) || $response['data']['status'] == "Pending" || $response['data']['status'] == "Processing") {
    
                        return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
    
                    } elseif ($response['code'] == "00" && $response['data']['status'] == "Successful") {
    
                        
                        $updateOrder = Order::where('ref', $ref)->first();
                        $updateOrder->status = 1;
                        $updateOrder->response = $response['data']['gateway_response'];
                        $updateOrder->save();
                        $msg = $response['data']['gateway_response'] . " TbchPortal Ref: " . $response['data']['customer_ref'];
                        $this->processReferralRewards($updateOrder);
    
    
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->tbch_code, $ref, $subcategory->telegram, $msg);
    
                        return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['data']['gateway_response']);
                }
                    
                }elseif($subcategory->id == 45 && strtolower($getSelectedProduct->plan) == strtolower("GET 3GB for 14 Days")){
                    try {
                    $response = $this->tbchPortal($network_id, $plan_id, $phone, $ref);
    
                    } catch (\Throwable $e) {
                        return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                    }
    
                    \Log::info("TBCH PORTAL RESPONSE");
                    \Log::info(print_r($response, true));
    
                    if ($response['code'] == 109 || $response['code'] == 110 || $response['code'] == 111 || $response['code'] == 112 || $response['code'] == 113 || $response['code'] == 114 || $response['code'] == 115 || $response['code'] == 116 || $response['code'] == 120 || $response['code'] == 121 || $response['code'] == 122 || $response['code'] == 310 || $response['code'] == 311 || $response['code'] == 401 || $response['code'] == 402 || $response['code'] == 601) {
                        $response_msg = isset($response['message']) ? $response['message'] : null;
                        
                        $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                        
    
                        $msg = $response['message'];
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->tbch_code, $ref, $subcategory->telegram, $msg);
    
                        $resp_msg = "Data is not available,please try again later";
    
    
                        return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
    
                    } elseif (!isset($response) || is_null($response) || $response['data']['status'] == "Pending" || $response['data']['status'] == "Processing") {
    
                        return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
    
                    } elseif ($response['code'] == "00" && $response['data']['status'] == "Successful") {
    
                        
                        $updateOrder = Order::where('ref', $ref)->first();
                        $updateOrder->status = 1;
                        $updateOrder->response = $response['data']['gateway_response'];
                        $updateOrder->save();
                        $msg = $response['data']['gateway_response'] . " TbchPortal Ref: " . $response['data']['customer_ref'];
                        $this->processReferralRewards($updateOrder);
    
    
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->tbch_code, $ref, $subcategory->telegram, $msg);
    
                        return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['data']['gateway_response']);
                }
                    
                }elseif($subcategory->id == 45 && strtolower($getSelectedProduct->plan) == strtolower("GET 500MB for 30 Days")){
                    try {
                    $response = $this->tbchPortal($network_id, $plan_id, $phone, $ref);
    
                    } catch (\Throwable $e) {
                        return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                    }
    
                    \Log::info("TBCH PORTAL RESPONSE");
                    \Log::info(print_r($response, true));
    
                    if ($response['code'] == 109 || $response['code'] == 110 || $response['code'] == 111 || $response['code'] == 112 || $response['code'] == 113 || $response['code'] == 114 || $response['code'] == 115 || $response['code'] == 116 || $response['code'] == 120 || $response['code'] == 121 || $response['code'] == 122 || $response['code'] == 310 || $response['code'] == 311 || $response['code'] == 401 || $response['code'] == 402 || $response['code'] == 601) {
                        $response_msg = isset($response['message']) ? $response['message'] : null;
                        
                        $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                        
    
                        $msg = $response['message'];
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->tbch_code, $ref, $subcategory->telegram, $msg);
    
                        $resp_msg = "Data is not available,please try again later";
    
    
                        return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
    
                    } elseif (!isset($response) || is_null($response) || $response['data']['status'] == "Pending" || $response['data']['status'] == "Processing") {
    
                        return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
    
                    } elseif ($response['code'] == "00" && $response['data']['status'] == "Successful") {
    
                        
                        $updateOrder = Order::where('ref', $ref)->first();
                        $updateOrder->status = 1;
                        $updateOrder->response = $response['data']['gateway_response'];
                        $updateOrder->save();
                        $msg = $response['data']['gateway_response'] . " TbchPortal Ref: " . $response['data']['customer_ref'];
                        $this->processReferralRewards($updateOrder);
    
    
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->tbch_code, $ref, $subcategory->telegram, $msg);
    
                        return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['data']['gateway_response']);
                }
                }else{
                

                try {
                     \Log::info("ZOE DATA PORTAL RESPONSE REQUEST");
                \Log::info($phone);
                \Log::info($product_code);
                \Log::info($ref);
                    $response = $this->zoeDataPortal($product_code, $phone, $ref);
                     \Log::info("ZOE DATA PORTAL RESPONSE REQUEST");
                \Log::info($response);

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                \Log::info("ZOE DATA PORTAL RESPONSE");
                \Log::info($response);

                if (isset($response) && isset($response['text_status']) && $response['text_status'] == "FAILED") {
                    $response_msg = !is_null($response['data']['true_response']) ? $response['data']['true_response'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    

                    // $msg = $response['data']['gateway_response'];
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->zoe_data_plan_id, $ref, $subcategory->telegram);

                    $msg = !is_null($response['data']['true_response']) ? $response['data']['true_response'] : '';


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                }elseif (isset($response) && isset($response['data']['text_status']) && $response['data']['text_status'] == "FAILED") {
                    $response_msg = !is_null($response['data']['true_response']) ? $response['data']['true_response'] : null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    

                    // $msg = $response['data']['gateway_response'];
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->zoe_data_plan_id, $ref, $subcategory->telegram);

                    $msg = !is_null($response['data']['true_response']) ? $response['data']['true_response'] : '';


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                }elseif (isset($response) && isset($response['data']['text_status']) && $response['data']['text_status'] == "PENDING") {
                    
                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif (!isset($response) || is_null($response) || $response['text_status'] == "PENDING" || $response['text_status'] == "PROCESSING") {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif (isset($response) && $response['text_status'] == "COMPLETED") {

                    
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = !is_null($response['data']['true_response']) ? $response['data']['true_response'] : '';
                    $updateOrder->save();
                    $msg = !is_null($response['data']['true_response']) ? $response['data']['true_response'] : '';
                    $this->processReferralRewards($updateOrder);


                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->zoe_data_plan_id, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $response['data']['true_response']);
                } else {
                    $updOrder = Order::where('ref', $ref)->first();
                    $updOrder->status = 0;
                    $updOrder->save();

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                }
                
                }
                
            
            }
            
            elseif (!is_null($subcategory->description) && $subcategory->description == "AFRIPORTAL") {

                $afri_network_id = isset($getSelectedProduct->afri_network_id) ? $getSelectedProduct->afri_network_id : null;
                $afri_plan_id = isset($getSelectedProduct->afri_plan_id) ? $getSelectedProduct->afri_plan_id : null;
                
                $product_id = $getSelectedProduct->product_id;
                $variation_code = $getSelectedProduct->variation_code;
                $pin = $getSelectedProduct->pin;
                
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amountActual;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();
         

                    try {
                        $response = $this->afriPortal($afri_network_id, $phone, $afri_plan_id,$ref);
                    } catch (\Throwable $e) {
                        return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                    }
    
                    \Log::info("AFRI PORTAL RESPONSE");
                    \Log::info($response);
    
                    if (isset($response) && isset($response['Status']) && $response['Status'] == "failed") {
                      
                        $response_msg = isset($response['api_response']) ? $response['api_response'] : null;
                        
                         $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                       
    
                        $msg = isset($response['api_response']) ? $response['api_response'] : null ;
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->afri_plan_id, $ref, $subcategory->telegram);
    
                        return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
    
                    } elseif (isset($response) && isset($response['error'][0])) {
                        $response_msg = isset($response['api_response']) ? $response['api_response'] : null ;
                        
                         $this->refundUser(
                            $ref,
                            $response_msg,
                            $amountActual,
                            [
                                'bal' => $prev,
                                'prev_bal' => $bal
                            ]
                        );
                        
                        $msg = isset($response['api_response']) ? $response['api_response'] : null ;
                        
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->afri_plan_id, $ref, $subcategory->telegram);
    
    
                        return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);
    
                    } elseif (!isset($response) || is_null($response) || $response['Status'] == "pending" || $response['Status'] == "processing") {
    
                        return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");
    
                    } elseif (isset($response) && $response['Status'] == "successful") {
    
                        
                        $updateOrder = Order::where('ref', $ref)->first();
                        $updateOrder->status = 1;
                        $updateOrder->response = isset($response['api_response']) ? $response['api_response'] : null ;
                        $updateOrder->save();
                        $msg = isset($response['api_response']) ? $response['api_response'] : null ;
                        $this->processReferralRewards($updateOrder);
    
    
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $getSelectedProduct->afri_plan_id, $ref, $subcategory->telegram, $msg);
    
                        return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Purchase successful", $msg);
                    }
                
            
            }
            

            else {
                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amountActual;
                $order->amount = $amountActual;
                $order->quantity = 1;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->data_size = $getSelectedProduct->data_size;
                $order->cost_price = $getSelectedProduct->cost_price ?? 0.00;
                $order->api_route = $subcategory->description;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->phone = $phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " " . $plan .
                    " at N" . $amountActual;
                $order->status = 0;
                $order->save();
            }


        }
    }
    

    public function purchaseBucketData(Request $request)
    {
        $plan = $request->plan_id;
        \Log::info($plan);
        $phone = $this->formatPhoneNumber($request->phonenumber);
        $ref = $this->referenceCode();
        $custom_reference = $request->custom_reference != null ? $request->custom_reference : null;
        $subcategory = Subcategory::find($request->subcategory_id);
        if ($subcategory->title === "AIRTEL CG") {
            $bucket_title = "AIRTEL EDS";
        } else {
            $bucket_title = $subcategory->title;
        }


        $bucket = Bucket::where('title', $bucket_title)->first();

        if (is_null($bucket)) {
            return $this->sendError("Bucket is not available", "Bucket is not available");
        }

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


        $wallet_name = $this->getBucketTitle($bucket->id);


        $productCollection = collect(json_decode($bucket->products));
        $getSelectedProduct = $productCollection->where('plan', $plan)->first();

        $data_cost = $getSelectedProduct->data_size / 1000;
        $data_cost = floatval($data_cost);

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
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
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
                    $response = $this->purchaseAirtimeNigeria($plan_id, $phone, $ref);
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                \Log::info("AIRTIME NIGERIA");
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

                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->aa_package_code, $ref, $bucket->telegram);
                    $resp_msg = "Data is not available, please try again later";


                    return $this->sendError3($ref, $custom_reference, $resp_msg, "Transaction failed", "Transaction failed");
                } else {

                    $updateOrder = BucketOrder::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['details']['gateway_response'];
                    $updateOrder->save();
                    $msg = $response['details']['gateway_response'] . " AIRTIMENIGERIA Ref: " . $response['details']['reference'];


                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->aa_package_code, $ref, $bucket->telegram, $msg);

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

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
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
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
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
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
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

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
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

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
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

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
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

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
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

                    $resp_msg = $msg;

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

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                \Log::info("TBCH PORTAL RESPONSE");
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
                    $order->response = $response['data']['message'];
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

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
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

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                \Log::info("BUCKET MTN PORTAL APP RESPONSE");
                \Log::info($response);
                \Log::info($request);
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
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->mtn_share_id, $ref, $bucket->telegram, $msg);

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
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->mtn_share_id, $ref, $bucket->telegram, $msg);

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
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->mtn_share_id, $ref, $bucket->telegram, $msg);

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
                        $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $share_id, $ref, $bucket->telegram, $msg);

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
                        $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->mtn_share_id, $ref, $bucket->telegram, $msg);

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
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->mtn_share_id, $ref, $bucket->telegram, $msg);

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
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->mtn_share_id, $ref, $bucket->telegram, $msg);

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
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->mtn_share_id, $ref, $bucket->telegram, $msg);

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
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->mtn_share_id, $ref, $bucket->telegram, $msg);

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
                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->mtn_share_id, $ref, $bucket->telegram, $msg);

                    //$msg = "Data is not available,please try again later";


                    return $this->sendError3($ref, $custom_reference, $msg, "Transaction failed: " . $msg, "Transaction failed: " . $msg);

                } elseif (isset($response['statusCode']) && $response['statusCode'] == '0000') {


                    $updateOrder = BucketOrder::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $response['data']['notification'];
                    $updateOrder->save();
                    $msg = $response['data']['notification'] . " MTNNG Ref: " . $response['transactionId'] . " SWIFTLINK REF: " . $ref;


                    $this->sendTelegramMessage($data_cost, $phone, $bucket->pins, $getSelectedProduct->mtn_share_id, $ref, $bucket->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $data_cost, $plan . " Purchase successful", $response['data']['notification']);
                }
            } elseif (!is_null($bucket->description) && $bucket->description == "MTNCG") {

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

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
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

                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
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



    public function purchaseCable(Request $request)
    {
        $plan = $request->plan;
        $amount = $request->amount;
        $phone = $request->phonenumber;
        $variation_code = $request->variation_code;
        
        if($phone == "07036028966" || $phone == "09037303701" || $phone == "+2347036028966" || $phone == "2347036028966" || $phone == "+2349037303701" || $phone == "2349037303701" ){
            return $this->sendResponse("Error","Error");
        }
        
        $custom_reference = $request->custom_reference != null ? $request->custom_reference : null;
        
        // 🔐 FETCH PLAN DETAILS FROM PROVIDER (SERVER-SIDE)
        $bouquetsResponse = $this->fetchBouquet(new Request([
            'plan' => $plan
        ]));

        $bouquets = $bouquetsResponse->getData()->data ?? [];

        if (empty($bouquets)) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to fetch cable plans'
            ], 400);
        }

        $selectedBouquet = collect($bouquets)->firstWhere(
            'variation_code',
            $variation_code
        );

        if (!$selectedBouquet) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid cable package selected'
            ], 400);
        }

        /*
            |--------------------------------------------------------------------------
            | LOCK AMOUNT FROM PROVIDER (SECURITY)
            |--------------------------------------------------------------------------
        */

        $amount = $selectedBouquet->variation_amount ?? $selectedBouquet['variation_amount'] ?? null;


        if (!$amount) {
            return response()->json([
                'status' => false,
                'message' => 'Package amount not found'
            ], 400);
        }

        // $ref = $this->vtPassreferenceCode();
        $subcategory = Subcategory::where('title', $request->plan)->first();

        if (!is_null($subcategory->description) && $subcategory->description == "VTPASS") {
            $ref = $this->vtPassreferenceCode();
        } elseif (!is_null($subcategory->description) && $subcategory->description == "RINGO") {
            $ref = $this->ringoReferenceCode();
        }
        $productCollection = json_decode($subcategory->products);
        $discount = $this->getUserLevel($productCollection, $this->user->userlevel);

        $serviceID = strtolower($subcategory->title);
        $amountActual = $amount - (($discount / 100) * $amount);

        if ($subcategory->status == 0) {
            return $this->sendError('Product not available right now', 'Product not available right');
        } else {

            $gate = (string) $request->header('X-Gate-Token');
            $expectedGate = (string) config('app.GATE_TOKEN');
            
            $isPalmPayRequest = $expectedGate !== ''
                && hash_equals($expectedGate, $gate);
            
            if (!$isPalmPayRequest && !$this->isDebited($amountActual)) {
                return $this->sendError(
                    'Insufficient Balance for this transaction',
                    'Insufficient Balance for this transaction'
                );
            }

            
            $prev = $this->user->wallet;

            $bal = $this->user->wallet - $amountActual;


            if (!is_null($subcategory->description) && $subcategory->description == "VTPASS") {


                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
                $order->amount = $amount;
                $order->quantity = 1;
                $order->phone = $phone;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->iuc = $request->cardno;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->api_route = "VTPASS";

                $order->description = $subcategory->title . " " . $plan . " at N" . $amount;
                $order->amount = $amountActual;
                $order->status = 0;
                $order->save();


                try {
                    $response = $this->VtPassPurchase($request->cardno, $serviceID, $request->variation_code, $amount, $phone, $ref);
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }
                
                \Log::info("VTPASS RESPONSE");
                \Log::info($response);

                if (!isset($response)) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif($response['code'] == "016" || $response['code'] == "040" || $response['code'] == "013"){
                    
                    $response_msg = null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    return $this->sendError2($ref, $custom_reference, "Transaction failed.", "Transaction failed." . $response['code']);
                
                } else {
                    $purchasedCode = $response['purchased_code'] ?? null;
                    
                    if ($response['content']['transactions']['status'] == "delivered") {
                        
                        $updateOrder = Order::where('ref', $ref)->first();
                        $updateOrder->status = 1;
                        
                        //$updateOrder->description = $updateOrder->description . ' ' . $response['purchased_code'];
                        
                        $updateOrder->description = $purchasedCode ? trim($updateOrder->description . ' ' . $purchasedCode) : $updateOrder->description;
        
                        $updateOrder->save();
                        $msg = $response['response_description'] . " VTPASS Ref: " . $response['content']['transactions']['transactionId'] . " Showmax Voucher: " . $response['purchased_code'];
                        
                        $this->processReferralRewards($updateOrder);
                        
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $subcategory->pins, $ref, $subcategory->telegram, $msg);

                        return $this->sendResponse3($ref, $custom_reference, $amountActual, $plan . " Payment successful", $plan . " Payment successful Voucher has been added to the transaction history", $response['purchased_code']);
                    } else {
                        $statusmsg = $response['content']['transactions']['status'];
                        $updateOrder = Order::where('ref', $ref)->first();
                        $updateOrder->status = 0;
                        //$updateOrder->description = $updateOrder->description . ' ' . $response['purchased_code'];
                        
                        $updateOrder->description = $purchasedCode ? trim($updateOrder->description . ' ' . $purchasedCode) : $updateOrder->description;
                        
                        $updateOrder->save();
                        $msg = $response['response_description'] . " VTPASS Ref: " . $response['content']['transactions']['transactionId'] . " Showmax Voucher: " . $response['purchased_code'];

                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $subcategory->pins, $ref, $subcategory->telegram, $msg);
                        return $this->sendResponse3($ref, $custom_reference, $amountActual, $plan . " Payment successful", $plan . " Payment successful Voucher has been added to the transaction history", $response['purchased_code']);

                        // return $this->sendResponse2($ref,$plan . " Payment $statusmsg", $plan . " Payment $statusmsg and will be completed shortly");
                    }
                }
            }


            if (!is_null($subcategory->description) && $subcategory->description == "RINGO") {


                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " " . $plan . " at N" . $amount;
                $order->amount = $amount;
                $order->quantity = 1;
                $order->phone = $phone;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->iuc = $request->cardno;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->api_route = "RINGO";
                $order->description = $subcategory->title . " " . $plan . " at N" . $amount;
                $order->amount = $amountActual;
                $order->status = 0;
                $order->save();



                try {
                    $response = $this->purchaseRingoCable($request->cardno, $serviceID, $request->variation_code, $ref, $request->amount);
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                if (!isset($response)) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif (is_null($response) || ($response['status'] != '200')) {


                    return $this->sendError2($ref, $custom_reference, "Transaction failed.", "Transaction failed." . $response['message']);
                } else {
                    if ($response['status'] == "200") {

                        $updateOrder = Order::where('ref', $ref)->first();
                        $updateOrder->status = 1;
                        $updateOrder->save();
                        $msg = $response['message'] . " Ringo Ref: " . $response['transref'];
                        $this->processReferralRewards($updateOrder);

                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $subcategory->pins, $ref, $subcategory->telegram, $msg);

                        return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Payment successful", $plan . " Payment successful");
                    } else {
                        //$statusmsg =  $response['content']['transactions']['status'];
                        $updateOrder = Order::where('ref', $ref)->first();
                        $updateOrder->status = 0;
                        $updateOrder->save();
                        $msg = $response['message'] . " Ringo Ref: " . $response['transref'];

                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $subcategory->pins, $ref, $subcategory->telegram, $msg);
                        return $this->sendResponse2($ref, $custom_reference, $amountActual, $plan . " Payment failed", $plan . " Payment failed");

                        //return $this->sendResponse2($ref,$plan . " Payment successful", $plan . " Payment successful and will be completed shortly");
                    }
                }
            }

        }

    }

    public function purchaseElectricity(Request $request)
    {
        // $plan = $request->plan;
        $amount = $request->amount;
        $phone = $request->phonenumber;
        $custom_reference = $request->custom_reference != null ? $request->custom_reference : null;
        
        if($phone == "07036028966" || $phone == "09037303701" || $phone == "+2347036028966" || $phone == "2347036028966" || $phone == "+2349037303701" || $phone == "2349037303701" ){
            return $this->sendResponse("Error","Error");
        }
        \Log::info("ELECTRICITY PURCHASE");
        \Log::info($request);


        $subcategory = Subcategory::where('title', $request->serviceID)->first();

        if (!is_null($subcategory->description) && $subcategory->description == "VTPASS") {
            $ref = $this->vtPassreferenceCode();
        } elseif (!is_null($subcategory->description) && $subcategory->description == "RINGO") {
            $ref = $this->ringoReferenceCode();
        }

        $rec = explode('-', $request->serviceID);
        $recIndex = $rec[1];
        $serv = $rec[0];
        $val = str_replace(' ', '-', trim($recIndex));
        $plan = trim($val);


        $productCollection = json_decode($subcategory->products);
        $discount = $this->getUserLevel($productCollection, $this->user->userlevel);

        $serviceID = strtolower($plan);
        $amountActual = $amount - (($discount / 100) * $amount);

        if ($subcategory->status == 0) {
            return $this->sendError('Product not available right now', 'Product not available right');
        } else {
            $gate = (string) $request->header('X-Gate-Token');
            $expectedGate = (string) config('app.GATE_TOKEN');
            
            $isPalmPayRequest = $expectedGate !== ''
                && hash_equals($expectedGate, $gate);
            
            if (!$isPalmPayRequest && !$this->isDebited($amountActual)) {
                return $this->sendError(
                    'Insufficient Balance for this transaction',
                    'Insufficient Balance for this transaction'
                );
            }
            $prev = $this->user->wallet;

            $bal = $this->user->wallet - $amountActual;


            if (!is_null($subcategory->description) && $subcategory->description == "VTPASS") {


                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " at N" . $amount;
                $order->amount = $amount;
                $order->quantity = 1;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->meter = $request->cardno;
                $order->bal = $bal;
                $order->phone = $phone;

                $order->prev_bal = $prev;
                $order->description = $subcategory->title . " at N" . $amount;
                $order->status = 0;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->api_route = "VTPASS";
                $order->save();

                try {
                    $response = $this->VtPassPurchase($request->cardno, $serviceID, $request->variation_code, $amount, $phone, $ref);
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                if (!isset($response)) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif($response['code'] == "016" || $response['code'] == "040" || $response['code'] == "013"){
                    
                     $response_msg = null;
                    
                    $this->refundUser(
                        $ref,
                        $response_msg,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal
                        ]
                    );
                    
                    return $this->sendError2($ref, $custom_reference, "Transaction failed.", "Transaction failed." . $response['code']);
                
                } else {

                    if ($response['content']['transactions']['status'] == "delivered") {
                        $updateOrder = Order::where('ref', $ref)->first();
                        $updateOrder->status = 1;
                        $updateOrder->description = $updateOrder->description . ' ' . $response['purchased_code'];
                        $updateOrder->save();
                        $msg = $response['response_description'] . " VTPASS Ref: " . $response['content']['transactions']['transactionId'] . " Meter Token: " . $response['purchased_code'];

                        $this->processReferralRewards($updateOrder);
                        
                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $subcategory->pins, $ref, $subcategory->telegram, $msg);

                        return $this->sendResponse3($ref, $custom_reference, $amountActual, $request->serviceID . " Payment successful", $request->serviceID . " Payment successful token has been added to the transaction history", $response['purchased_code']);
                    } else {
                        $statusmsg = $response['content']['transactions']['status'];
                        $updateOrder = Order::where('ref', $ref)->first();
                        $updateOrder->status = 0;
                        $updateOrder->description = $updateOrder->description . ' ' . $response['purchased_code'];
                        $updateOrder->save();
                        $msg = $response['response_description'] . " VTPASS Ref: " . $response['content']['transactions']['transactionId'] . " Meter Token: " . $response['purchased_code'];

                        $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $subcategory->pins, $ref, $subcategory->telegram, $msg);

                        return $this->sendResponse3($ref, $custom_reference, $amountActual, $request->serviceID . " Payment $statusmsg", $request->serviceID . " Payment $statusmsg token has been added to the transaction history", $response['purchased_code']);
                    }
                }
            }

            if (!is_null($subcategory->description) && $subcategory->description == "RINGO") {


                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " at N" . $amount;
                $order->amount = $amount;
                $order->quantity = 1;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->iuc = $request->cardno;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';
                $order->api_route = "RINGO";
                $order->description = $subcategory->title . " at N" . $amount;
                $order->status = 0;
                $order->save();


                try {
                    $response = $this->purchaseRingo($request->cardno, $serv, $request->variation_code, $amount, $phone, $ref);
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                if (!isset($response)) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif (is_null($response) || !isset($response['status']) || $response['status'] != "200") {
                    return $this->sendError2($ref, $custom_reference, "Sorry we could not perform the transaction", "Sorry we could not perform the transaction ");
                } else {

                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->description = $updateOrder->description . ' Token :' . $response['token'];
                    $updateOrder->save();
                    $msg = $response['message'] . " RINGO Ref: " . $response['transref'] . " Meter Token: " . $response['token'];
                    $this->processReferralRewards($updateOrder);

                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $subcategory->pins, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse3($ref, $custom_reference, $amountActual, $plan . " Payment successful  a pin has been sent to added to the transaction history", $plan . " Payment successful  a pin has been sent to added to the transaction history.", $response['token']);
                }
            }

        }
    }

    public function purchaseExam(Request $request)
    {
        \Log::info("EXAM PURCHASE");
        \Log::info($request->all());
        
        $plan = $request->plan;
        $amount = $request->amount;
        $phone = $request->phonenumber;
        $custom_reference = $request->custom_reference != null ? $request->custom_reference : null;
        
        


        $ref = $this->vtPassreferenceCode();
        $subcategory = Subcategory::where('title', $request->plan)->first();

        $productCollection = json_decode($subcategory->products);
        $amount = $this->getUserLevel($productCollection, $this->user->userlevel);

        $serviceID = strtolower($subcategory->title);
        // $amountActual = $amount - (($discount / 100) * $amount);

        $amountActual = $amount;

        if ($subcategory->status == 0) {
            return $this->sendError('Product not available right now', 'Product not available right');
        } else {
            $gate = (string) $request->header('X-Gate-Token');
            $expectedGate = (string) config('app.GATE_TOKEN');
            
            $isPalmPayRequest = $expectedGate !== ''
                && hash_equals($expectedGate, $gate);
            
            if (!$isPalmPayRequest && !$this->isDebited($amountActual)) {
                return $this->sendError(
                    'Insufficient Balance for this transaction',
                    'Insufficient Balance for this transaction'
                );
            }
            $prev = $this->user->wallet;

            $bal = $this->user->wallet - $amountActual;



            if (!is_null($subcategory->description) && $subcategory->description == "VTPASS") {


                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title . " at N" . $amountActual;
                $order->amount = $amount;
                $order->quantity = 1;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->iuc = $request->cardno;
                $order->phone = $request->phone;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';

                $order->description = $subcategory->title . " at N" . $amountActual;
                $order->status = 0;
                $order->save();


                try {
                    $response = $this->VtPassPurchase($request->cardno, $serviceID, $request->variation_code, $amount, $phone, $ref);
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                if (!isset($response)) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif (is_null($response) || ($response['code'] != '000')) {
                    return $this->sendError2($ref, $custom_reference, "Sorry we could not perform the transaction", "Sorry we could not perform the transaction " . $response['code']);
                } else {
                    $purchased_code = $response['purchased_code'];
 
                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->description = $updateOrder->description . $response['purchased_code'];
                    $updateOrder->iuc = $response['purchased_code'];
                    $updateOrder->save();
                    $msg = $response['response_description'] . " VTPASS Ref: " . $response['content']['transactions']['transactionId'] . " Details: " . $response['purchased_code'];
                    $this->processReferralRewards($updateOrder);
                    $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $subcategory->pins, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $purchased_code, $plan . " Payment successful  a pin has been sent to added to the transaction history.");
                }
            }
            if (is_null($subcategory->description)) {


                $order = new Order();
                $order->ref = $ref;
                $order->custom_reference = $custom_reference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->phone = $phone;
                $order->plan = $subcategory->title . " at N" . $amountActual;
                $order->amount = $amount;
                $order->quantity = 1;
                $order->subtotal = $amountActual;
                $order->total = $amountActual;
                $order->iuc = $request->cardno;
                $order->bal = $bal;
                $order->prev_bal = $prev;
                $order->channel = is_null($request->channel) ? 'Web' : 'App';

                $order->description = $subcategory->title . " at N" . $amountActual;
                $order->status = 0;
                $order->save();


                try {
                    if($subcategory->title == "WAEC"){
                        $response = WaecPins::where(["used" => 0,"variation_code" => $request->variation_code])->first();
                        if(!is_null($response)){
                            $purchased_code = "Pin No: ".$response->pin_no."\n". "Serial No:".$response->serial_no;
                        }else{
                            $response = null;
                        }
                    }elseif($subcategory->title == "NECO"){
                        $response = NecoPins::where(["used" => 0, "variation_code" => $request->variation_code])->first();
                        if(!is_null($response)){
                            $purchased_code = "Pin No: ".$response->pin_no;
                        }else{
                            $response = null;
                        }
                    }elseif($subcategory->title == "JAMB"){
                        $response = JambPins::where(["used" => 0, "variation_code" => $request->variation_code])->first();
                        if(!is_null($response)){
                            $purchased_code = "Pin No: ".$response->pin_no;
                        }else{
                            $response = null;
                        }
                    }



                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }


                if (!isset($response) ) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif (is_null($response)) {
                    return $this->sendError2($ref, $custom_reference, "Sorry we could not perform the transaction", "Sorry we could not perform the transaction");
                } else {

                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->response = $updateOrder->description." ".$purchased_code;
                    $updateOrder->iuc = $purchased_code;
                    $updateOrder->save();

                    // update waecpins
                    $response->used = 1;
                    $response->save();
                    $this->processReferralRewards($updateOrder);

                    $msg = "Transaction Successful" . " Pluginng Ref: " . $custom_reference . " Details: " . $purchased_code;

                    // $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $subcategory->pins, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $purchased_code, $plan . " Payment successful  a pin has been sent to added to the transaction history.");
                }
            }
        }

    }

    public function initiateA2cash(Request $request)
    {
        \Log::info($request->tphone);

        try {
            $response = $this->generateMtnOtp($request->tphone);
            \Log::info("InitiateA2Cash");
            \Log::info($response);
        } catch (Exception $e) {
            return $this->sendError("An error occurred: $e", "An error occurred");
        }

        return $this->sendResponse($response, "Otp sent successfully");


    }

    public function purchaseA2cash(Request $request)
    {

        \Log::info($request);

        $ref = $this->referenceCode();
        $otp = $request->otp != null ? $request->otp : null;
        $pin = $request->pin != null ? $request->pin : null;
        $custom_reference = $request->custom_reference != null ? $request->custom_reference : null;

        $amount = $request->tamount;
        $phone = $request->tphone;
        $subcategory = Subcategory::find($request->subcategory_id);

        $amount = intVal($amount);
        $productCollection = json_decode($subcategory->products);
        //$getSelectedProduct = $productCollection->first();
        $discount = $this->getUserLevel($productCollection, $this->user->userlevel);

        $network = explode(' ', $subcategory->title)[0];
        $amountActual = $amount - (($discount / 100) * $amount);

        if (!is_null($subcategory->description2) && $subcategory->description2 == "MTNTRANSFER") {
            $prev = $this->user->wallet;

            $bal = $this->user->wallet + $amountActual;

            $order = new Order();
            $order->ref = $ref;
            $order->custom_reference = $custom_reference;
            $order->user_id = $this->user->id;
            $order->subcategory_id = $subcategory->id;
            $order->plan = $subcategory->title . " N" . $amount;
            $order->amount = $amount;
            $order->quantity = 1;
            $order->subtotal = $amountActual;
            $order->phone = $phone;
            $order->channel = is_null($request->channel) ? 'Web' : 'App';
            $order->bal = $prev;
            $order->prev_bal = $prev;

            $order->total = $amountActual;
            $order->description = $subcategory->title . " N" . $amount;
            $order->status = 0;
            $order->save();

            try {
                $response = $this->convertA2Cash($otp, $phone, $pin, $amount);
            } catch (\Throwable $e) {
                return $this->sendProviderConnectionPending(
                    $e,
                    $ref,
                    $custom_reference
                );
            }
            \Log::info("Conversion Response");
            \Log::info($response);
            if (isset($response['statusCode']) && $response['statusCode'] == '200') {
                $this->isCredited($amountActual, $this->user->id);
                $upOrder = Order::where('ref', $ref)->first();
                $upOrder->bal = $bal;
                $upOrder->status = 1;  

                $message = isset($response['statusMessage']) ? $response['statusMessage'] : "Airtime conversion successful";
                $old_balance = isset($response['old_balance']) ? $response['old_balance'] : " ";
                $new_balance = isset($response['new_balance']) ? $response['new_balance'] : " ";

                $upOrder->response = $message . " previous_airtime_balance: " . $old_balance . " new_airtime_balance: " . $new_balance;

                $upOrder->save();

                return $this->sendResponse($response, $message);

            } elseif (isset($response['error']) && !isset($response['status'])) {
                $upOrder = Order::where('ref', $ref)->first();
                $upOrder->status = 4;
                $message = isset($response['error_description']) ? $response['error_description'] : "Airtime conversion failed";

                $old_balance = isset($response['old_balance']) ? $response['old_balance'] : " ";
                $new_balance = isset($response['new_balance']) ? $response['new_balance'] : " ";

                $upOrder->response = $message . " previous_airtime_balance: " . $old_balance . " new_airtime_balance: " . $new_balance;

                $upOrder->save();

                return $this->sendError($message, $message);
            } elseif (isset($response['status'])) {
                $upOrder = Order::where('ref', $ref)->first();
                $upOrder->status = 4;

                $message = isset($response['message']) ? $response['message'] : "Airtime conversion failed!";
                $old_balance = isset($response['old_balance']) ? $response['old_balance'] : " ";
                $new_balance = isset($response['new_balance']) ? $response['new_balance'] : " ";

                $upOrder->response = $message . " previous_airtime_balance: " . $old_balance . " new_airtime_balance: " . $new_balance;

                $upOrder->save();

                return $this->sendError($message, $message);
            } else {
                $upOrder = Order::where('ref', $ref)->first();
                $upOrder->status = 4;
                $message = isset($response['message']) ? $response['message'] : "Airtime conversion failed!";

                $old_balance = isset($response['old_balance']) ? $response['old_balance'] : " ";
                $new_balance = isset($response['new_balance']) ? $response['new_balance'] : " ";

                $upOrder->response = $message . " previous_airtime_balance: " . $old_balance . " new_airtime_balance: " . $new_balance;

                $upOrder->save();

                return $this->sendError($message, $message);
            }
        } else {
            $prev = $this->user->wallet;

            $bal = $this->user->wallet + $amountActual;
            $order = new Order();
            $order->ref = $ref;
            $order->custom_reference = $custom_reference;
            $order->user_id = $this->user->id;
            $order->subcategory_id = $subcategory->id;
            $order->plan = $subcategory->title . " N" . $amount;
            $order->amount = $amount;
            $order->quantity = 1;
            $order->subtotal = $amountActual;
            $order->total = $amountActual;
            $order->phone = $phone;
            $order->bal = $bal;
            $order->prev_bal = $prev;
            $order->description = $subcategory->title . " to cash convert of N" . $amount;
            $order->status = 0;
            $order->channel = is_null($request->channel) ? 'Web' : 'App';

            $order->save();

            // $channelText = $subcategory->pins;
            // $channelText = str_replace(['AMOUNT', 'PHONE', 'REF'], [$amount, $phone, $ref], $channelText);

            $msg = "Email: " . $this->user->email . ", Name: " . $this->user->firstname . ' ' . $this->user->lastname . ", Transferred From: $phone, Amount: NGN$amount, Network: $network, REF: $ref";

            // $this->sendTelegramMessage($amount, $phone, $subcategory->pins, $subcategory->pins, $ref, $subcategory->telegram, $msg);

            return $this->sendResponse2($ref, $custom_reference, $amountActual, $subcategory->title . " to cash convert successful", $subcategory->title . " to cash convert successful. You will be credited in few mins");
        }
    }


    public function sendSMS(Request $request)
    {
        $senderID = $request->senderID;
        $phone = $request->phone;
        $message = $request->message;

        $ref = $this->referenceCode(); //work on code length;
        $custom_reference = $request->custom_reference != null ? $request->custom_reference : null;

        $subcategory = Subcategory::where('title', 'BULKSMS')->first();

        $productCollection = json_decode($subcategory->products);
        //$getSelectedProduct = $productCollection->first();
        $rate = $this->getUserLevel($productCollection, $this->user->userlevel);

        $numbers_array = explode(",", $phone);
        $no_of_numbers = count($numbers_array);


        $no_of_char = strlen($message);
        $message_count = ceil($no_of_char / 160);

        $amountActual = $rate * $no_of_numbers * $message_count;
        if ($subcategory->status == 0) {
            return $this->sendError('Product not available right now', 'Product not available right');
        } else {

            if (!$this->isDebited($amountActual)) {
                return $this->sendError('Insufficient Balance for this transaction', 'Insufficient Balance for this transaction');
            }
            $prev = $this->user->wallet;

            $bal = $this->user->wallet - $amountActual;
            
            $order = new Order();
            $order->ref = $ref;
            $order->custom_reference = $custom_reference;
            $order->user_id = $this->user->id;
            $order->subcategory_id = $subcategory->id;
            $order->plan = $subcategory->title . " at N" . $amountActual;
            $order->amount = $amountActual;
            $order->quantity = 1;
            $order->subtotal = $amountActual;
            $order->total = $amountActual;
            $order->phone = $phone;
            $order->bal = $bal;
            $order->prev_bal = $prev;
            $order->description = $subcategory->title . " at N" . $amountActual . " SenderID: $senderID Msg: $message";
            $order->status = 0;
            $order->channel = is_null($request->channel) ? 'Web' : 'App';

            $order->save();

            if (!is_null($subcategory->description) && $subcategory->description == "SMARTSMS") {

                try {
                    $response = $this->smartSMSsend($ref, $senderID, $phone, $message);
                } catch (\Throwable $e) {
                    return $this->sendProviderConnectionPending($e, $ref, $custom_reference);
                }

                if (!isset($response)) {

                    return $this->sendError2($ref, $custom_reference, "Transaction pending", "Transaction pending");

                } elseif (is_null($response) || ($response['code'] != 1000)) {
                    $updateOrder = Order::where('ref', $ref)->where('status', 0)->first();
                    $updateOrder->status = 4;
                    $updateOrder->save();

                    $this->isCredited($amountActual, $this->user->id);
                    $subcategory = Subcategory::where('title', 'Reversal')->first();

                    $order = new Order();
                    $order->ref = $ref;
                    $order->custom_reference = $custom_reference;
                    $order->user_id = $this->user->id;
                    $order->subcategory_id = $subcategory->id;
                    $order->plan = $subcategory->title . " at N" . $amountActual;
                    $order->amount = $amountActual;
                    $order->quantity = 1;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';

                    $order->subtotal = $amountActual;
                    $order->total = $amountActual;
                    $order->phone = $phone;
                    $order->bal = $prev;
                    $order->prev_bal = $bal;
                    $order->description = $subcategory->title . " at N" . $amountActual;
                    $order->status = 2;
                    $order->save();


                    return $this->sendError2($ref, $custom_reference, "Duplicate Transaction. Transaction failed. Please try after sometime.", "Duplicate Transaction. Transaction failed. Please try after sometime.");
                } else {

                    $updateOrder = Order::where('ref', $ref)->first();
                    $updateOrder->status = 1;
                    $updateOrder->description = $updateOrder->description . $response['code'];
                    $updateOrder->save();
                    $msg = " SMARTSMS Ref: " . $ref;


                    $this->sendTelegramMessage($amountActual, $phone, $subcategory->pins, $subcategory->pins, $ref, $subcategory->telegram, $msg);

                    return $this->sendResponse2($ref, $custom_reference, $amountActual, $subcategory->title . " Sent successfully", $subcategory->title . " sent successfully");
                }
            }
            elseif(!is_null($subcategory->description) && $subcategory->description == "BULKSMSNG"){
                
                try {
                    $response = BulkSMSService::sendBulkSMS(
                        $numbers_array,
                        $message,
                        $senderID
                    );
                } catch (\Throwable $e) {
                    \Log::error('BulkSMS request failed', [
                        'error' => $e->getMessage(),
                    ]);
            
                    return $this->sendError2(
                        $ref,
                        $custom_reference,
                        'Transaction pending',
                        'Transaction pending'
                    );
                }
            
                \Log::info('BULKSMS NG response', $response);
                
                $total = (int) $response['total'];
                $successful = (int) $response['successful'];
                $failed = (int) $response['failed'];
                $friendlyMessage = $response['message']
                    ?? BulkSMSService::friendlyResponse($response);
                
                $failedResults = array_values(array_filter(
                    $response['results'],
                    function ($result) {
                        return $result['status'] === 'failed';
                    }
                ));
                
                $order = Order::where('ref', $ref)->first();

                if ($successful === $total) {
                    // Complete success
                    $order->status = 1;
                    $order->description .= ' BSNG-0000';
                    $order->response = $friendlyMessage;
                    $order->save();
                
                    return $this->sendResponse2(
                        $ref,
                        $custom_reference,
                        $amountActual,
                        $friendlyMessage,
                        $friendlyMessage
                    );
                }
                
                if ($successful === 0) {
                    // Complete failure: refund everything
                    $error = isset($failedResults[0]['error'])
                        ? $failedResults[0]['error']
                        : 'All SMS messages failed';
                    $failureMessage = $friendlyMessage .
                        ' Your wallet has been refunded.';
                
                    $this->refundUser(
                        $ref,
                        $error,
                        $amountActual,
                        [
                            'bal' => $prev,
                            'prev_bal' => $bal,
                        ]
                    );
                
                    $order->description .= ' BSNG-FAILED';
                    $order->response = $failureMessage;
                    $order->save();
                
                    return $this->sendError2(
                        $ref,
                        $custom_reference,
                        $failureMessage,
                        $failureMessage
                    );
                }
                
                // Partial success
                $refundAmount = round(
                    ($amountActual / $total) * $failed,
                    2
                );
                
                $chargedAmount = round(
                    $amountActual - $refundAmount,
                    2
                );
                
                $this->refundUser(
                    $ref,
                    'Partial SMS failure: ' . $failed . ' recipient(s) failed',
                    $refundAmount,
                    [
                        'bal' => $prev,
                        'prev_bal' => $bal,
                    ]
                );

                $partialMessage = $friendlyMessage .
                    ' The charge for the failed message' .
                    ($failed === 1 ? '' : 's') .
                    ($failed === 1 ? ' has' : ' have') .
                    ' been refunded to your wallet.';
                
                // Use your application's actual partial-status value.
                $order->status = 1;
                $order->description .= ' BSNG-PARTIAL';
                $order->response = $partialMessage;
                $order->save();
                
                return $this->sendResponse2(
                    $ref,
                    $custom_reference,
                    $chargedAmount,
                    $partialMessage,
                    $partialMessage
                );

                
                
            }
        }
    }
    
    public function withdraw(Request $request)
    {
        $amount = $request->amount;
        $ref = $this->referenceCode(); //work on code length;
        $subcategory = Subcategory::where('title', 'Withdraw')->first();
        $amountActual = $amount;


        if ($amountActual > User::find($this->user->id)->wallet) {
            return $this->sendError('You cannot withdraw more than you have', 'You cannot withdraw more than you have');
        }
        if (User::find($this->user->id)->account_number == null) {
            return $this->sendError('Go to profile to update your account details', 'Go to profile to update your account details');
        }
        $prev = $this->user->wallet;

        $bal = $this->user->wallet - $amountActual;
        $bank = $this->user->bank_name . "|" . $this->user->account_number;


        $order = new Order();
        $order->ref = $ref;
        $order->user_id = $this->user->id;
        $order->subcategory_id = $subcategory->id;
        $order->plan = $subcategory->title . " $bank";
        $order->amount = $amountActual;
        $order->quantity = 1;
        $order->subtotal = $amountActual;
        $order->total = $amountActual;
        // $order->phone = $phone;
        // $order->bal = $bal;
        //$order->prev_bal = $prev;
        $order->description = $subcategory->title . " to $bank";
        $order->status = 0;
        $order->channel = is_null($request->channel) ? 'Web' : 'App';

        $order->save();

        $this->sendTelegramMessage($amountActual, $this->user->phone, $subcategory->pins, $subcategory->pins, $ref, $subcategory->telegram);

        return $this->sendResponse($subcategory->title . " completed successfully, kindly update your bank in profile", $subcategory->title . " completed successfully, kindly update your bank in profile");
    }

    public function fetchBouquet(Request $request)
    {
        $getsubcategory = Subcategory::where('title', $request->plan)->first();
        if (!is_null($getsubcategory) && $getsubcategory->description == "RINGO") {
            if ($request->plan == "STARTIMES") {
                $fetch = $this->VtPassfetchBouquet($request->plan);
            } else {
                $fetch = $this->RingofetchCable($request->plan);
            }
            return $this->sendResponse($fetch, 'Fetched successfully');
        } else if (!is_null($getsubcategory) && $getsubcategory->description == "VTPASS") {
            $fetch = $this->VtPassfetchBouquet($request->plan);
            return $this->sendResponse($fetch, 'Fetched successfully');
        } else if (is_null($getsubcategory->description)) {

            if ($request->plan == "WAEC") {
                $fetch = array(
                    "0" => array(
                        "name" => "WASSCE/GCE RESULT CHECKER",
                        "variation_code" => "waec_result_checker"
                    )
                );

            } elseif ($request->plan == "NECO") {
                $fetch = array(
                    "0" => array(
                        "name" => "NECO RESULT CHECKER",
                        "variation_code" => "neco_result_checker"
                    )
                );
            } elseif ($request->plan == "JAMB") {
                $fetch = array(
                    "0" => array(
                        "name" => "UTME",
                        "variation_code" => "utme"
                    ),
                    "1" => array(
                        "name" => "Direct Entry (DE)",
                        "variation_code" => "de"
                    ),
                );
            }
            // \Log::info($fetch);
            // $fetch = $this->VtPassfetchBouquet($request->plan);
            return $this->sendResponse($fetch, 'Fetched successfully');
        }
        // if ($request->description == "RINGO") {
        //     if ($request->plan == "STARTIMES") {
        //         $fetch =  $this->VtPassfetchBouquet($request->plan);
        //     } else {
        //         $fetch =  $this->RingofetchCable($request->plan);
        //     }

        //     return $this->sendResponse($fetch, 'Fetched successfully');
        // } else if ($request->description == "VTPASS") {
        //     $fetch =  $this->VtPassfetchBouquet($request->plan);
        //     return $this->sendResponse($fetch, 'Fetched successfully');
        // }
        else {

            return $this->sendResponse([], 'Fetched successfully');
        }
    }

    public function ringoFetchBouquet(Request $request)
    {
        //$fetch = $this->purchaseRingo($request->cardno, $request->serviceID, $request->variation_code, $request->amount, $request->phone, $request->ref);
        $fetch = $this->RingofetchCable($request->plan);
        //$this->ringoReQuery($request->ref);
        // $fetch = $this->EGMSPurchase('910', 1, $request->phone, $request->ref);

        return $this->sendResponse($fetch, 'Fetched successfully');
    }

    public function ringoVerify(Request $request)
    {
        $verify = $this->verifyRingo($request->cardno, $request->plan, $request->type);
        return $this->sendResponse($verify, 'Fetched successfully');
    }

    public function verifyCard(Request $request)
    {

        \Log::info($request->serviceID);
        \Log::info($request->plan);
        \Log::info($request->cardno);
        \Log::info($request->type);

        $getsubcategory = Subcategory::where('title', $request->plan)->first();
        if (!is_null($getsubcategory) && $getsubcategory->description == "RINGO") {
            $verify = $this->verifyRingoCable($request->cardno, $request->plan);
            return $this->sendResponse($verify, 'Verified successfully');
        } else if (is_null($getsubcategory) && !is_null($request->description) && $request->description == "RINGO") {
            $verify = $this->verifyRingo($request->cardno, $request->serviceID, $request->type);

            return $this->sendResponse($verify, 'Verified successfully');
        } else {

            $verify = is_null($request->type) ? $this->VtPassVerify($request->cardno, $request->plan)
                : $this->VtPassVerify($request->cardno, $request->plan, $request->type);

            \Log::info($verify);

            return $this->sendResponse($verify, 'Verified successfully');
        }
    }

    public function verifyCardApp(Request $request)
    {

        $getsubcategory = Subcategory::where('title', $request->plan)->first();
        if (!is_null($getsubcategory) && $getsubcategory->category_id == 3 && $getsubcategory->description == "RINGO") {
            $verify = $this->verifyRingoCable($request->cardno, $request->plan);
            return $this->sendResponse($verify, 'Verified successfully');
        } else if (is_null($getsubcategory) && !is_null($request->description) && $getsubcategory->category_id == 4 && $request->description == "RINGO") {
            $verify = $this->verifyRingo($request->cardno, $request->serviceID, $request->type);

            return $this->sendResponse($verify, 'Verified successfully');
        } else {

            $verify = is_null($request->type) ? $this->VtPassVerify($request->cardno, $request->plan)
                : $this->VtPassVerify($request->cardno, $request->plan, $request->type);

            return $this->sendResponse($verify, 'Verified successfully');
        }
    }

    public function provRepush(Request $request)
    {
        $repush = $this->providusRepush($request->settlement_id);
        if ($repush['requestSuccessful']) {
            return $this->sendResponse($repush['responseMessage'], $repush['responseMessage']);
        } else {
            return $this->sendError($repush['responseMessage'], $repush['responseMessage']);
        }

        // $order = Order::where('ref', $request->settlement_id)->where('status', 0)->first();

        // if (!is_null($order)) {

        //     if ($repush->requestSuccessful) {
        //         // $this->isCredited($order->subtotal, $order->user_id);
        //         // $order->status = 1;
        //         // $order->save();

        //         return $this->sendResponse($repush, 'Sent successfully');
        //     }
        // } else {
        //     return $this->sendError("Invalid settlement Id", "Invalid settlement Id");
        // }
    }
    
    public function store(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'category_id' => 'required',
            'subcategory_id' => 'required',
            'user_id' => 'required',
            'price' => 'required',
            'quantity' => 'required',
            'total' => 'required',
            'subtotal' => 'required',
            'ref' => 'required',
            'description' => 'required',
            'status' => 'required',

        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }


        try {
            DB::beginTransaction();
            $product = Order::create($input);

            DB::commit();
            return $this->sendResponse(new OrderResource(Order::find($product->id)), 'Order created successfully.');
        } catch (\Exception $ex) {
            //throw $th;

            return $this->sendError($ex->getMessage());
        }
    }
    
    public function show($id)
    {
        $product = Order::find($id);

        if (is_null($product)) {
            return $this->sendError('Order not found.');
        }

        return $this->sendResponse(new OrderResource($product), 'Order retrieved successfully.');
    }

    public function requeryUserTransaction($id, Request $request)
    {
        $id = $request->id;

        $order = Order::findOrFail($id);
        $request_id = $order->ref;
        $subcategory = SubCategory::findOrFail($order->subcategory_id);

        \Log::info("REQUERY USER");
        \Log::info($request_id);
        \Log::info($subcategory->category_id);


        if ($subcategory->category_id == 4 && $order->status == 0) {
            if (!is_null($order->api_route) && $order->api_route == "VTPASS") {
                // VTPASS Requery function
                $trans_record = $this->VtPassRequery($request_id);

                \Log::info("VTPASS REQUERY 1");
                \Log::info($trans_record);

                if ($trans_record['code'] == '000' && $trans_record['content']['transactions']['status'] == "delivered") {
                    $token = $trans_record['purchased_code'];
                    $token = explode(":", $token);
                    $a = trim($token[0]);
                    $b = trim($token[1]);
                    $token = $a . ":" . $b;
                    $order->status = 1;
                    $order->description = $order->description . ' ' . $token;
                    $order->save();
                    return $this->sendResponse("Transaction successful", " Transaction successful");
                } elseif ($trans_record['code'] == "016" || $trans_record['code'] == "040" || $trans_record['code'] == "013" || $trans_record['code'] == "034") {

                    $order->status = 4;
                    $order->save();

                    //refund
                    $this->isCredited($order->subtotal, $order->user_id);

                    $new_order = new Order();
                    $new_order->ref = $order->ref;
                    $new_order->custom_reference = $order->custom_reference;
                    $new_order->user_id = $order->user_id;
                    $new_order->subcategory_id = $subcategory->id;
                    $new_order->plan = $subcategory->title . " " . $order->plan . " at N" . $order->amount;
                    $new_order->amount = $order->subtotal;
                    $new_order->quantity = 1;
                    $new_order->channel = is_null($order->channel) ? 'Web' : 'App';

                    $new_order->subtotal = $order->subtotal;
                    $new_order->total = $order->subtotal;
                    $new_order->phone = $order->phone;
                    $new_order->bal = $order->prev;
                    $new_order->prev_bal = $order->bal;
                    $new_order->description = $subcategory->title . " " . $order->plan .
                        " at N" . $order->subtotal;
                    $new_order->status = 2;
                    //$order->response = $response['data']['gateway_response'];
                    $new_order->save();
                    return $this->sendError("Transaction failed", " Transaction failed");
                } else {
                    return $this->sendError2($order->ref, $order->custom_reference, "Transaction pending", "Transaction pending");
                }
            }

            if (!is_null($order->api_route) && $order->api_route == "RINGO") {
                // VTPASS Requery function
                $trans_record = $this->ringoRequery($request_id);

                if ($trans_record['status'] == '200') {
                    $order->status = 1;
                    $order->description . ' Token :' . $trans_record['token'];
                    $order->save();

                    return $this->sendResponse("Transaction successful", " Transaction successful");
                } elseif ($trans_record['code'] == "300") {

                    $order->status = 4;
                    $order->save();

                    //refund
                    $this->isCredited($order->subtotal, $order->user_id);

                    $new_order = new Order();
                    $new_order->ref = $order->ref;
                    $new_order->custom_reference = $order->custom_reference;
                    $new_order->user_id = $order->user_id;
                    $new_order->subcategory_id = $subcategory->id;
                    $new_order->plan = $subcategory->title . " " . $order->plan . " at N" . $order->amount;
                    $new_order->amount = $order->subtotal;
                    $new_order->quantity = 1;
                    $new_order->channel = is_null($order->channel) ? 'Web' : 'App';

                    $new_order->subtotal = $order->subtotal;
                    $new_order->total = $order->subtotal;
                    $new_order->phone = $order->phone;
                    $new_order->bal = $order->prev;
                    $new_order->prev_bal = $order->bal;
                    $new_order->description = $subcategory->title . " " . $order->plan .
                        " at N" . $order->subtotal;
                    $new_order->status = 2;
                    //$order->response = $response['data']['gateway_response'];
                    $new_order->save();
                    return $this->sendError("Transaction failed", " Transaction failed");
                } else {
                    return $this->sendError2($order->ref, $order->custom_reference, "Transaction pending", "Transaction pending");
                }
            }

        } elseif ($subcategory->category_id == 3 && $order->status == 0) {
            if (!is_null($order->api_route) && $order->api_route == "VTPASS") {
                // VTPASS Requery function
                $trans_record = $this->VtPassRequery($request_id);
                \Log::info("VTPASS REQUERY 2");
                \Log::info($trans_record);

                if ($trans_record['code'] == '000' && $trans_record['content']['transactions']['status'] == "delivered") {
                    $order->status = 1;
                    $order->save();
                    return $this->sendResponse("Transaction successful", " Transaction successful");
                } elseif ($trans_record['code'] == "016" || $trans_record['code'] == "040" || $trans_record['code'] == "013" || $trans_record['code'] == "034") {

                    $order->status = 4;
                    $order->save();

                    //refund
                    $this->isCredited($order->subtotal, $order->user_id);

                    $new_order = new Order();
                    $new_order->ref = $order->ref;
                    $new_order->custom_reference = $order->custom_reference;
                    $new_order->user_id = $order->user_id;
                    $new_order->subcategory_id = $subcategory->id;
                    $new_order->plan = $subcategory->title . " " . $order->plan . " at N" . $order->amount;
                    $new_order->amount = $order->subtotal;
                    $new_order->quantity = 1;
                    $new_order->channel = is_null($order->channel) ? 'Web' : 'App';

                    $new_order->subtotal = $order->subtotal;
                    $new_order->total = $order->subtotal;
                    $new_order->phone = $order->phone;
                    $new_order->bal = $order->prev;
                    $new_order->prev_bal = $order->bal;
                    $new_order->description = $subcategory->title . " " . $order->plan .
                        " at N" . $order->subtotal;
                    $new_order->status = 2;
                    //$order->response = $response['data']['gateway_response'];
                    $new_order->save();
                    return $this->sendError("Transaction failed", " Transaction failed");
                } else {
                    return $this->sendError2($order->ref, $order->custom_reference, "Transaction pending", "Transaction pending");
                }
            }

            if (!is_null($order->api_route) && $order->api_route == "RINGO") {
                // VTPASS Requery function
                $trans_record = $this->ringoRequery($request_id);

                if ($trans_record['code'] == '200') {
                    $order->status = 1;
                    $order->save();
                    return $this->sendResponse("Transaction successful", " Transaction successful");
                } elseif ($trans_record['code'] == "300") {

                    $order->status = 4;
                    $order->save();

                    //refund
                    $this->isCredited($order->subtotal, $order->user_id);

                    $new_order = new Order();
                    $new_order->ref = $order->ref;
                    $new_order->custom_reference = $order->custom_reference;
                    $new_order->user_id = $order->user_id;
                    $new_order->subcategory_id = $subcategory->id;
                    $new_order->plan = $subcategory->title . " " . $order->plan . " at N" . $order->amount;
                    $new_order->amount = $order->subtotal;
                    $new_order->quantity = 1;
                    $new_order->channel = is_null($order->channel) ? 'Web' : 'App';

                    $new_order->subtotal = $order->subtotal;
                    $new_order->total = $order->subtotal;
                    $new_order->phone = $order->phone;
                    $new_order->bal = $order->prev;
                    $new_order->prev_bal = $order->bal;
                    $new_order->description = $subcategory->title . " " . $order->plan .
                        " at N" . $order->subtotal;
                    $new_order->status = 2;
                    //$order->response = $response['data']['gateway_response'];
                    $new_order->save();
                    return $this->sendError("Transaction failed", " Transaction failed");
                } else {
                    return $this->sendError2($order->ref, $order->custom_reference, "Transaction pending", "Transaction pending");
                }
            }
        }

    }

    public function requeryAdminTransaction($id, Request $request)
    {
        $id = $request->id;

        $order = Order::findOrFail($id);
        $request_id = $order->ref;
        $subcategory = SubCategory::findOrFail($order->subcategory_id);

        \Log::info("REQUERY USER");
        \Log::info($request_id);
        \Log::info($subcategory->category_id);


        if ($subcategory->category_id == 4 && $order->status == 0) {
            if (!is_null($order->api_route) && $order->api_route == "VTPASS") {
                // VTPASS Requery function
                $trans_record = $this->VtPassRequery($request_id);

                \Log::info("VTPASS REQUERY 1");
                \Log::info($trans_record);

                if ($trans_record['code'] == '000' && $trans_record['content']['transactions']['status'] == "delivered") {
                    
                    $token = $trans_record['purchased_code'] ?? '';
                    $token = explode(":", $token);
                    $a = trim($token[0] ?? '');
                    $b = trim($token[1] ?? '');
                    //$token = $a . ":" . $b;
                    $token = $b ? "{$a}:{$b}" : $a;
                    $order->status = 1;
                    $order->description = $order->description . ' ' . $token;
                    $order->save();
                    return $this->sendResponse("Transaction successful", " Transaction successful");
                } elseif ($trans_record['code'] == "016" || $trans_record['code'] == "040" || $trans_record['code'] == "013" || $trans_record['code'] == "034") {

                    $order->status = 4;
                    $order->save();

                    //refund
                    $this->isCredited($order->subtotal, $order->user_id);

                    $new_order = new Order();
                    $new_order->ref = $order->ref;
                    $new_order->custom_reference = $order->custom_reference;
                    $new_order->user_id = $order->user_id;
                    $new_order->subcategory_id = $subcategory->id;
                    $new_order->plan = $subcategory->title . " " . $order->plan . " at N" . $order->amount;
                    $new_order->amount = $order->subtotal;
                    $new_order->quantity = 1;
                    $new_order->channel = is_null($order->channel) ? 'Web' : 'App';

                    $new_order->subtotal = $order->subtotal;
                    $new_order->total = $order->subtotal;
                    $new_order->phone = $order->phone;
                    $new_order->bal = $order->prev;
                    $new_order->prev_bal = $order->bal;
                    $new_order->description = $subcategory->title . " " . $order->plan .
                        " at N" . $order->subtotal;
                    $new_order->status = 2;
                    //$order->response = $response['data']['gateway_response'];
                    $new_order->save();
                    return $this->sendError("Transaction failed", " Transaction failed");
                } else {
                    return $this->sendError2($order->ref, $order->custom_reference, "Transaction pending", "Transaction pending");
                }
            }

            if (!is_null($order->api_route) && $order->api_route == "RINGO") {
                // VTPASS Requery function
                $trans_record = $this->ringoRequery($request_id);

                if ($trans_record['status'] == '200') {
                    $order->status = 1;
                    $order->description . ' Token :' . $trans_record['token'];
                    $order->save();

                    return $this->sendResponse("Transaction successful", " Transaction successful");
                } elseif ($trans_record['code'] == "300") {

                    $order->status = 4;
                    $order->save();

                    //refund
                    $this->isCredited($order->subtotal, $order->user_id);

                    $new_order = new Order();
                    $new_order->ref = $order->ref;
                    $new_order->custom_reference = $order->custom_reference;
                    $new_order->user_id = $order->user_id;
                    $new_order->subcategory_id = $subcategory->id;
                    $new_order->plan = $subcategory->title . " " . $order->plan . " at N" . $order->amount;
                    $new_order->amount = $order->subtotal;
                    $new_order->quantity = 1;
                    $new_order->channel = is_null($order->channel) ? 'Web' : 'App';

                    $new_order->subtotal = $order->subtotal;
                    $new_order->total = $order->subtotal;
                    $new_order->phone = $order->phone;
                    $new_order->bal = $order->prev;
                    $new_order->prev_bal = $order->bal;
                    $new_order->description = $subcategory->title . " " . $order->plan .
                        " at N" . $order->subtotal;
                    $new_order->status = 2;
                    //$order->response = $response['data']['gateway_response'];
                    $new_order->save();
                    return $this->sendError("Transaction failed", " Transaction failed");
                } else {
                    return $this->sendError2($order->ref, $order->custom_reference, "Transaction pending", "Transaction pending");
                }
            }

        } elseif ($subcategory->category_id == 3 && $order->status == 0) {
            if (!is_null($order->api_route) && $order->api_route == "VTPASS") {
                // VTPASS Requery function
                $trans_record = $this->VtPassRequery($request_id);
                \Log::info("VTPASS REQUERY 2");
                \Log::info($trans_record);

                if ($trans_record['code'] == '000' && $trans_record['content']['transactions']['status'] == "delivered") {
                    $order->status = 1;
                    $order->save();
                    return $this->sendResponse("Transaction successful", " Transaction successful");
                } elseif ($trans_record['code'] == "016" || $trans_record['code'] == "040" || $trans_record['code'] == "013" || $trans_record['code'] == "034") {

                    $order->status = 4;
                    $order->save();

                    //refund
                    $this->isCredited($order->subtotal, $order->user_id);

                    $new_order = new Order();
                    $new_order->ref = $order->ref;
                    $new_order->custom_reference = $order->custom_reference;
                    $new_order->user_id = $order->user_id;
                    $new_order->subcategory_id = $subcategory->id;
                    $new_order->plan = $subcategory->title . " " . $order->plan . " at N" . $order->amount;
                    $new_order->amount = $order->subtotal;
                    $new_order->quantity = 1;
                    $new_order->channel = is_null($order->channel) ? 'Web' : 'App';

                    $new_order->subtotal = $order->subtotal;
                    $new_order->total = $order->subtotal;
                    $new_order->phone = $order->phone;
                    $new_order->bal = $order->prev;
                    $new_order->prev_bal = $order->bal;
                    $new_order->description = $subcategory->title . " " . $order->plan .
                        " at N" . $order->subtotal;
                    $new_order->status = 2;
                    //$order->response = $response['data']['gateway_response'];
                    $new_order->save();
                    return $this->sendError("Transaction failed", " Transaction failed");
                } else {
                    return $this->sendError2($order->ref, $order->custom_reference, "Transaction pending", "Transaction pending");
                }
            }

            if (!is_null($order->api_route) && $order->api_route == "RINGO") {
                // VTPASS Requery function
                $trans_record = $this->ringoRequery($request_id);

                if ($trans_record['code'] == '200') {
                    $order->status = 1;
                    $order->save();
                    return $this->sendResponse("Transaction successful", " Transaction successful");
                } elseif ($trans_record['code'] == "300") {

                    $order->status = 4;
                    $order->save();

                    //refund
                    $this->isCredited($order->subtotal, $order->user_id);

                    $new_order = new Order();
                    $new_order->ref = $order->ref;
                    $new_order->custom_reference = $order->custom_reference;
                    $new_order->user_id = $order->user_id;
                    $new_order->subcategory_id = $subcategory->id;
                    $new_order->plan = $subcategory->title . " " . $order->plan . " at N" . $order->amount;
                    $new_order->amount = $order->subtotal;
                    $new_order->quantity = 1;
                    $new_order->channel = is_null($order->channel) ? 'Web' : 'App';

                    $new_order->subtotal = $order->subtotal;
                    $new_order->total = $order->subtotal;
                    $new_order->phone = $order->phone;
                    $new_order->bal = $order->prev;
                    $new_order->prev_bal = $order->bal;
                    $new_order->description = $subcategory->title . " " . $order->plan .
                        " at N" . $order->subtotal;
                    $new_order->status = 2;
                    //$order->response = $response['data']['gateway_response'];
                    $new_order->save();
                    return $this->sendError("Transaction failed", " Transaction failed");
                } else {
                    return $this->sendError2($order->ref, $order->custom_reference, "Transaction pending", "Transaction pending");
                }
            }
        }

    }


    public function updateOrder($id, Request $request)
    {
        $pendingOrders = Order::where('status', 0)->where('id', $id)->first();
        if (is_null($pendingOrders)) {
            return $this->sendError('Order not found.');
        }

        $admin_user = User::find($this->user->id);
        \Log::info($admin_user);
        if ($admin_user->role == 1) {
            $amount = $pendingOrders->subtotal;

            $prev = User::find($pendingOrders->user_id)->wallet;

            $bal = $prev + $pendingOrders->subtotal;



            if ($request->value == "confirm") {

                $amt = $amount;
                if ($amt >= 10000) {
                    $amt = $amt - 50;
                }


                if (
                    $pendingOrders->subcategory_id == 17 || $pendingOrders->subcategory_id == 57 || $pendingOrders->subcategory_id == 18
                    || $pendingOrders->subcategory_id == 19 || $pendingOrders->subcategory_id == 20
                ) {
                    //airtime to cash
                    $this->isCredited($amount, $pendingOrders->user_id);
                    $pendingOrders->status = 1;
                    $pendingOrders->bal = $prev + $amt;
                    $pendingOrders->prev_bal = $prev;
                    //$pendingOrders->description = $pendingOrders->description . " Prev Bal: $prev, Bal: $bal";

                    $pendingOrders->save();

                    return $this->sendResponse(" Confirmed successful", " Confirmed successful");
                } else if ($pendingOrders->subcategory_id == 39) {
                    //withdrawal

                    if ($this->isDebited($pendingOrders->subtotal, $pendingOrders->user_id)) {
                        $pendingOrders->status = 1;
                        $pendingOrders->bal = $prev - $pendingOrders->subtotal;
                        $pendingOrders->prev_bal = $prev;
                        //$pendingOrders->description = $pendingOrders->description . " Prev Bal: $prev, Bal: $bal";

                        $pendingOrders->save();

                        return $this->sendResponse(" Withdrawal Confirmed successful", " Withdrawal Confirmed successful");
                    } else {
                        return $this->sendError("Insufficent funds in wallet", "Insufficent funds in wallet");
                    }
                } else if ($pendingOrders->subcategory_id == 34) {
                    //manual funding
                    $this->isCredited($amt, $pendingOrders->user_id);

                    $pendingOrders->status = 1;
                    $pendingOrders->bal = $prev + $amount;
                    $pendingOrders->prev_bal = $prev;
                    //$pendingOrders->description = $pendingOrders->description . " Prev Bal: $prev, Bal: $bal";

                    $pendingOrders->save();

                    return $this->sendResponse(" Confirmed successful", " Confirmed successful");
                } else {

                    // if ($this->isDebited($pendingOrders->subtotal)) {

                    $pendingOrders->status = 1;
                    //$prev = $this->user->wallet;

                    // $bal =  $this->user->wallet - $amountActual;
                    $pendingOrders->bal = $prev - $pendingOrders->subtotal;
                    $pendingOrders->prev_bal = $prev;
                    //$pendingOrders->description = $pendingOrders->description . " Prev Bal: $prev, Bal: $bal";

                    $pendingOrders->save();

                    return $this->sendResponse(" Confirmed successful", " Confirmed successful");
                }
                // } else {
                //     return $this->sendError("Insufficient fund in wallet to debit",  "Insufficient fund in wallet to debit");
                // }
                // }
            } else if ($request->value == "cancelled") {
                if ($pendingOrders->subcategory_id == 39) {
                    $pendingOrders->status = 3;
                    //$pendingOrders->description = $pendingOrders->description . " Prev Bal: $prev, Bal: $bal";
                    $pendingOrders->bal = $prev;
                    $pendingOrders->prev_bal = $prev;
                    $pendingOrders->save();
                    return $this->sendResponse(" cancelled successful", " cancelled successful");
                } else {
                    $pendingOrders->status = 3;
                    //$pendingOrders->description = $pendingOrders->description . " Prev Bal: $prev, Bal: $bal";
                    $pendingOrders->bal = $bal;
                    $pendingOrders->prev_bal = $prev;
                    $pendingOrders->save();
                    return $this->sendResponse(" cancelled successful", " cancelled successful");
                }
            } else if ($request->value == "reverse") {
                $subcategory = Subcategory::find($pendingOrders->subcategory_id);
                if ($subcategory->category_id == 1 || $subcategory->category_id == 2 || $subcategory->category_id == 3 || $subcategory->category_id == 4) {


                    $this->refundUser(
                        $pendingOrders->ref,
                        null,
                        $amount,
                        [
                            'phone' => $pendingOrders->phone,
                            'bal' => $bal,
                            'prev_bal' => $prev
                        ]
                    );

                    return $this->sendResponse("Reversed successful", " Reversed successful");

                    // if ($this->isCredited($amount, $pendingOrders->user_id)) {
                    //     $pendingOrders->status = 2;
                    //     $pendingOrders->bal = $bal;
                    //     $pendingOrders->prev_bal = $prev;
                    //     //$pendingOrders->description = $pendingOrders->description . " Prev Bal: $prev, Bal: $bal";

                    //     $pendingOrders->save();
                    //     return $this->sendResponse("Reversed successful", " Reversed successful");
                    // }
                }

            }

        } else {
            return $this->sendError("Processing failed!");
        }



    }

    public function updateConfirmedOrder($id, Request $request)
    {
        $pendingOrders = Order::where(['status' => 1, 'id' => $id])->first();
        if (is_null($pendingOrders)) {
            return $this->sendError('Order not found.');
        }

        $admin_user = User::find($this->user->id);
        \Log::info($admin_user);
        if ($admin_user->role == 1) {
            $amount = $pendingOrders->subtotal;

            $prev = User::find($pendingOrders->user_id)->wallet;

            $bal = $prev + $pendingOrders->subtotal;



            if ($request->value == "confirm") {

                $amt = $amount;
                if ($amt >= 10000) {
                    $amt = $amt - 50;
                }


                if (
                    $pendingOrders->subcategory_id == 17 || $pendingOrders->subcategory_id == 18
                    || $pendingOrders->subcategory_id == 19 || $pendingOrders->subcategory_id == 20
                ) {
                    //airtime to cash
                    $this->isCredited($amount, $pendingOrders->user_id);
                    $pendingOrders->status = 1;
                    $pendingOrders->bal = $prev + $amt;
                    $pendingOrders->prev_bal = $prev;
                    //$pendingOrders->description = $pendingOrders->description . " Prev Bal: $prev, Bal: $bal";

                    $pendingOrders->save();

                    return $this->sendResponse(" Confirmed successful", " Confirmed successful");
                } else if ($pendingOrders->subcategory_id == 39) {
                    //withdrawal

                    if ($this->isDebited($pendingOrders->subtotal, $pendingOrders->user_id)) {
                        $pendingOrders->status = 1;
                        $pendingOrders->bal = $prev - $pendingOrders->subtotal;
                        $pendingOrders->prev_bal = $prev;
                        //$pendingOrders->description = $pendingOrders->description . " Prev Bal: $prev, Bal: $bal";

                        $pendingOrders->save();

                        return $this->sendResponse(" Withdrawal Confirmed successful", " Withdrawal Confirmed successful");
                    } else {
                        return $this->sendError("Insufficent funds in wallet", "Insufficent funds in wallet");
                    }
                } else if ($pendingOrders->subcategory_id == 34) {
                    //manual funding
                    $this->isCredited($amt, $pendingOrders->user_id);

                    $pendingOrders->status = 1;
                    $pendingOrders->bal = $prev + $amt;
                    $pendingOrders->prev_bal = $prev;
                    //$pendingOrders->description = $pendingOrders->description . " Prev Bal: $prev, Bal: $bal";

                    $pendingOrders->save();

                    return $this->sendResponse(" Confirmed successful", " Confirmed successful");
                } else {

                    // if ($this->isDebited($pendingOrders->subtotal)) {

                    $pendingOrders->status = 1;
                    //$prev = $this->user->wallet;

                    // $bal =  $this->user->wallet - $amountActual;
                    $pendingOrders->bal = $prev - $pendingOrders->subtotal;
                    $pendingOrders->prev_bal = $prev;
                    //$pendingOrders->description = $pendingOrders->description . " Prev Bal: $prev, Bal: $bal";

                    $pendingOrders->save();

                    return $this->sendResponse(" Confirmed successful", " Confirmed successful");
                }
                // } else {
                //     return $this->sendError("Insufficient fund in wallet to debit",  "Insufficient fund in wallet to debit");
                // }
                // }
            } else if ($request->value == "cancelled") {
                if ($pendingOrders->subcategory_id == 39) {
                    $pendingOrders->status = 3;
                    //$pendingOrders->description = $pendingOrders->description . " Prev Bal: $prev, Bal: $bal";
                    $pendingOrders->bal = $prev;
                    $pendingOrders->prev_bal = $prev;
                    $pendingOrders->save();
                    return $this->sendResponse(" cancelled successful", " cancelled successful");
                } else {
                    $pendingOrders->status = 3;
                    //$pendingOrders->description = $pendingOrders->description . " Prev Bal: $prev, Bal: $bal";
                    $pendingOrders->bal = $bal;
                    $pendingOrders->prev_bal = $prev;
                    $pendingOrders->save();
                    return $this->sendResponse(" cancelled successful", " cancelled successful");
                }
            } else if ($request->value == "reverse") {
                if ($this->reverseConfirmedOrder($pendingOrders, $amount, $bal, $prev)) {
                    return $this->sendResponse("Reversed successful", " Reversed successful");

                }

            }

        } else {
            return $this->sendError("Processing failed!");
        }



    }

    public function updateOrderExternal()
    {
        $pendingOrders = Order::where(['response' => 'Dear Customer, you do not have sufficient airtime to buy this bundle. Please recharge and try again or Dial *303# to BORROW DATA and Pay Back Later.', 'status' => 1])->first();

        \Log::info("External Reversal Done");
        \Log::info(print_r($pendingOrders, true));

        if (is_null($pendingOrders)) {
            return $this->sendError('Order not found.');
        }
        $amount = $pendingOrders->subtotal;

        $prev = User::find($pendingOrders->user_id)->wallet;

        $bal = $prev + $pendingOrders->subtotal;

        //    Reverse here
        if ($this->isCredited($amount, $pendingOrders->user_id)) {
            $pendingOrders->status = 2;
            $pendingOrders->bal = $bal;
            $pendingOrders->prev_bal = $prev;
            //$pendingOrders->description = $pendingOrders->description . " Prev Bal: $prev, Bal: $bal";

            $pendingOrders->save();
            return $this->sendResponse("Reversed successful", " Reversed successful");
        }

    }
    
    public function update($id, Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'category_id' => 'required',
            'subcategory_id' => 'required',
            'user_id' => 'required',
            'price' => 'required',
            'quantity' => 'required',
            'total' => 'required',
            'subtotal' => 'required',
            'ref' => 'required',
            'description' => 'required',
            'status' => 'required',

        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }



        $product = Order::find($id);
        DB::beginTransaction();
        //code...
        $product = Order::find($id);

        $product->update($request->all());

        DB::commit();


        return $this->sendResponse(new OrderResource($product), 'Order updated successfully.');
    }
    
    public function updateStatus(Request $request) {
        $request->validate([
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:orders,id',
            'status' => 'required|string'
        ]);

        $transaction_ids = $request->transaction_ids;

        \Log::info($transaction_ids);
        
        $admin_user = User::find($this->user->id);
        if ($admin_user->role == 1) {

        if($request->status == "confirm"){

            foreach($transaction_ids as $id){
                $order = Order::find($id);
                $order->status = 1;
                $order->save();
            }


            return $this->sendResponse(count($transaction_ids). " Transactions confirmed successfully", count($transaction_ids). " Transactions confirmed successfully");

        }elseif($request->status == "reverse"){ 

            foreach ($transaction_ids as $id) {
                $updOrder = Order::find($id);

                $subcategory = Subcategory::find($updOrder->subcategory_id);
                if ($subcategory->category_id == 1 || $subcategory->category_id == 2 || $subcategory->category_id == 3 || $subcategory->category_id == 4 || $subcategory->category_id == 6) {
    
                    $response_msg = null;
                    
                    $this->refundUser(
                        $updOrder->ref,
                        $response_msg,
                        $updOrder->subtotal,
                        [
                            'bal' => $updOrder->prev_bal,
                            'prev_bal' => $updOrder->bal
                        ]
                    );
                }
            }

            return $this->sendResponse(count($transaction_ids). " Transactions failed and reversed successfully", count($transaction_ids). " Transactions failed and reversed successfully");
        }
        
        }else{
            return $this->sendError("Processing failed!");
        }
    }


    public function postPayment(Request $request)
    {
        $input = $request->all();
        if ($request->input('payment_method_id') != null) {

            //confirm if payment has been made initially?



            $data = array(
                'payment_method_id' => $request->input('payment_method_id'),
            );
            Order::where('ref', $request->input('order_ref'))->update($data);
            $payment = Payment::where('order_ref', $input['order_ref'])->first();

            if (is_null($payment)) {
                $payment = new Payment();
                $payment->user_id = $input["user_id"];
                $payment->order_ref = $input["order_ref"];
                $payment->payment_method_id = $input["payment_method_id"];
                $payment->amount = $input["amount"];
                $payment->response = $input["response"];
                $payment->save();
            } else {
                $payment->user_id = $input["user_id"];
                $payment->order_ref = $input["order_ref"];
                $payment->payment_method_id = $input["payment_method_id"];
                $payment->amount = $input["amount"];
                $payment->response = $input["response"];
                $payment->save();
            }


            return $this->sendResponse(new PaymentResource($payment), 'Order payment method updated successfully.');
        } else {
            return $this->sendError('Payment method not set');
        }
    }

    public function getTransaction(Request $request)
    {
        $ref = $request->ref;
        \Log::info($ref);

        if(Order::where('ref', $ref)->orWhere('custom_reference', $ref)->exists()){
            $order = Order::where("ref", $ref)->orWhere('custom_reference', $ref)->first();
        }

        if(BucketOrder::where('ref', $ref)->orWhere('custom_reference', $ref)->exists()){
            $bucket_order = BucketOrder::where("ref", $ref)->orWhere('custom_reference', $ref)->first();
        }


        if (isset($order) && !is_null($order)) {
            $order_arr = array(
                "ref" => $order->ref,
                "custom_reference" => $order->custom_reference,
                "status" => $order->status,
                "description" => $order->description,
                "response" => $order->response,
                "amount" => $order->subtotal,
                "created_at" => $order->created_at
            );
            \Log::info("Transaction Success Response");
            \Log::info($this->sendResponse($order_arr, "Transaction Retrieved successfully."));
            return $this->sendResponse($order_arr, "Transaction Retrieved successfully.");
        } elseif (isset($bucket_order) && !is_null($bucket_order)) {
            $bucket_order_arr = array(
                "ref" => $bucket_order->ref,
                "custom_reference" => $bucket_order->custom_reference,
                "status" => $bucket_order->status,
                "description" => $bucket_order->description,
                "response" => $bucket_order->response,
                "amount" => $bucket_order->amount,
                "created_at" => $bucket_order->created_at
            );
            \Log::info("Transaction Bucket Success Response");
            \Log::info($this->sendResponse($bucket_order_arr, "Transaction Retrieved successfully."));
            return $this->sendResponse($bucket_order_arr, "Transaction Retrieved successfully.");
        } else {
            \Log::info("Transaction Error Response");
            \Log::info($this->sendError("Transaction does not exist", "Transaction does not exist."));

            return $this->sendError("Transaction does not exist", "Transaction does not exist.");
        }

  
    }

    public function exportTransactions(Request $request)
    { 
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        \Log::info("START DATE"); 
        \Log::info($start_date);
        return Excel::download(new ExportOrder($start_date,$end_date), 'transactions.xlsx');
    }
    
    public function destroy($id)
    {
        $admin_user = User::find($this->user->id);
        \Log::info($admin_user);
        if($admin_user->role == 1){
            $order = Order::find($id);
            $order->delete();
    
            return $this->sendResponse([], 'Order deleted successfully.');
        }else {
            return $this->sendError("Processing failed!");
        }
    }
    
}
