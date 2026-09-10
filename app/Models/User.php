<?php

namespace App\Models;

use App\AppConstants;
use App\Traits\ReferenceTrait;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

use Illuminate\Support\Facades\Http;

class User extends Authenticatable
//implements MustVerifyEmail
//class User extends Authenticatable

{
    use HasApiTokens, HasFactory, Notifiable, ReferenceTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'phone',
        'email',
        'password',
        // 'role',
        'hear_about_us',
        'referral_id',
        'bank_name',
        'account_number',
        'mtn_sme_wallet',
        'mtn_sme_wallet_status',
        'airtel_eds_wallet',
        'airtel_eds_wallet_status',
        'glo_cg_wallet',
        'glo_cg_wallet_status',
        'nmobile_cg_wallet',
        'nmobile_cg_wallet_status',
        'mtn_smart_wallet',
        'mtn_smart_wallet_status',
        'airtel_awoof_wallet',
        'airtel_awoof_wallet_status',
        'glo_awoof_wallet',
        'glo_awoof_wallet_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'transaction_pin',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    public function order()
    {
        return $this->hasMany(Order::class);
    }

    public function payment()
    {
        return $this->hasMany(Payment::class);
    }

    public function withdrawal()
    {
        return $this->hasMany(Withdrawal::class);
    }


    public function generateRehoboth($bvn)
    {
            $reb = $this->rehobothLoginToken();
            // $unhashed_bvn=openssl_decrypt($this->bvn, "AES-128-CTR",
            //     "SWIFtlInk1nG", 0, 'SWiftLiNknG89101');

            // $unhashed_nin=openssl_decrypt($this->nin, "AES-128-CTR",
            //     "SWIFtlInk1nG", 0, 'SWiftLiNknG89101');
            if($bvn==""){
                $bvn = "01234567891";
            }

            $curl = curl_init();
            $data = array(
                'FirstName' => $this->firstname,
                'LastName' => $this->lastname,
                'PhoneNumber' => $this->phone,
                'Gender' => "Male",
                'Email' => $this->email,
                'Tier' => "1",
                'DateOfBirth' => "19-09-1991",
                'BVN' => $bvn,
                'isUnique' => 1
            );

            \Log::info($data);


            $authSignature = $reb['Authorisation']['auth'];
            $token = $reb['Authorisation']['accesscode'];


            curl_setopt_array($curl, array(
              CURLOPT_URL => config('app.rehobothBaseUrl') .'virtual/create',
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS => json_encode($data),
              CURLOPT_HTTPHEADER => array(
                "X-Auth-Signature: $authSignature",
                "Authorization: Bearer $token",
                'Content-Type: application/json',
              ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            $response = curl_exec($curl);
        \Log::info("generate_reserved_account2");
            \Log::info($response);
        $result = json_decode($response);

        return $result;


    }

      public function generate_reserved_account($bvn,$nin,$pref_bank)
    {

        //$random = Str::random(40);
        //  Use openssl_decrypt() function to decrypt the data
        // $unhashed_bvn=openssl_decrypt($this->bvn, "AES-128-CTR",
        //         "SWIFtlInk1nG", 0, 'SWiftLiNknG89101');

        // $unhashed_nin=openssl_decrypt($this->nin, "AES-128-CTR",
        //         "SWIFtlInk1nG", 0, 'SWiftLiNknG89101');


        $token = $this->monnifyLoginToken();

        $curl = curl_init();
        if(!is_null($this->bvn)){
            $data = array(
            'accountReference' =>  $this->email,
            'accountName' => $this->firstname . ' ' . $this->lastname,
            'currencyCode' => 'NGN',
            'contractCode' => config('app.monnifyContractCode'),
            'customerEmail' => $this->email,
            'customerName' => $this->firstname . ' ' . $this->lastname,
            'bvn' => $bvn,
            //"getAllAvailableBanks" => true,
            // "preferredBanks" => ["035"]
            "getAllAvailableBanks" => false,
            "preferredBanks" =>  $pref_bank

        );
        }elseif(!is_null($this->nin)){
            $data = array(
                'accountReference' =>  $this->email,
                'accountName' => $this->firstname . ' ' . $this->lastname,
                'currencyCode' => 'NGN',
                'contractCode' => config('app.monnifyContractCode'),
                'customerEmail' => $this->email,
                'customerName' => $this->firstname . ' ' . $this->lastname,
                'nin' => $nin,
                //"getAllAvailableBanks" => true,
                // "preferredBanks" => ["035"]
                "getAllAvailableBanks" => false,
                "preferredBanks" => $pref_bank
            );
        }elseif(is_null($this->nin) || is_null($this->bvn)){
            $data = array(
                'accountReference' =>  $this->email,
                'accountName' => $this->firstname . ' ' . $this->lastname,
                'currencyCode' => 'NGN',
                'contractCode' => config('app.monnifyContractCode'),
                'customerEmail' => $this->email,
                'customerName' => $this->firstname . ' ' . $this->lastname,

                //"getAllAvailableBanks" => true,
                // "preferredBanks" => ["035"]
                "getAllAvailableBanks" => false,
                "preferredBanks" =>  $pref_bank
            );
        }


        \Log::info($data);

        curl_setopt_array($curl, array(
            CURLOPT_URL => config('app.monnifyBaseUrl') . '/api/v2/bank-transfer/reserved-accounts',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer $token",
                    'Content-Type: application/json'
                ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response = curl_exec($curl);
        \Log::info("generate_reserved_account");
        \Log::info($response);
        $result = json_decode($response);

        return $result;
    }

    public function getReservedAccount()
    {

        $token = $this->monnifyLoginToken();
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, config('app.monnifyBaseUrl') . "/api/v2/bank-transfer/reserved-accounts/$this->email");
        //	curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:7.0.1) Gecko/20100101 Firefox/7.0.1');
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 0);
        curl_setopt($curl, CURLOPT_ENCODING, "");
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer $token"
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($response);
        // return $result;
        return $result;
    }
    
     public function getBvn($bvn, $name, $dob, $phone)
    {
        $data = array(
            "bvn" => $bvn,
            "name" => $name,
            "dateOfBirth" => $dob,
            "mobileNo" => $phone

        );
        $token = $this->monnifyLoginToken();
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => config('app.monnifyBaseUrl') . '/api/v1/vas/bvn-details-match/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json",
                    "Authorization: Bearer $token",
                ],
        ));
        curl_close($curl);
        $response = curl_exec($curl);

        $result = json_decode($response);
        return $result;
    }

    public function getNin($nin)
    {
        $data = array(
            "nin" => $nin,
        );
        $token = $this->monnifyLoginToken();
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => config('app.monnifyBaseUrl') . '/api/v1/vas/nin-details',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json",
                    "Authorization: Bearer $token",
                ],
        ));
        curl_close($curl);
        $response = curl_exec($curl);

        $result = json_decode($response);
        return $result;
    }
    
    

    public function updateMonnify($acct_ref,$bvn="",$nin=""){
        $token = $this->monnifyLoginToken();
        $curl = curl_init();

        if(!is_null($bvn)){
            $data = array(
                "bvn" => $bvn,
            );

        }elseif(!is_null($nin)){
            $data = array(
                "nin" => $nin,
            );
        }


        curl_setopt_array($curl, array(
            CURLOPT_URL => config('app.monnifyBaseUrl') . '/api/v1/bank-transfer/reserved-accounts/'.$acct_ref.'/kyc-info',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json",
                    "Authorization: Bearer $token",
                ],
        ));
        curl_close($curl);
        $response = curl_exec($curl);

        $result = json_decode($response);
        return $result;
    }
    
    public function rehobothLoginToken()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => config('app.rehobothBaseUrl')."init",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'Rehoboth_Api_Email' => config('app.rehobothEmail'),
                'Rehoboth_Api_Password' => config('app.rehobothPassword'),
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        // echo $response;
        
        return json_decode($response,true);

        
    }
    
    public function initializeMonnifyPayment($customer_name,$customer_email,$paymentReference,$amount)
    {
        $token = $this->monnifyLoginToken();

        $response = Http::withHeaders([
            'Authorization' => "Bearer " . $token,
            'Content-Type' => 'application/json',

        ])
            ->post(config('app.monnifyBaseUrl') . '/api/v1/merchant/transactions/init-transaction', [
                "amount" => $amount,
                "customerName" => $customer_name,
                "customerEmail" => $customer_email,
                "paymentReference" => $paymentReference,
                "paymentDescription" => "Atm Wallet Funding",
                "currencyCode" => "NGN",
                "contractCode" => config('app.monnifyContractCode'),
                "redirectUrl" => "https://swiftlinkng.com/dashboard/callback",
                "paymentMethods" => ["CARD"]
            ]);
        return $response->json();

    }
    
    public function initializeBudPayPayment($amount,$customer_email,$customer_reference)
    {
        $secret_key = config('app.budPaySecretKey');

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer " . $secret_key

        ])
            ->post(config('app.budPayBaseUrl') . '/transaction/initialize', [
                "amount" => str($amount),
                "currency" => "NGN",
                "email" => $customer_email,
                "callback" => "https://swiftlinkng.com/dashboard/callback",
                "reference" => $customer_reference
            ]);
        return $response->json();

    }


    public function VerifyMonnify($transaction_reference)
    {
        $token = $this->monnifyLoginToken();
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,

        ])->get("https://api.monnify.com/api/v2/transactions/$transaction_reference");
        return $response->json();
    }
    
    public function VerifyBudpay($transaction_reference)
    {
        $secret_key = config('app.budPaySecretKey');

        $response = Http::withHeaders([
            "Content-Type" => "application/json",
            'Authorization' => 'Bearer ' . $secret_key,

        ])->get(config('app.budPayBaseUrl') . "/transaction/verify/$transaction_reference");
        return $response->json();
    }


    public function monnifyLoginToken()
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => config('app.monnifyBaseUrl') . '/api/v1/auth/login/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Basic " . base64_encode(config('app.monnifyAPIKey') . ":" . config('app.monnifySecretKey')),
            ],
        ));
        curl_close($curl);
        $response = curl_exec($curl);

        $result = json_decode($response);
        return $result->responseBody->accessToken;
    }

    public  function hasReservedAccount()
    {
        return is_null(User::find($this->id)->reserved_acct)  ? false : true;
    }

    public function referrer()
    {
        //the user who referred current user
        return User::where('id', $this->referral_id)->first();
    }


    public function bonus()
    {

        $subcategory = Subcategory::where('title', 'Bonus')->first();
        $item =  json_decode($subcategory->products);
        $result = $this->getUserLevel($item, $this->userlevel);
        return $result;
    }

    public function fundmax()
    {

        $subcategory = Subcategory::where('title', 'Upgrade')->first();
        $item =  collect(json_decode($subcategory->products));
        $level = $this->translateLevel($this->userlevel);

        $getSelectedProduct = $item->where('title', $level)->first();

        $result = $getSelectedProduct->fundmax;



        return $result;
    }

    public function fundmin()
    {

        $subcategory = Subcategory::where('title', 'Upgrade')->first();
        $item =  collect(json_decode($subcategory->products));
        $level = $this->translateLevel($this->userlevel);

        $getSelectedProduct = $item->where('title', $level)->first();

        $result = $getSelectedProduct->fundmin;


        return $result;
    }

    public function bonusIsAwardable()
    {
         $userRef = $this->referrer();
        if (is_null($userRef)) {
            return false;
        }

        // $to = Carbon::createFromDate($this->created_at);
        // $from = Carbon::now();
        // //SETTINGS KEY

        // $getSettings = Setting::where('key', 'BONUS_DURATION')->first();
        // $bonusDuration = $getSettings->value;
        // $diff_in_days = $to->diffInDays($from);

        // return $diff_in_days <= $bonusDuration ? true : false;
        return true;
    }
}
