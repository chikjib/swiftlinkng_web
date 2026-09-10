<?php

namespace App\Http\Controllers\API;
use App\Models\Bucket;
use App\Models\BucketOrder;
use App\Models\User;

use App\Models\Order;
use App\Models\Setting;
use App\Models\Withdrawal;
use App\Models\Commissions;
use App\Models\Subcategory;
use App\Traits\WalletTrait;
use Illuminate\Http\Request;
use App\Providers\UploadClass;
use App\Traits\ReferenceTrait;
use App\Providers\UploadImageClass;
use App\Http\Resources\UserResource;
use App\Services\ReferralDashboardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Traits\IntegrationsTrait;


function array_recursive_search_key_map($needle,$haystack){
    foreach($haystack as $first_level_key=>$value){
        if($needle === $value){
            return array($first_level_key);
        }elseif(is_array($value)){
            $callback = array_recursive_search_key_map($needle,$value);
            if($callback){
                return array_merge(array($first_level_key),$callback);
            }
        }
    }
}

class UserController extends BaseController
{
    use WalletTrait;
    use ReferenceTrait;
    use IntegrationsTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


    public function loadHomePage()
    {
        // $reb = $this->user->generate_reserved_account2();
        // \Log::info("Rehoboth Reserved Account");
        // \Log::info($reb['error']);

        $success['transactions'] =  Order::where('user_id', $this->user->id)->count();

        $success['balance'] =  $this->user->wallet;
        $success['userlevel'] =  $this->user->userlevel;
        $success['webhook_url'] = $this->user->webhook_url;

        $success['bvn'] =  $this->user->bvn;
        $success['nin'] =  $this->user->nin;

        $user =  User::find($this->user->id);
        $today = date("Y-m-d");
        $db_date = date("Y-m-d", strtotime($user->bvn_identify_date));

        if($db_date != $today && $user->bvn_attempts_count == 2){
            $user->bvn_attempts_count = 0;
            $user->save();
        }
        
        $bvn=openssl_decrypt($this->user->bvn, "AES-128-CTR",
                "SWIFtlInk1nG", 0, 'SWiftLiNknG89101');
        
        $nin=openssl_decrypt($this->user->nin, "AES-128-CTR",
                "SWIFtlInk1nG", 0, 'SWiftLiNknG89101');  
                
        \Log::info("DECRYPTED BVN");
        \Log::info($this->user->email);
        \Log::info("BVN");
        \Log::info($bvn);
        \Log::info("NIN");
        \Log::info($nin);
        
        
        if(!is_null($user->reserved_acct)){
            $reserved = json_decode($user->reserved_acct,true);
            // extract($reserved);
            // if()

            $sterling_reserved_array = array_recursive_search_key_map("Sterling bank",$reserved);
            $wema_reserved_array = array_recursive_search_key_map("Wema bank",$reserved);
            $moniepoint_reserved_array = array_recursive_search_key_map("Moniepoint Microfinance Bank",$reserved);
            $gtbank_reserved_array = array_recursive_search_key_map("GT Bank",$reserved);
            $providus_reserved_array = array_recursive_search_key_map("Providus Bank",$reserved);
            $rehoboth_reserved_array = array_recursive_search_key_map("Rehoboth Bank",$reserved);
            $palmpay_reserved_array = array_recursive_search_key_map("Palmpay", $reserved);

            \Log::info($reserved);

            // if(is_array($wema_reserved_array)){
            //     \Log::info($wema_reserved_array[0]);
            //     $key1 = $wema_reserved_array[0];
            //     $sterling = $reserved[$key1];
            //     \Log::info($sterling);

            // }
            if(is_array($sterling_reserved_array)){
                \Log::info($sterling_reserved_array[0]);
                $key1 = $sterling_reserved_array[0];
                $sterling = $reserved[$key1];

            }else{
                $not_array = "not array";
                \Log::info($not_array);
            }

            if(is_array($wema_reserved_array)){
                \Log::info($wema_reserved_array[0]);
                $key2 = $wema_reserved_array[0];
                $wema = $reserved[$key2];
                $user->wema_reserved_acct = json_encode($wema);

            }else{
                $not_array = "not array";
                \Log::info($not_array);
            }

            if(is_array($moniepoint_reserved_array)){
                \Log::info($moniepoint_reserved_array[0]);
                $key3 = $moniepoint_reserved_array[0];
                $moniepoint = $reserved[$key3];
                $user->moniepoint_reserved_acct = json_encode($moniepoint);

            }else{
                $not_array = "not array";
                \Log::info($not_array);
            }

            if(is_array($gtbank_reserved_array)){
                \Log::info($gtbank_reserved_array[0]);
                $key4 = $gtbank_reserved_array[0];
                $gtbank = $reserved[$key4];
                $user->gtbank_reserved_acct = json_encode($gtbank);
            }else{
                $not_array = "not array";
                \Log::info($not_array);
            }

            if(is_array($providus_reserved_array)){
                \Log::info($providus_reserved_array[0]);
                $key5 = $providus_reserved_array[0];
                $providus = $reserved[$key5];
                $user->providus_reserved_acct = json_encode($providus);

            }else{
                $not_array = "not array";
                \Log::info($not_array);
            }

            if(is_array($rehoboth_reserved_array)){
                \Log::info($rehoboth_reserved_array[0]);
                $key6 = $rehoboth_reserved_array[0];
                $rehoboth = $reserved[$key6];
                $user->rehoboth_reserved_acct = json_encode($rehoboth);

            }else{
                $not_array = "not array";
                \Log::info($not_array);
            }
            
            if (is_array($palmpay_reserved_array)) {
                \Log::info($palmpay_reserved_array[0]);
                $key7 = $rehoboth_reserved_array[0];
                $palmpay = $reserved[$key7];
                $user->palmpay_reserved_acct = json_encode($palmpay);

            } else {
                $not_array = "not array";
                \Log::info($not_array);
            }


            $user->reserved_acct = NULL;
            $user->save();

        }
        // $usercollection =  collect(json_decode($user->reserved_acct))->toArray();
       
        // if (count($usercollection) == 1) {
            
        //     $success['bankName'] =  $usercollection[0]->BankName;
        //     $success['accountNumber'] =  $usercollection[0]->AccountNumber;
        //     // $success['bankName'] =  $usercollection[0]->bankName;
        //     // $success['accountNumber'] =  $usercollection[0]->accountNumber;
        // } else if (count($usercollection) == 2) {
        //     $success['bankName'] =  $usercollection[1]->bankName;
        //     $success['accountNumber'] =  $usercollection[1]->accountNumber;

        //     $success['bankName1'] =  $usercollection[0]->bankName;
        //     $success['accountNumber1'] =  $usercollection[0]->accountNumber;
        // } else if (count($usercollection) == 3) {
        //     $success['bankName'] =  $usercollection[2]->bankName;
        //     $success['accountNumber'] =  $usercollection[2]->accountNumber;

        //     $success['bankName1'] =  $usercollection[1]->bankName;
        //     $success['accountNumber1'] =  $usercollection[1]->accountNumber;

        //     $success['bankName2'] =  $usercollection[0]->bankName;
        //     $success['accountNumber2'] =  $usercollection[0]->accountNumber;
        // } else if (count($usercollection) == 4) {
        //     $success['bankName'] =  $usercollection[3]->bankName;
        //     $success['accountNumber'] =  $usercollection[3]->accountNumber;

        //     $success['bankName1'] =  $usercollection[1]->bankName;
        //     $success['accountNumber1'] =  $usercollection[1]->accountNumber;

        //     $success['bankName2'] =  $usercollection[2]->bankName;
        //     $success['accountNumber2'] =  $usercollection[2]->accountNumber;

        //     $success['bankName3'] =  $usercollection[0]->bankName;
        //     $success['accountNumber3'] =  $usercollection[0]->accountNumber;
        // } else if (count($usercollection) == 5) {
        //     $success['bankName'] =  $usercollection[4]->bankName;
        //     $success['accountNumber'] =  $usercollection[4]->accountNumber;

        //     $success['bankName1'] =  $usercollection[3]->bankName;
        //     $success['accountNumber1'] =  $usercollection[3]->accountNumber;

        //     $success['bankName2'] =  $usercollection[2]->bankName;
        //     $success['accountNumber2'] =  $usercollection[2]->accountNumber;

        //     $success['bankName3'] =  $usercollection[1]->bankName;
        //     $success['accountNumber3'] =  $usercollection[1]->accountNumber;
        //     $success['bankName4'] =  $usercollection[0]->bankName;
        //     $success['accountNumber4'] =  $usercollection[0]->accountNumber;
        // } else if(count($usercollection) == 6){
        
        //     $success['bankName'] =  $usercollection[5]->bankName;
        //     $success['accountNumber'] =  $usercollection[5]->accountNumber;

        
        //   $success['bankName1'] =  $usercollection[4]->bankName;
        //     $success['accountNumber1'] =  $usercollection[4]->accountNumber;

        //     $success['bankName2'] =  $usercollection[3]->bankName;
        //     $success['accountNumber2'] =  $usercollection[3]->accountNumber;

        //     $success['bankName3'] =  $usercollection[2]->bankName;
        //     $success['accountNumber3'] =  $usercollection[2]->accountNumber;

        //     $success['bankName4'] =  $usercollection[1]->bankName;
        //     $success['accountNumber4'] =  $usercollection[1]->accountNumber;
        //     $success['bankName5'] =  $usercollection[0]->bankName;
        //     $success['accountNumber5'] =  $usercollection[0]->accountNumber; 
        
        // } else {
        //     $success['bankName'] =  '';
        //     $success['accountNumber'] =  '';
        // }
        
        $success['wema_reserved_acct'] = json_decode($user->wema_reserved_acct);
        $success['moniepoint_reserved_acct'] = json_decode($user->moniepoint_reserved_acct);
        $success['gtbank_reserved_acct'] = json_decode($user->gtbank_reserved_acct);
        $success['providus_reserved_acct'] = json_decode($user->providus_reserved_acct);
        $success['rehoboth_reserved_acct'] = json_decode($user->rehoboth_reserved_acct);
        $success['palmpay_reserved_acct'] = json_decode($user->palmpay_reserved_acct);
        $success['opay_reserved_acct'] = $user->opay_wallet_number ? (object) [
            'bankCode' => 'OPAY', 'bankName' => 'OPay',
            'accountNumber' => $user->opay_wallet_number,
            'accountName' => $user->opay_wallet_name,
            'refId' => $user->opay_wallet_ref_id,
            'accountType' => $user->opay_wallet_account_type,
            'status' => $user->opay_wallet_status,
        ] : null;

        if(is_null($success['providus_reserved_acct']) && !is_null($user->providus_account)){
            $data  = json_encode(array(
                'bankCode' => "",
                'bankName' => "Providus bank",
                'accountNumber' => $user->providus_account,
                'accountName' => "",
            ));
            $success['providus_reserved_acct'] = json_decode($data);
        }




        $success['card_charge'] = Setting::where('key', 'MONNIFY_CARD_BANK_CHARGE')->first()->value;
        $success['opay_charge'] = (float) config('services.opay_wallet.fee_percent', 0.4);

        // $success['totalCommission'] =  Order::where('user_id', $this->user->id)->where('subcategory_id', 23)->sum('amount') ?? 0;
        $success['totalCommission'] =  $this->user->commission;

        $success['levelPackage'] = Subcategory::where('title', 'Upgrade')->first()->products;
        $success['vaccount_desc'] = Subcategory::where('title', 'ReservedAcct')->first()->description2;
        $success['ref_msg'] = 'Earn N50 and a lifetime commission of 0.2% on every successful data transaction when you refer family and friends. Click Referral Program to know more.';

        $success['notice'] = Setting::where('key', 'NOTICE')->first()->value;
        $success['footer'] = Setting::where('key', 'FOOTER')->first()->value;

        return $this->sendResponse($success, 'User login successfully.');
    }


    public function usersettings()
    {

        //$result['rave_publickey'] = env('MIX_RAVE_PUBLICKEY'); app update
        // $result['rave_enc'] = env('RAVE_ENC');
        // $result['rave_secretkey'] = env('RAVE_CLIENT_SECRET');
        $result['monnify_apikey'] = env('MIX_MONNIFYAPIKEY');
        $result['monnify_contractCode'] = env('MIX_MONNIFYCONTRACTCODE');
        $result['monnify_live'] = env('MIX_monnify_live');
        
        // $result['RAVE_ISTEST_MODE'] = env('RAVE_ISTEST_MODE');
        $result['card_charge'] = Setting::where('key', 'MONNIFY_CARD_BANK_CHARGE')->first()->value;
        // $result['card_charge'] = Setting::where('key', 'RAVE_CARD_BANK_CHARGE')->first()->value;



        $result['bonus'] = $this->user->bonus();
        $result['fundmin'] = $this->user->fundmin();
        $result['fundmax'] = $this->user->fundmax();

        return $this->sendResponse($result, 'Settings retrieved successfully.');
    }



    public function index(Request $request)
    {

        $search = $request->input('search');
        $role = $request->input('role');
        
        $admin_user = User::find($this->user->id);
        if ($admin_user->role == 1) {

        if ($search) {
            $data =  User::where('firstname', 'like', '%' . $search . '%')
                ->orWhere('lastname', 'like', '%' . $search . '%')
                ->orWhere('phone', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%')
                ->paginate(2);
        } else if (!is_null($role)) {
            $data = User::where('role', $role)->get();
        } else {
            $data = User::orderBy('created_at', 'desc')->paginate(20);
        }



        $products = UserResource::collection($data);

        return $products;
        }else{
            return $this->sendError("Processing failed!");
            
        }

        // return $this->sendResponse(UserResource::collection($products), 'Users retrieved successfully.');
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
            'firstname' => 'required',
            'lastname' => 'required',
            'password' => 'required',
            'email' => 'required',
            //     'address' => 'required',
            'phone' => 'required',
            'role' => 'required',
            //   'gender' => 'required',

        ]);


        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        if ($request->file()) {
            $fileupload =  new UploadImageClass();
            $filename = $fileupload($request);
            $input['user_image'] = $filename;
        }
        $input['password'] = bcrypt($input['password']);

        $product = User::create($input);

        return $this->sendResponse(new UserResource($product), 'User created successfully.');
    }

    public function change_password(Request $request)
    {
        $input = $request->all();
        $userid = Auth::guard('api')->user()->id;
        $rules = array(
            'old_password' => 'required',
            'new_password' => 'required|min:6',
            'confirm_password' => 'required|same:new_password',
        );
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $arr = array("status" => 400, "message" => $validator->errors()->first(), "data" => array());
        } else {
            try {
                if ((password_verify(request('old_password'), Auth::user()->password)) == false) {
                    $arr = array("status" => 400, "message" => "Check your old password.", "data" => array());
                } else if ((password_verify(request('new_password'), Auth::user()->password)) == true) {
                    $arr = array("status" => 400, "message" => "Please enter a password which is not similar then current password.", "data" => array());
                } else {
                    $hash = password_hash($input['password'], PASSWORD_DEFAULT);

                    User::where('id', $userid)->update(['password' => $hash]);
                    $arr = array("status" => 200, "message" => "Password updated successfully.", "data" => array());
                }
            } catch (\Exception $ex) {
                if (isset($ex->errorInfo[2])) {
                    $msg = $ex->errorInfo[2];
                } else {
                    $msg = $ex->getMessage();
                }
                $arr = array("status" => 400, "message" => $msg, "data" => array());
            }
        }
        // return \Response::json($arr);

        return $this->sendResponse($arr, 'User updated successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // public function show($id)
    // {
    //     $product = User::find($id);

    //     if (is_null($product)) {
    //         return $this->sendError('User not found.');
    //     }

    //     return $this->sendResponse(new UserResource($product), 'User retrieved successfully.');
    // }
    
    public function show($id)
{
    $authUser = auth()->user();

    if ($authUser->role == 1) {
        $user = User::find($id);
    } else {
        $user = User::where('id', $id)
                    ->where('id', $authUser->id)
                    ->first();
    }

    if (!$user) {
        return $this->sendError('User not found.');
    }

    $userData = (new UserResource($user))->resolve(request());
    $userData = array_merge(
        $userData,
        ReferralDashboardService::forUser($user)
    );

    return $this->sendResponse(
        $userData,
        'User retrieved successfully.'
    );
}

    public function generateApiKey()
    {

        $user = User::find($this->user->id);

        $token =  $user->createToken('pluginng')->accessToken;

        $user->userToken =  $token;
        $user->save();
        return $this->sendResponse(new UserResource($user), 'User api generated successfully.');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */


    public function uploadImage($id, Request $request)
    {
        // if (!is_null($input['user_image'])) {
        if ($request->file()) {
            $product = User::find($id);
            $input = $request->all();
            $fileupload =  new UploadImageClass();
            $filename = $fileupload->upload($request);
            //}
            $product->user_image = $filename;

            $product->save();
            return $this->sendResponse(new UserResource($product), 'User updated successfully.');
        }
    }


    public function updateWallet(Request $request)
    {

        $subcategory = Subcategory::where('title', 'Manual')->first();

        if (is_null($request->user_id)) {
            return $this->sendError('User not found.', 'User not found.');
        }

        $admin_user = User::find($this->user->id);
        \Log::info($admin_user);



        if ($admin_user->role == 1) {

            if ($request->type == "credit") {
                $prev = User::find($request->user_id)->wallet;

                if ($this->isCredited($request->amount, $request->user_id)) {

                    $bal = $prev + $request->amount;
                    $order = new Order();
                    $order->ref = $this->referenceCode();
                    $order->user_id = $request->user_id;
                    $order->subcategory_id = $subcategory->id;
                    $order->plan = $subcategory->title;
                    $order->amount = $request->amount;
                    $order->quantity = 1;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';

                    $order->subtotal = $request->amount;
                    $order->total = $request->amount;
                    $order->bal = $bal;
                    $order->prev_bal = $prev;
                    $order->description = $subcategory->title . " System Credited ";

                    $order->status = 1;
                    $order->save();
                    return $this->sendResponse("Wallet credited successfully.", 'Wallet credited successfully.');
                } else {
                    return $this->sendError('Invalid amount specified', 'Invalid amount specified');
                }
            }

            if ($request->type == "debit") {
                $prev = User::find($request->user_id)->wallet;
                if ($this->isDebited($request->amount, $request->user_id)) {
                    $bal = $prev - $request->amount;

                    $order = new Order();
                    $order->ref = $this->referenceCode();
                    $order->user_id = $request->user_id;
                    $order->subcategory_id = $subcategory->id;
                    $order->plan = $subcategory->title;
                    $order->amount = $request->amount;
                    $order->quantity = 1;
                    $order->subtotal = $request->amount;
                    $order->total = $request->amount;
                    $order->bal = $bal;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';

                    $order->prev_bal = $prev;
                    $order->description = $subcategory->title . " System Debited";
                    $order->status = 1;
                    $order->save();
                    return $this->sendResponse("Wallet debited successfully.", 'Wallet debited successfully.');
                } else {
                    return $this->sendError('Invalid amount specified', 'Invalid amount specified');
                }
            }

            if ($request->type == "credit_bucket") {
                $bucket = Bucket::findOrFail($request->bucket_id);


                $wallet_name = $this->getBucketTitle($bucket->id);

                $user = User::find($request->user_id);

                $data_bundles = collect(json_decode($bucket->price_per_gb, true));
                $data_size = $request->data_size;
                $matching_bundle = $data_bundles->first(function ($bundle) use ($data_size) {
                    \Log::info($bundle['lower_limit']);
                    return $data_size >= $bundle['lower_limit'] && $data_size <= $bundle['upper_limit'];
                });

                $prev_data_wallet_bal = $user->$wallet_name;
                $data_wallet_bal = $user->$wallet_name + $data_size;


                $amountActual = $data_size * $matching_bundle['price'];

                if ($this->IncrementBucket($wallet_name, $user->id, $data_size)) {
                    $bucket_order = new BucketOrder();
                    $bucket_order->ref = $this->referenceCode();
                    ;
                    // $order->custom_reference = $custom_reference;
                    $bucket_order->user_id = $user->id;
                    $bucket_order->bucket_id = $bucket->id;
                    $bucket_order->plan = $bucket->title . " Bucket Purchase of " . $data_size . "GB " . "System Credited";
                    $bucket_order->amount = $amountActual;
                    $bucket_order->quantity = 1;
                    $bucket_order->subtotal = $amountActual;
                    $bucket_order->total = $amountActual;
                    $bucket_order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $bucket_order->data_size = $data_size * 1000;
                    $bucket_order->bucket_bal = $data_wallet_bal;
                    $bucket_order->prev_bucket_bal = $prev_data_wallet_bal;
                    $bucket_order->description = $bucket->title . " Bucket Purchase of " . $data_size . "GB " . "System Credited";
                    $bucket_order->status = 1;
                    $bucket_order->save();


                    $order = new Order();
                    $order->ref = $this->referenceCode();
                    // $order->custom_reference = $custom_reference;
                    $order->user_id = $user->id;
                    $order->subcategory_id = Subcategory::where('title', $bucket->title)->first()->id;
                    $order->plan = $bucket->title . " Bucket Purchase of " . $data_size . "GB " . "System Credited";
                    $order->amount = $amountActual;
                    $order->quantity = 1;
                    $order->subtotal = $amountActual;
                    $order->total = $amountActual;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $data_size * 1000;
                    $order->bal = $user->wallet;
                    $order->prev_bal = $user->wallet;
                    $order->description = $bucket->title . " Bucket Purchase of " . $data_size . "GB " . "System Credited";
                    $order->status = 1;
                    $order->save();

                    return $this->sendResponse("Bucket Wallet credited successfully.", 'Bucket Wallet credited successfully.');

                }

            }

            if ($request->type == "debit_bucket") {
                $bucket = Bucket::findOrFail($request->bucket_id);


                $wallet_name = $this->getBucketTitle($bucket->id);
                $user = User::find($request->user_id);
                $data_size = $request->data_size;

                $prev_data_wallet_bal = $user->$wallet_name;
                $data_wallet_bal = $user->$wallet_name - $data_size;


                if ($this->DecrementBucket($wallet_name, $user->id, $data_size)) {
                    $bucket_order = new BucketOrder();
                    $bucket_order->ref = $this->referenceCode();
                    ;
                    // $order->custom_reference = $custom_reference;
                    $bucket_order->user_id = $user->id;
                    $bucket_order->bucket_id = $bucket->id;
                    $bucket_order->plan = $bucket->title . " of " . $data_size . "GB " . "System Debited";
                    $bucket_order->amount = $data_size;
                    $bucket_order->quantity = 1;
                    $bucket_order->subtotal = $data_size;
                    $bucket_order->total = $data_size;
                    $bucket_order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $bucket_order->data_size = $data_size * 1000;
                    $bucket_order->bucket_bal = $data_wallet_bal;
                    $bucket_order->prev_bucket_bal = $prev_data_wallet_bal;
                    $bucket_order->description = $bucket->title . " of " . $data_size . "GB " . "System Debited";
                    $bucket_order->status = 1;
                    $bucket_order->save();


                    $order = new Order();
                    $order->ref = $this->referenceCode();
                    // $order->custom_reference = $custom_reference;
                    $order->user_id = $user->id;
                    $order->subcategory_id = Subcategory::where('title', $bucket->title)->first()->id;
                    $order->plan = $bucket->title . " " . $data_size . "GB " . "System Debited";
                    $order->amount = $data_size;
                    $order->quantity = 1;
                    $order->subtotal = $data_size;
                    $order->total = $data_size;
                    $order->channel = is_null($request->channel) ? 'Web' : 'App';
                    $order->data_size = $data_size * 1000;
                    $order->bal = $user->wallet;
                    $order->prev_bal = $user->wallet;
                    $order->description = $bucket->title . " " . $data_size . "GB " . "System Debited";
                    $order->status = 1;
                    $order->save();

                    return $this->sendResponse("Bucket Wallet debited successfully.", 'Bucket Wallet debited successfully.');

                }

            }

        } else {
            return $this->sendError('Processing failed!');
        }

        return $this->sendError('Type not defined', 'Type not defined');
    }

    /**
     * Credit or debit a user's commission wallet from the admin panel.
     */
    public function updateCommissionWallet(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', 'in:credit,debit'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'channel' => ['nullable', 'string', 'max:30'],
        ]);

        if (!$this->user || (int) $this->user->role !== 1) {
            return $this->sendError('Unauthorized.', 'Unauthorized.', 403);
        }

        $subcategory = Subcategory::where('title', 'Bonus')->first()
            ?: Subcategory::where('title', 'Manual')->first();

        if (!$subcategory) {
            return $this->sendError(
                'Bonus wallet adjustment is not configured.',
                'Create a Bonus or Manual subcategory before adjusting commission balances.',
                422
            );
        }

        $amount = round((float) $validated['amount'], 2);
        $adminId = (int) $this->user->id;

        try {
            $result = DB::transaction(function () use ($validated, $amount, $subcategory, $adminId) {
                $user = User::lockForUpdate()->findOrFail($validated['user_id']);
                $previousBalance = round((float) $user->commission, 2);

                if ($validated['type'] === 'debit' && $amount > $previousBalance) {
                    throw new \DomainException(
                        'Debit amount cannot exceed the available commission balance of N' .
                        number_format($previousBalance, 2)
                    );
                }

                $newBalance = $validated['type'] === 'credit'
                    ? round($previousBalance + $amount, 2)
                    : round($previousBalance - $amount, 2);

                $user->commission = $newBalance;
                $user->save();

                $action = $validated['type'] === 'credit' ? 'credited' : 'debited';
                $order = new Order();
                $order->ref = $this->referenceCode();
                $order->user_id = $user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = 'Admin Bonus ' . ucfirst($action);
                $order->amount = $amount;
                $order->quantity = 1;
                $order->subtotal = $amount;
                $order->total = $amount;
                $order->bal = $newBalance;
                $order->prev_bal = $previousBalance;
                $order->channel = $validated['channel'] ?? 'Web';
                $order->description = 'Bonus wallet ' . $action .
                    ' by admin.';
                $order->response = sprintf(
                    'Bonus wallet %s by N%s. Balance: N%s.',
                    $action,
                    number_format($amount, 2),
                    number_format($newBalance, 2)
                );
                $order->status = 1;
                Order::withoutEvents(function () use ($order) {
                    $order->save();
                });

                return [
                    'user_id' => $user->id,
                    'commission' => $newBalance,
                    'previous_balance' => $previousBalance,
                    'reference' => $order->ref,
                ];
            }, 3);
        } catch (\DomainException $exception) {
            return $this->sendError($exception->getMessage(), $exception->getMessage(), 422);
        }

        \Log::info('admin.commission_wallet.adjusted', [
            'admin_id' => $adminId,
            'user_id' => $result['user_id'],
            'type' => $validated['type'],
            'amount' => $amount,
            'previous_balance' => $result['previous_balance'],
            'new_balance' => $result['commission'],
            'reference' => $result['reference'],
        ]);

        $message = $validated['type'] === 'credit'
            ? 'Bonus wallet credited successfully.'
            : 'Bonus wallet debited successfully.';

        return $this->sendResponse($result, $message);
    }



    public function transferBonus(Request $request)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'channel' => ['nullable', 'string', 'max:30'],
        ]);

        if (!$this->user || is_null($this->user->id)) {
            return $this->sendError('User not found.', 'User not found.');
        }

        $subcategory = Subcategory::where('title', 'Bonus')->first();
        if (!$subcategory) {
            return $this->sendError(
                'Bonus transfer is not configured.',
                'Bonus transfer is not configured.',
                422
            );
        }

        $amount = round((float) $validated['amount'], 2);

        try {
            DB::transaction(function () use ($amount, $validated, $subcategory) {
                $user = User::lockForUpdate()->findOrFail($this->user->id);

                if ((float) $user->commission <= 0) {
                    throw new \DomainException('No bonus to transfer.');
                }

                if ((float) $user->commission < $amount) {
                    throw new \DomainException(
                        'You cannot transfer above N' . number_format($user->commission, 2)
                    );
                }

                $previousWallet = (float) $user->wallet;
                $user->wallet = round($previousWallet + $amount, 2);
                $user->commission = round((float) $user->commission - $amount, 2);
                $user->save();

            $order = new Order();
            $order->ref = $this->referenceCode();
                $order->user_id = $user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = $subcategory->title;
                $order->amount = $amount;
                $order->quantity = 1;
                $order->subtotal = $amount;
                $order->total = $amount;
                $order->channel = $validated['channel'] ?? 'Web';
                $order->bal = $user->wallet;
                $order->prev_bal = $previousWallet;
            $order->description = $subcategory->title . " Ref Bonus Transfer";
            $order->status = 1;
            $order->save();
            }, 3);
        } catch (\DomainException $exception) {
            return $this->sendError(
                $exception->getMessage(),
                $exception->getMessage(),
                422
            );
        }

        return $this->sendResponse(
            'Wallet credited successfully.',
            'Wallet credited successfully.'
        );
    }


    public function updateLevel(Request $request)
    {

        $subcategory =  Subcategory::where('title', 'Upgrade')->first();

$user = auth()->user();

        if (is_null($user->id)) {
            return $this->sendError('User not found.', 'User not found.');
        }
        
        

        $user =  User::find($user->id);
        //$admin_user = User::find($this->user->id);
        //\Log::info($admin_user);
        //if($admin_user->role == 1){
            
        if ($user->userlevel ==  $request->userlevel) {
            return $this->sendError('User is already on this level.', 'User already on specified level and cannot be downgraded');
        }
        $prev = User::find($user->id)->wallet;


        $productCollection = collect(json_decode($subcategory->products));

        if ($request->has('levelName')) {
            $amount = $productCollection->where('title', $request->levelName)->first()->amount;
        } else {
            $amount = $productCollection->values()->get($request->userlevel)->amount;
        }


        if ($this->isDebited($amount, $user->id)) {
            $bal =  $prev - $amount;

            $order = new Order();
            $order->ref = $this->referenceCode();
            $order->user_id =  $user->id;
            $order->subcategory_id =  $subcategory->id;
            $order->plan =  $subcategory->title;
            $order->amount = $amount;
            $order->quantity =  1;
            $order->subtotal =  $amount;
            $order->total = $amount;
            $order->channel = is_null($request->channel) ? 'Web' : 'App';

            $order->bal = $bal;
            $order->prev_bal = $prev;
            $order->description = $subcategory->title . " System Debited";
            $order->status = 1;
            $order->save();

            $user->userlevel = $request->userlevel;
            $user->save();
            return $this->sendResponse("Level upgraded successfully.", 'Level upgraded successfully.');
        } else {
            return $this->sendError('insufficient funds in wallet', 'insufficient funds in wallet');
        }
        //}else {
        //    return $this->sendError("Processing failed!");
        //}
    }
    
    public function updateLevelAdmin(Request $request)
    {

        $subcategory =  Subcategory::where('title', 'Upgrade')->first();

        if (is_null($request->user_id)) {
            return $this->sendError('User not found.', 'User not found.');
        }

        $user =  User::find($request->user_id);
        
            
        if ($user->userlevel ==  $request->userlevel) {
            return $this->sendError('User is already on this level.', 'User already on specified level and cannot be downgraded');
        }
        $prev = User::find($request->user_id)->wallet;


        $productCollection = collect(json_decode($subcategory->products));

        if ($request->has('levelName')) {
            $amount = $productCollection->where('title', $request->levelName)->first()->amount;
        } else {
            $amount = $productCollection->values()->get($request->userlevel)->amount;
        }


        if ($this->isDebited($amount, $request->user_id)) {
            $bal =  $prev - $amount;

            $order = new Order();
            $order->ref = $this->referenceCode();
            $order->user_id =  $request->user_id;
            $order->subcategory_id =  $subcategory->id;
            $order->plan =  $subcategory->title;
            $order->amount = $amount;
            $order->quantity =  1;
            $order->subtotal =  $amount;
            $order->total = $amount;
            $order->channel = is_null($request->channel) ? 'Web' : 'App';

            $order->bal = $bal;
            $order->prev_bal = $prev;
            $order->description = $subcategory->title . " System Debited";
            $order->status = 1;
            $order->save();

            $user->userlevel = $request->userlevel;
            $user->save();
            return $this->sendResponse("Level upgraded successfully.", 'Level upgraded successfully.');
        } else {
            return $this->sendError('insufficient funds in wallet', 'insufficient funds in wallet');
        }
        
    }

    public function update(Request $request, $id)
{
    if ($id != auth()->id()) {
        abort(403);
    }
 
    $data = $request->validate([
        'firstname'       => 'sometimes|string|max:255',
        'lastname'        => 'sometimes|string|max:255',
        'phone'        => 'sometimes|string|max:14',
        'email'           => 'sometimes|email',
        'bank_name'       => 'nullable|string',
        'account_number'  => 'nullable|string',
    ]);

    $user = auth()->user();

    // Update only fields that were sent in the request
    \Log::info($data);
    $user->fill($data);

    $user->save();

    return $this->sendResponse(
        new UserResource($user),
        'User updated successfully.'
    );
}


    public function getReservedAccount()
    {

        $result = $this->user->getReservedAccount();
\Log::info("getReservedAccount");
\Log::info($result);
        if ($result->requestSuccessful) {
            $user =  User::find($this->user->id);
            $user->reserved_acct = json_encode($result->responseBody->accounts);
            $user->save();
            return $user;
        }
    }

//     public function createNewReservedAccount()
//     {
//         $result = $this->user->generate_reserved_account();
// \Log::info("createNewReservedAccount");
// \Log::info($result);
//         if ($result->requestSuccessful) {
//             $user =  User::find($this->user->id);
//             $user->reserved_acct = json_encode($result->responseBody->accounts);

//             $user->save();
//             return $user;
//         }
//     }

// REHOBOTH
    
    public function createNewReservedAccount()
    {
        $result = $this->user->generate_reserved_account();
\Log::info("createNewReservedAccount");
\Log::info($result);
        if ($result->error == false) {
            $user =  User::find($this->user->id);
            $user->reserved_acct = json_encode($result->Virtual);

            $user->save();
            return $user;
        }
    }

    public function generateProvidus($bvn)
    {
        $result = $this->providusCreateAccount($this->user->firstname . ' ' . $this->user->lastname, $bvn);
        //die(json_encode($result));
        \Log::info("generateProvidus");
        \Log::info($result);
        return $result;
    }

    public function generateSquadCo($bvn)
    {
        $result = $this->squadcoGT($this->user->email, $this->user->firstname, $this->user->lastname, $this->user->phone,$bvn);
        //die(json_encode($result));
        \Log::info($result);
        return $result;
    }
    
    public function generatePalmpay($bvn,$cac = null)
    {
        $customer_name = $this->user->firstname ." ". $this->user->lastname;
        $cac_or_bvn = !is_null($this->user->bvn) ? $bvn : $cac = "BN000000";
        $result = $this->palmPay($this->referenceCode(), $customer_name, $this->user->email, $cac_or_bvn);
        \Log::info($result);
        return $result;
    }
    
   
    // public function generate_reserved_account(Request $request)
    // {

    //     // die(json_encode($this->user));
    //     if ($this->user->hasReservedAccount() && !$request->has('r')) {
    //         return $this->sendError('Account already reserved for user', 'Account already reserved for user');
    //     }
        

    //     $squadco = $this->generateSquadCo();

    //     $providus = $this->generateProvidus();
        
    //     $rehoboth = $this->user->generateRehoboth();
        
    //     $result = $this->user->generate_reserved_account();
    
        

    //     if (!is_null($result)) {
            
            
    //         if ($result->responseCode == "0") {
    //             $user =  User::find($this->user->id);
    //             $user->reserved_acct = json_encode($result->responseBody->accounts);

    //             $user->save();
    //         } else {
    //             $result = $this->user->getReservedAccount();
 
    //             if ($result->requestSuccessful) {
    //                 $user =  User::find($this->user->id);
    //                 $user->reserved_acct = json_encode($result->responseBody->accounts);
    //                 $user->save();
    //             }
    //         }
            
            
    //         if($this->user->providus_account == null && $providus['requestSuccessful'] != false){
    //             $bank = array("bankCode" => "", "bankName" => "Providus Bank", "accountNumber" => $providus['account_number'], "accountName" => $providus['account_name']);
    //             $done_providus = 1;
    //         }
            
    //         if($this->user->providus_account != null){
    //             $bank = array("bankCode" => "", "bankName" => "Providus Bank", "accountNumber" => $this->user->providus_account, "accountName" => "");
    //             $done_providus = 1;
    //         }
            
    //         \Log::info("Squadco success");
    //         \Log::info($squadco['status']);
            
    //         if($squadco['status'] == 200){
    //             $bank2 = array("bankCode" => $squadco['data']['bank_code'], "bankName" => "GT Bank", "accountNumber" => $squadco['data']['virtual_account_number'], "accountName" => $squadco['data']['first_name'] . ' ' . $squadco['data']['last_name']);
    //             $done_squadco = 1;
    //         }
            
            
    //         //Rehoboth
    //         if ($rehoboth->error == false) {
    //             $bank3 = array("bankCode" => "", "bankName" => $rehoboth->Virtual->BankName, "accountNumber" => $rehoboth->Virtual->AccountNumber, "accountName" => $rehoboth->Virtual->AccountName);
    //             $done_rehoboth = 1;
    //         }
            
    //         //$this->user->providus_account != null ? "" : $bank = array("bankCode" => "", "bankName" => "Providus Bank", "accountNumber" => $providus['account_number'], "accountName" => $providus['account_name']);
    //         //$bank2 = array("bankCode" => $squadco['data']['bank_code'], "bankName" => "GT Bank", "accountNumber" => $squadco['data']['virtual_account_number'], "accountName" => $squadco['data']['first_name'] . ' ' . $squadco['data']['last_name']);
           
            
    //         $user2 = User::find($this->user->id);
    //         $acct = $user2->reserved_acct;
    //         //$providus = $user2->providus_account;
            
    //         if(!empty($acct) && isset($done_providus)){
    //             $json = json_decode($acct, true);
    //             array_push($json, $bank);
    //             $user2->reserved_acct = json_encode($json);
                
    //             if($this->user->providus_account == null){
    //                 $user2->providus_account = $providus['account_number'];
    //             }
    //              $user2->save();
    //         }
            
    //         if(!empty($acct) && isset($done_squadco)){
    //             $json = json_decode($acct, true);
    //             array_push($json, $bank2);
    //             $user2->reserved_acct = json_encode($json);
                
    //             $user2->save();
    //         }
            
    //         if(!empty($acct) && isset($done_rehoboth)){
    //             $json = json_decode($acct, true);
    //             array_push($json, $bank3);
    //             $user2->reserved_acct = json_encode($json);
                
    //             $user2->rehoboth_account = $rehoboth->Virtual->AccountNumber;
    //             $user2->save();
    //         }
            
           
            
            
            
    //         return $this->sendResponse(new UserResource($user), 'User account reserved successfully.');
    //     } else {
    //         return $this->sendError('Error occurred creating reserved account. Try Again later', 'Error occurred creating reserved account. Try Again later');
    //     }
    // }
    
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
    
    public function generate_reserved_account(Request $request)
    {
        $bank = $request->bank;
        // \Log::info($bank);
        $user =  User::find($this->user->id);

        if(!is_null($this->user->bvn) && is_null($this->user->nin)){
            $bvn=openssl_decrypt($this->user->bvn, "AES-128-CTR",
                "SWIFtlInk1nG", 0, 'SWiftLiNknG89101');
            \Log::info($bvn);

            if($bank == "gtbank"){
                $squadco = $this->generateSquadCo($bvn);
                if($squadco['status'] == 200){
                    $gtbank_account = array("bankCode" => $squadco['data']['bank_code'], "bankName" => "GT Bank", "accountNumber" => $squadco['data']['virtual_account_number'], "accountName" => $squadco['data']['first_name'] . ' ' . $squadco['data']['last_name']);
                    $user->gtbank_reserved_acct = json_encode($gtbank_account);
                    $user->save();

                    return $this->sendResponse(new UserResource($user), 'User account reserved successfully.');

                }else {
                    return $this->sendError('Error occurred creating reserved account. Try Again later', 'Error occurred creating reserved account. Try Again later');
                }
            }elseif($bank == "providus"){
                $providus = $this->generateProvidus($bvn);

                if($providus['requestSuccessful'] != false){
                    $providus_account = array("bankCode" => "", "bankName" => "Providus Bank", "accountNumber" => $providus['account_number'], "accountName" => $providus['account_name']);
                    $user->providus_reserved_acct = json_encode($providus_account);
                    $user->providus_account = $providus['account_number'];
                    $user->save();
                    return $this->sendResponse(new UserResource($user), 'User account reserved successfully.');
                }else{
                    return $this->sendError('Error occurred creating reserved account. Try Again later', 'Error occurred creating reserved account. Try Again later');
                }
            }elseif($bank == "rehoboth"){
                $rehoboth = $this->user->generateRehoboth($bvn);
                if ($rehoboth->error == false) {
                    $rehoboth_account = array("bankCode" => "", "bankName" => $rehoboth->Virtual->BankName, "accountNumber" => $rehoboth->Virtual->AccountNumber, "accountName" => $rehoboth->Virtual->AccountName);
                    $user->rehoboth_reserved_acct = json_encode($rehoboth_account);
                    $user->rehoboth_account = $rehoboth->Virtual->AccountNumber;
                    $user->save();
                    return $this->sendResponse(new UserResource($user), 'User account reserved successfully.');
                }else{
                    return $this->sendError('Error occurred creating reserved account. Try Again later', 'Error occurred creating reserved account. Try Again later');
                }
            }elseif($bank == "wema"){
                $wema = $this->user->generate_reserved_account($bvn,"",["035"]);
                if($wema->requestSuccessful == false && $wema->responseCode == "99"){
                    $wema = $this->user->getReservedAccount();
                    $wema = json_decode(json_encode($wema->responseBody->accounts),true);

                    $wema_reserved_array = array_recursive_search_key_map("Wema bank",$wema);

                    if(is_array($wema_reserved_array)){
                        \Log::info($wema_reserved_array[0]);
                        $key2 = $wema_reserved_array[0];
                        $wema = $wema[$key2];
                        $user->wema_reserved_acct = json_encode($wema);

                    }
                    // $moniepoint_reserved_array = array_recursive_search_key_map("Moniepoint Microfinance Bank",$reserved);

                }else{
                    $user->wema_reserved_acct = json_decode(json_encode($wema->responseBody->accounts[0]), true);

                }
                $user->save();
                return $this->sendResponse(new UserResource($user), 'User account reserved successfully.');

            }elseif($bank == "moniepoint"){
                $moniepoint = $this->user->generate_reserved_account($bvn,"",["50515"]);

                if($moniepoint->requestSuccessful == false && $moniepoint->responseCode == "99"){
                    $moniepoint = $this->user->getReservedAccount();

                    $moniepoint = json_decode(json_encode($moniepoint->responseBody->accounts),true);

                    $moniepoint_reserved_array = array_recursive_search_key_map("Moniepoint Microfinance Bank",$moniepoint);



                    if(is_array($moniepoint_reserved_array)){
                        $key2 = $moniepoint_reserved_array[0];
                        $moniepoint = $moniepoint[$key2];
                        $user->moniepoint_reserved_acct = json_encode($moniepoint);

                    }

                }else{
                    $user->moniepoint_reserved_acct = json_decode(json_encode($moniepoint->responseBody->accounts[0]), true);
                }

                $user->save();
                return $this->sendResponse(new UserResource($user), 'User account reserved successfully.');
            }elseif($bank == "palmpay"){
                $palmpay = $this->generatePalmpay($bvn);

                if($palmpay['respMsg'] == "success" && $palmpay['respCode'] == "00000000" && $palmpay['status'] == true){
                    $palmpay_account = array("bankCode" => "", "bankName" => "Palmpay", "accountNumber" => $palmpay['data']['virtualAccountNo'], "accountName" => $palmpay['data']['virtualAccountName']);
                    $user->palmpay_reserved_acct = json_encode($palmpay_account);
                    $user->palmpay_account = $palmpay['data']['virtualAccountNo'];
                    $user->save();
                    return $this->sendResponse(new UserResource($user), 'User account reserved successfully.');
                }else{
                    return $this->sendError('Error occurred creating reserved account. Try Again later', 'Error occurred creating reserved account. Try Again later');
                }
            }
        }elseif(!is_null($this->user->nin) && is_null($this->user->bvn)){
            $nin=openssl_decrypt($this->user->nin, "AES-128-CTR",
                "SWIFtlInk1nG", 0, 'SWiftLiNknG89101');

                if($bank == "wema"){
                    $wema = $this->user->generate_reserved_account("",$nin,["035"]);
                    if($wema->requestSuccessful == false && $wema->responseCode == "99"){
                        $wema = $this->user->getReservedAccount();
                        $wema = json_decode(json_encode($wema->responseBody->accounts),true);

                        $wema_reserved_array = array_recursive_search_key_map("Wema bank",$wema);

                        if(is_array($wema_reserved_array)){
                            \Log::info($wema_reserved_array[0]);
                            $key2 = $wema_reserved_array[0];
                            $wema = $wema[$key2];
                            $user->wema_reserved_acct = json_encode($wema);

                        }
                        // $moniepoint_reserved_array = array_recursive_search_key_map("Moniepoint Microfinance Bank",$reserved);

                    }else{
                        $user->wema_reserved_acct = json_decode(json_encode($wema->responseBody->accounts[0]), true);

                    }
                    $user->save();
                    return $this->sendResponse(new UserResource($user), 'User account reserved successfully.');

                }elseif($bank == "moniepoint"){
                    $moniepoint = $this->user->generate_reserved_account("",$nin,["50515"]);
                    if($moniepoint->requestSuccessful == false && $moniepoint->responseCode == "99"){
                        $moniepoint = $this->user->getReservedAccount();

                        $moniepoint = json_decode(json_encode($moniepoint->responseBody->accounts),true);

                        $moniepoint_reserved_array = array_recursive_search_key_map("Moniepoint Microfinance Bank",$moniepoint);



                        if(is_array($moniepoint_reserved_array)){
                            $key2 = $moniepoint_reserved_array[0];
                            $moniepoint = $moniepoint[$key2];
                            $user->moniepoint_reserved_acct = json_encode($moniepoint);

                        }

                    }else{
                        $user->moniepoint_reserved_acct = json_decode(json_encode($moniepoint->responseBody->accounts[0]), true);
                    }
                    $user->save();
                    return $this->sendResponse(new UserResource($user), 'User account reserved successfully.');

                }elseif($bank == "providus"){
                    $providus = $this->generateProvidus($nin);

                    if($providus['requestSuccessful'] != false){
                        $providus_account = array("bankCode" => "", "bankName" => "Providus Bank", "accountNumber" => $providus['account_number'], "accountName" => $providus['account_name']);
                        $user->providus_reserved_acct = json_encode($providus_account);
                        $user->providus_account = $providus['account_number'];
                        $user->save();
                        return $this->sendResponse(new UserResource($user), 'User account reserved successfully.');
                    }else{
                        return $this->sendError('Error occurred creating reserved account. Try Again later', 'Error occurred creating reserved account. Try Again later');
                    }
                }elseif($bank == "rehoboth"){
                    $rehoboth = $this->user->generateRehoboth($nin);
                    if ($rehoboth->error == false) {
                        $rehoboth_account = array("bankCode" => "", "bankName" => $rehoboth->Virtual->BankName, "accountNumber" => $rehoboth->Virtual->AccountNumber, "accountName" => $rehoboth->Virtual->AccountName);
                        $user->rehoboth_reserved_acct = json_encode($rehoboth_account);
                        $user->rehoboth_account = $rehoboth->Virtual->AccountNumber;
                        $user->save();
                        return $this->sendResponse(new UserResource($user), 'User account reserved successfully.');
                    }else{
                        return $this->sendError('Error occurred creating reserved account. Try Again later', 'Error occurred creating reserved account. Try Again later');
                    }
                }
        }elseif(is_null($this->user->nin) && is_null($this->user->bvn)){

            if($bank == "providus"){
                $providus = $this->generateProvidus("");

                if($providus['requestSuccessful'] != false){
                    $providus_account = array("bankCode" => "", "bankName" => "Providus Bank", "accountNumber" => $providus['account_number'], "accountName" => $providus['account_name']);
                    $user->providus_reserved_acct = json_encode($providus_account);
                    $user->providus_account = $providus['account_number'];
                    $user->save();
                    return $this->sendResponse(new UserResource($user), 'User account reserved successfully.');
                }else{
                    return $this->sendError('Error occurred creating reserved account. Try Again later', 'Error occurred creating reserved account. Try Again later');
                }
            }elseif($bank == "rehoboth"){
                $rehoboth = $this->user->generateRehoboth("");
                if ($rehoboth->error == false) {
                    $rehoboth_account = array("bankCode" => "", "bankName" => $rehoboth->Virtual->BankName, "accountNumber" => $rehoboth->Virtual->AccountNumber, "accountName" => $rehoboth->Virtual->AccountName);
                    $user->rehoboth_reserved_acct = json_encode($rehoboth_account);
                    $user->rehoboth_account = $rehoboth->Virtual->AccountNumber;
                    $user->save();
                    return $this->sendResponse(new UserResource($user), 'User account reserved successfully.');
                }else{
                    return $this->sendError('Error occurred creating reserved account. Try Again later', 'Error occurred creating reserved account. Try Again later');
                }
            }elseif ($bank == "wema") {
                $wema = $this->user->generate_reserved_account("", "", ["035"]);

                if ($wema->requestSuccessful == false && $wema->responseCode == "99") {
                    $wema = $this->user->getReservedAccount();
                    $wema = json_decode(json_encode($wema->responseBody->accounts), true);

                    $wema_reserved_array = array_recursive_search_key_map("Wema bank", $wema);

                    if (is_array($wema_reserved_array)) {
                        \Log::info($wema_reserved_array[0]);
                        $key2 = $wema_reserved_array[0];
                        $wema = $wema[$key2];
                        $user->wema_reserved_acct = json_encode($wema);

                    }
                    // $moniepoint_reserved_array = array_recursive_search_key_map("Moniepoint Microfinance Bank",$reserved);

                } else {
                    $user->wema_reserved_acct = json_decode(json_encode($wema->responseBody->accounts[0]), true);

                }
                $user->save();
                return $this->sendResponse(new UserResource($user), 'User account reserved successfully.');

            }elseif ($bank == "moniepoint") {

                $moniepoint = $this->user->generate_reserved_account("", "", ["50515"]);
                // \Log::info($moniepoint);

                if ($moniepoint->requestSuccessful == false && $moniepoint->responseCode == "99") {
                    $moniepoint = $this->user->getReservedAccount();

                    $moniepoint = json_decode(json_encode($moniepoint->responseBody->accounts), true);

                    $moniepoint_reserved_array = array_recursive_search_key_map("Moniepoint Microfinance Bank", $moniepoint);



                    if (is_array($moniepoint_reserved_array)) {
                        $key2 = $moniepoint_reserved_array[0];
                        $moniepoint = $moniepoint[$key2];
                        $user->moniepoint_reserved_acct = json_encode($moniepoint);

                    }

                } else {
                    $user->moniepoint_reserved_acct = json_decode(json_encode($moniepoint->responseBody->accounts[0]), true);
                }
                $user->save();
                return $this->sendResponse(new UserResource($user), 'User account reserved successfully.');

            }
        }elseif(!is_null($this->user->bvn) && !is_null($this->user->nin)){
            $bvn=openssl_decrypt($this->user->bvn, "AES-128-CTR",
                "SWIFtlInk1nG", 0, 'SWiftLiNknG89101');
                \Log::info("GTBANK BVN");
                \Log::info($bvn);
            if($bank == "gtbank"){
                $squadco = $this->generateSquadCo($bvn);
                \Log::info("GTBANK ACCOUNT");
                \Log::info(print_r($squadco,true));
                if($squadco['status'] == 200){
                    $gtbank_account = array("bankCode" => $squadco['data']['bank_code'], "bankName" => "GT Bank", "accountNumber" => $squadco['data']['virtual_account_number'], "accountName" => $squadco['data']['first_name'] . ' ' . $squadco['data']['last_name']);
                    $user->gtbank_reserved_acct = json_encode($gtbank_account);
                    $user->save();

                    return $this->sendResponse(new UserResource($user), 'User account reserved successfully.');

                }else {
                    return $this->sendError('Account not available at the moment, please try other banks','Account not available at the moment, please try other banks');
                }
            }elseif($bank == "providus"){
                $providus = $this->generateProvidus($bvn);

                if($providus['requestSuccessful'] != false){
                    $providus_account = array("bankCode" => "", "bankName" => "Providus Bank", "accountNumber" => $providus['account_number'], "accountName" => $providus['account_name']);
                    $user->providus_reserved_acct = json_encode($providus_account);
                    $user->providus_account = $providus['account_number'];
                    $user->save();
                    return $this->sendResponse(new UserResource($user), 'User account reserved successfully.');
                }else{
                    return $this->sendError('Account not available at the moment, please try other banks','Account not available at the moment, please try other banks');
                }
            }elseif($bank == "rehoboth"){
                $rehoboth = $this->user->generateRehoboth($bvn);
                if ($rehoboth->error == false) {
                    $rehoboth_account = array("bankCode" => "", "bankName" => $rehoboth->Virtual->BankName, "accountNumber" => $rehoboth->Virtual->AccountNumber, "accountName" => $rehoboth->Virtual->AccountName);
                    $user->rehoboth_reserved_acct = json_encode($rehoboth_account);
                    $user->rehoboth_account = $rehoboth->Virtual->AccountNumber;
                    $user->save();
                    return $this->sendResponse(new UserResource($user), 'User account reserved successfully.');
                }else{
                    return $this->sendError('Account not available at the moment, please try other banks','Account not available at the moment, please try other banks');
                }
            }elseif($bank == "wema"){
                $wema = $this->user->generate_reserved_account($bvn,"",["035"]);
                if($wema->requestSuccessful == false && $wema->responseCode == "99"){
                    $wema = $this->user->getReservedAccount();
                    $wema = json_decode(json_encode($wema->responseBody->accounts),true);

                    $wema_reserved_array = array_recursive_search_key_map("Wema bank",$wema);

                    if(is_array($wema_reserved_array)){
                        \Log::info($wema_reserved_array[0]);
                        $key2 = $wema_reserved_array[0];
                        $wema = $wema[$key2];
                        $user->wema_reserved_acct = json_encode($wema);

                    }
                    // $moniepoint_reserved_array = array_recursive_search_key_map("Moniepoint Microfinance Bank",$reserved);

                }else{
                    $user->wema_reserved_acct = json_decode(json_encode($wema->responseBody->accounts[0]), true);

                }
                $user->save();
                return $this->sendResponse(new UserResource($user), 'User account reserved successfully.');

            }elseif($bank == "moniepoint"){
                $moniepoint = $this->user->generate_reserved_account($bvn,"",["50515"]);

                if($moniepoint->requestSuccessful == false && $moniepoint->responseCode == "99"){
                    $moniepoint = $this->user->getReservedAccount();

                    $moniepoint = json_decode(json_encode($moniepoint->responseBody->accounts),true);

                    $moniepoint_reserved_array = array_recursive_search_key_map("Moniepoint Microfinance Bank",$moniepoint);



                    if(is_array($moniepoint_reserved_array)){
                        $key2 = $moniepoint_reserved_array[0];
                        $moniepoint = $moniepoint[$key2];
                        $user->moniepoint_reserved_acct = json_encode($moniepoint);

                    }

                }else{
                    $user->moniepoint_reserved_acct = json_decode(json_encode($moniepoint->responseBody->accounts[0]), true);
                }

                $user->save();
                return $this->sendResponse(new UserResource($user), 'User account reserved successfully.');
            }elseif($bank == "palmpay"){
                $palmpay = $this->generatePalmpay($bvn);

                if($palmpay['respMsg'] == "success" && $palmpay['respCode'] == "00000000" && $palmpay['status'] == true){
                    $palmpay_account = array("bankCode" => "", "bankName" => "Palmpay", "accountNumber" => $palmpay['data']['virtualAccountNo'], "accountName" => $palmpay['data']['virtualAccountName']);
                    $user->palmpay_reserved_acct = json_encode($palmpay_account);
                    $user->palmpay_account = $palmpay['data']['virtualAccountNo'];
                    $user->save();
                    return $this->sendResponse(new UserResource($user), 'User account reserved successfully.');
                }else{
                    return $this->sendError('Error occurred creating reserved account. Try Again later', 'Error occurred creating reserved account. Try Again later');
                }
            }
        }

    }

    public function updateBvn(Request $request)
    {
        \Log::info($request);
        $user = User::find($this->user->id);
        // Restrict to once a day
        $today = date("Y-m-d");
        \Log::info($today);
        \Log::info($user->bvn_identify_date);
        $db_date = date("Y-m-d", strtotime($user->bvn_identify_date));
        \Log::info($db_date);

        $ref = $this->referenceCode();
        $prev = $this->user->wallet;
        $bal = $this->user->wallet;



        if ($db_date == $today) {
            return $this->sendError("Bvn verification limit for today is exceeded", "Bvn verification limit for today is exceeded");
        } else {

            $phone = $this->formatPhoneNumber($request->phone);

            $details = $this->user->getBvn($request->bvn, $request->name, $request->dob, $phone);
            
            $names = explode(" ", $request->name);
            $names_count = count($names);
            if($names_count > 1){
                $first_name = $names[0];
                $last_name = $names[$names_count - 1];
            }else{
                $first_name = $names[0];
                $last_name = $this->user->lastname;
            }
            // $nn = $details->responseBody;
            \Log::info(print_r($details, true));

            $hashed_bvn = openssl_encrypt(
                $request->bvn,
                "AES-128-CTR",
                "SWIFtlInk1nG",
                0,
                'SWiftLiNknG89101'
            );
            
            if(User::where('bvn',$hashed_bvn)->exists()){
                return $this->sendError("Bvn already attached to another account", "Bvn already attached to another account");
            }else{
            

            // Use openssl_decrypt() function to decrypt the data
            // $decryption=openssl_decrypt ($encryption(bvn from db already hashed), "AES-128-CTR",
            //         "SWIFtlInk1nG", 0, 'SWiftLiNknG89101');

            $user->bvn_attempts_count = $user->bvn_attempts_count + 1;

            if($user->bvn_attempts_count == 2){
                $user->bvn_identify_date = $today;
            }

            $user->save();

            $subcategory = Subcategory::find(49);

            $order = new Order();
            $order->ref = $ref;
            $order->user_id = $this->user->id;
            $order->subcategory_id = $subcategory->id;
            $order->plan = $subcategory->title;
            $order->amount = 0;
            $order->quantity = 1;
            $order->subtotal = 0;
            $order->phone = $this->user->phone;
            $order->channel = is_null($request->channel) ? 'Web' : 'App';
            $order->bal = $bal;
            $order->prev_bal = $prev;

            $order->total = 0;
            $order->description = "BVN VERIFICATION";
            $order->status = 0;
            $order->save();



            if ($details->responseCode == 0 && $details->responseMessage == "success") {
                if ($details->responseBody->name->matchStatus == "PARTIAL_MATCH" || $details->responseBody->name->matchStatus == "FULL_MATCH") {

                    $user->firstname = $first_name;
                    $user->lastname = $last_name;
                    $user->bvn = $hashed_bvn;
                    $user->save();


                    $unhashed_bvn = openssl_decrypt(
                        $user->bvn,
                        "AES-128-CTR",
                        "SWIFtlInk1nG",
                        0,
                        'SWiftLiNknG89101'
                    );
                    \Log::info($user->reserved_acct);
                    if (!is_null($user->wema_reserved_acct) || !is_null($user->moniepoint_reserved_acct)) {

                        // UPDATE MONNIFY
                        $this->user->updateMonnify($this->user->email, $unhashed_bvn, "");

                    }

                    if (!is_null($user->gtbank_reserved_acct)) {
                        // UPDATE GTBANK
                        $this->updateSquadcoGT($this->user->email, $this->user->phone, $unhashed_bvn);
                    }

                    $updOrder = Order::where('ref', $ref)->first();
                    $updOrder->status = 1;
                    $updOrder->save();

                    return $this->sendResponse("Verified successfully", "Verified successfully");
                }



            } else {
                return $this->sendError($details->responseMessage, $details->responseMessage);
            }
        }
        }
        return $this->sendError("An error occurred, please try again", "An error occurred, please try again");
    }

    public function updateNin(Request $request)
    {
        \Log::info($request);
        $user = User::find($this->user->id);

        $today = date("Y-m-d");
        $db_date = date("Y-m-d", strtotime($user->nin_identify_date));

        $ref = $this->referenceCode();
        $prev = $this->user->wallet;
        $bal = $this->user->wallet;


        if ($db_date == $today) {
            return $this->sendError("Nin verification limit for today is exceeded", "Nin verification limit for today is exceeded");
        } else {

            $details = $this->user->getNin($request->nin);

            // $nn = $details->responseBody;
            \Log::info(print_r($details, true));



            $hashed_nin = openssl_encrypt(
                $request->nin,
                "AES-128-CTR",
                "SWIFtlInk1nG",
                0,
                'SWiftLiNknG89101'
            );
            
            if(User::where('nin',$hashed_nin)->exists()){
                return $this->sendError("Nin already attached to another account", "Nin already attached to another account");
            }else{
            



            // Use openssl_decrypt() function to decrypt the data 
            // $decryption=openssl_decrypt ($encryption(bvn from db already hashed), "AES-128-CTR",
            //         "SWIFtlInk1nG", 0, 'SWiftLiNknG89101');
            $user->nin_identify_date = $today;
            $user->save();

            $subcategory = Subcategory::find(49); 

            $order = new Order(); 
            $order->ref = $ref;
            $order->user_id = $this->user->id;
            $order->subcategory_id = $subcategory->id;
            $order->plan = $subcategory->title;
            $order->amount = 0;
            $order->quantity = 1;
            $order->subtotal = 0;
            $order->phone = $this->user->phone;
            $order->channel = is_null($request->channel) ? 'Web' : 'App';
            $order->bal = $bal;
            $order->prev_bal = $prev;

            $order->total = 0;
            $order->description = "NIN VERIFICATION";
            $order->status = 0;
            $order->save();

            if ($details->responseCode == 0 && $details->responseMessage == "success") {
                $first_name = $details->responseBody->firstName;
                $last_name = $details->responseBody->lastName;


                $user->firstname = $first_name;
                $user->lastname = $last_name;
                $user->nin = $hashed_nin;
                $user->save();

                $unhashed_nin = openssl_decrypt(
                    $user->nin,
                    "AES-128-CTR",
                    "SWIFtlInk1nG",
                    0,
                    'SWiftLiNknG89101'
                );

                if (!is_null($user->wema_reserved_acct) || !is_null($user->moniepoint_reserved_acct)) {
                    // UPDATE MONNIFY
                    $this->user->updateMonnify($this->user->email, "", $unhashed_nin);

                    // UPDATE PROVIDUS
                }

                $updOrder = Order::where('ref', $ref)->first();
                $updOrder->status = 1;
                $updOrder->save();

                return $this->sendResponse("Verified successfully", "Verified successfully");

            } else {
                return $this->sendError($details->responseMessage, $details->responseMessage);
            }
            }
        }
    }


    // public function update(Request $request, User $product)
    // {
    //     $input = $request->all();

    //     $validator = Validator::make($input, [
    //         'firstname' => 'required',
    //         'lastname' => 'required',
    //         'email' => 'required',
    //         //   'phone' => 'required',

    //     ]);


    //     if ($validator->fails()) {
    //         return $this->sendError('Validation Error.', $validator->errors());
    //     }

    //     $product->firstname = $input['firstname'];
    //     $product->lastname = $input['lastname'];
    //     $product->email = $input['email'];
    //     $product->address = $input['address'];
    //     $product->phone = $input['phone'];
    //     $product->gender = $input['gender'];
    //     $product->wallet = $input['wallet'];
    //     $product->city = $input['city'];
    //     $product->state = $input['state'];
    //     $product->country = $input['country'];

    //     $product->save();

    //     return $this->sendResponse(new UserResource($product), 'User updated successfully.');
    // }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
     
    public function initializeAtmPayment(Request $request)
    {
        $customer_name = $this->user->first_name ." ". $this->user->last_name;
        $customer_email = $this->user->email;
        $paymentReference = $this->referenceCode();
        $amount =  $request->amount;
        $customer_reference = $this->vtPassreferenceCode();

        $payment_gateway = "monnify";
        $response = [];

        if($payment_gateway == "monnify"){
            $response = $this->user->initializeMonnifyPayment($customer_name,$customer_email,$paymentReference,$amount);
        }else{
            $response = $this->user->initializeBudPayPayment($amount,$customer_email,$customer_reference);
        }

        return $this->sendResponse($response, "intialization successful");

    }
    
    public function initializePalmpayAtm(Request $request)
    {
        $customerReference = $this->referenceCode();
        $amount = $request->amount;
        $response = $this->generatePalmpayCardPayment($customerReference,$this->user->email, $this->user->phone, $amount);
        return $this->sendResponse($response, "Initialization successful");
    }
    
    public function initializePalmpayTransfer(Request $request) 
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:100', 'max:15000'],
        ]);
        $customerReference = $this->referenceCode();
        $amount = round((float) $validated['amount'], 2);
        $response = $this->generatePalmpayBankTransfer($customerReference,$this->user->email, $this->user->phone, $amount);

        $responseCode = (string) ($response['respCode'] ?? $response['code'] ?? '');
        $responseMessage = trim((string) ($response['respMsg'] ?? $response['message'] ?? ''));
        $gatewayStatus = $response['status'] ?? null;
        $successful = $gatewayStatus === true
            || $gatewayStatus === 1
            || $gatewayStatus === '1'
            || in_array($responseCode, ['00000000', '0', '200'], true)
            || strtolower($responseMessage) === 'success';

        $accountNumber = $this->firstPalmpayValue($response, [
            'payerVirtualAccNo', 'virtualAccountNo', 'accountNumber',
            'bankAccountNo', 'accountNo'
        ]);
        $checkoutUrl = $this->firstPalmpayValue($response, [
            'checkoutUrl', 'payUrl', 'paymentUrl'
        ]);
        $payerAccountId = $this->firstPalmpayValue($response, ['payerAccountId']);
        $orderNo = $this->firstPalmpayValue($response, ['orderNo']);

        if (!$successful || ($accountNumber === null && $checkoutUrl === null)) {
            $message = $responseMessage !== ''
                ? $responseMessage
                : 'PalmPay did not return a temporary account. Please try again.';
            return $this->sendError($message, [], 502);
        }

        if ($successful) {
            $subcategory = Subcategory::where('title', 'Palmpay')->first();
            if ($subcategory) {
                $order = new Order();
                $order->ref = $customerReference;
                $order->user_id = $this->user->id;
                $order->subcategory_id = $subcategory->id;
                $order->plan = 'PalmPay One-Time Account';
                $order->amount = $amount;
                $order->quantity = 1;
                $order->prev_bal = $this->user->wallet;
                $order->bal = $this->user->wallet;
                $order->subtotal = $amount;
                $order->total = $amount;
                $order->description = 'PalmPay One-Time Account Pending';
                $order->response = json_encode([
                    'message' => 'Waiting for payment confirmation.',
                    'payerAccountId' => $payerAccountId,
                    'orderNo' => $orderNo,
                ], JSON_UNESCAPED_SLASHES);
                $order->channel = 'App';
                $order->status = 0;
                $order->save();
            }
        }

        return $this->sendResponse([
            'accountNumber' => $accountNumber,
            'accountName' => $this->firstPalmpayValue($response, [
                'payerAccountName', 'virtualAccountName', 'accountName'
            ]) ?? trim($this->user->firstname.' '.$this->user->lastname),
            'bankName' => $this->firstPalmpayValue($response, [
                'payerBankName', 'bankName'
            ]) ?? 'PalmPay',
            'checkoutUrl' => $checkoutUrl,
            'payerAccountId' => $payerAccountId,
            'orderNo' => $orderNo,
            'orderId' => $this->firstPalmpayValue($response, ['orderId']) ?? $customerReference,
            'expiresIn' => 2700,
        ], "One-time account generated successfully");
    }

    private function firstPalmpayValue(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload)) {
                $value = trim((string) $payload[$key]);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        foreach ($payload as $value) {
            if (is_array($value)) {
                $match = $this->firstPalmpayValue($value, $keys);
                if ($match !== null) {
                    return $match;
                }
            }
        }

        return null;
    }

    // public function VerifyPayment(Request $request, $transaction_reference){
    //     $response = $this->user->VerifyMonnify($transaction_reference);
    //     return $this->sendResponse($response, "verification retrieved");
    // }
    
    public function VerifyPayment($transaction_reference){
        $gateway = "monnify";
        if ($gateway == "monnify"){
            $response = $this->user->VerifyMonnify($transaction_reference);
        }else{
            $response = $this->user->VerifyBudpay($transaction_reference);
        }

        return $this->sendResponse($response, "verification retrieved");
    }
    
    public function UpdateWebhook(Request $request)

    {
        $user = User::find($this->user->id);
        $user->webhook_url = $request->webhook_url;
        $user->save();
        return $this->sendResponse("Updated successfully", "Updated successfully");
    }
    
    public function bucketSwitcher(Request $request, $user_id)
    {
        $user = User::find($user_id);
        if($user->is_bucket_on == 1) {
            $user->is_bucket_on = 0;
            $user->save();
            return $this->sendResponse("Bucket switched off successfully","Bucket switched off successfully");
        }elseif($user->is_bucket_on == 0){
            $user->is_bucket_on = 1;
            $user->save();
            return $this->sendResponse("Bucket switched on successfully","Bucket switched on successfully");
        }


    }
    
     public function UpdatePurchaseMode(Request $request, $id)
    {
        $user = User::find($id);
        $user->purchase_mode = $request->purchase_mode;
        $user->save();

        return $this->sendResponse("Purchase mode switched to ".$request->purchase_mode ." successfully","Purchase mode switched to ".$request->purchase_mode ." successfully");

    }
    
    public function getTotalWallet(Request $request)
    { 
        $users = User::sum('wallet');
        return $this->sendResponse($users,"Retrieved successfully");
    }
     
    public function destroy($id)
    {
        $admin_user = User::find($this->user->id);
        \Log::info($admin_user);
        if($admin_user->role == 1){
            $payment = User::find($id);
            $payment->delete();
            return $this->sendResponse([], 'User deleted successfully.');
        }else{
            return $this->sendError("Processing failed!");
        }
    }
}
