<?php

namespace App\Traits;

use App\AppConstants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\MtnLogin;

function format_my_phone($phone){
$phone = str_replace(" ","",$phone);
$pattern = '/\+?234[0-9]{10}/';
    if(preg_match($pattern,$phone)){
        $phone = "0" . preg_replace('/^\+?234|\|1|\D/', '', ($phone));
        return $phone;
    }else{
        $phone = $phone;
        return $phone;
    }
}


trait IntegrationsTrait
{
    
    public function addCountryCode($number) {
        // Check if the number already starts with 234
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
    
    public function addFullCountryCode($number)
    {
        // Check if the number already starts with +234
        if (substr($number, 0, 4) === "+234") {
            return $number; // Return as is
        }

        // Remove leading zero if present
        if ($number[0] === "0") {
            $number = substr($number, 1);
        }

        // Add +234 in front of the number
        return "+234" . $number;
    }

    public function parseSMEPlugNetworkID($network)
    {
        if ($network == "mtn") {
            return 1;
        }
        if ($network == "airtel") {
            return 2;
        }
        if ($network == "9mobile") {
            return 3;
        }
        if ($network == "glo") {
            return 4;
        }
    }

    public function parseOgaDamsNetworkID($network)
    {
        if ($network == "mtn") {
            return 1;
        }
        if ($network == "airtel") {
            return 2;
        }
        if ($network == "9mobile") {
            return 4;
        }
        if ($network == "glo") {
            return 3;
        }
    }
    
    public function ayinlakNetworkID($network)
    {
        if ($network == "mtn") {
            return 1;
        }
        if ($network == "airtel") {
            return 4;
        }
        if ($network == "9mobile") {
            return 3;
        }
        if ($network == "glo") {
            return 2;
        }
    }
    
    public function parseAutoPilotNetworkID($network)
    {
        if ($network == "mtn") {
            return "1";
        }
        if ($network == "airtel") {
            return "2";
        }
        if ($network == "9mobile") {
            return "4";
        }
        if ($network == "glo") {
            return "3";
        }
    }
    
    public function parseAutoSyncNetworkID($network)
    {
        if ($network == "mtn") {
            return "1";
        }
        if ($network == "airtel") {
            return "2";
        }
        if ($network == "9mobile") {
            return "4";
        }
        if ($network == "glo") {
            return "15";
        }
    }
    
    public function parseTBCHPortalID($network)
    {
        if ($network == "mtn") {
            return 1;
        }
        if ($network == "airtel") {
            return 2;
        }
        if ($network == "9mobile") {
            return 3;
        }
        if ($network == "glo") {
            return 4;
        }
    }

    public function SMEPlugAirtimeApi($network_id, $amount, $phone, $customer_reference)
    {
        $curl = curl_init();
        $data =  array(
            "network_id" => $network_id,
            "amount" => $amount,
            "phone_number" => $phone,
            "type" => 1,
            "customer_reference" => $customer_reference

        );

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://smeplug.com/api/v1/vtu',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . config('app.smePlugPrivateKey')
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response);
    }

    public function SMEPlugApi($network_id, $plan_id, $phone, $customer_reference)
    {
        $curl = curl_init();
        $data =  array(
            "network_id" => $network_id,
            "plan_id" => $plan_id,
            "phone" => $phone,
            "customer_reference" => $customer_reference

        );

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://smeplug.com/api/v1/data/purchase',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . config('app.smePlugPrivateKey')
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response);
    }

    public function VtPassfetchBouquet($serviceID)
    {
        $str_contains = str_contains($serviceID, 'Electric');
        if ($str_contains) {
            $rec = explode('-', $serviceID);
            $recIndex = $rec[1];
            $val = str_replace(' ', '-', trim($recIndex));
            $serviceID = trim($val);
        }
        $serviceID = strtolower($serviceID);

        $response = Http::withHeaders([
            'api-key' => config('app.vtpassApiKey'),
            'public-key' => config('app.vtpassPublicKey'),
            'Content-Type' => 'application/json',

        ])->get(config('app.vtPassBaseUrl') . "/service-variations?serviceID=$serviceID");
        return $response->json()['content']['varations'];
    }



    public function VtPassVerify($billerCode, $serviceID, $type = "")
    {

        $str_contains = str_contains($serviceID, 'Electric');
        if ($str_contains) {
            $rec = explode('-', $serviceID);
            $recIndex = $rec[1];
            $val = str_replace(' ', '-', trim($recIndex));
            $serviceID = trim($val);
        }
        $serviceID = strtolower($serviceID);

        $response = Http::withHeaders([
            'api-key' => config('app.vtpassApiKey'),
            'secret-key' => config('app.vtpassSecretKey'),
            'Content-Type' => 'application/json',

        ])->post(config('app.vtPassBaseUrl') . "/merchant-verify", $type == '' ? [
                'billersCode' => $billerCode,
                'serviceID' => $serviceID,

            ] : [
                'billersCode' => $billerCode,
                'serviceID' => $serviceID,
                'type' => $type,

            ]);

        return $response->json();
    }

    public function VtPassAirtime($serviceID, $amount, $phone, $ref)
    {
        $serviceID = strtolower($serviceID);
        \Log::info($serviceID);

        $response = Http::withHeaders([
            'api-key' => config('app.vtpassApiKey'),
            'secret-key' => config('app.vtpassSecretKey'),
            'Content-Type' => 'application/json',

        ])->post(config('app.vtPassBaseUrl')  . "/pay", [
                'serviceID' => $serviceID == "9mobile" ? "etisalat" : $serviceID,
                'request_id' => $ref,
                'amount' => $amount,
                'phone' => $phone,
            ]);

        return $response->json();
    }

    public function VtPassPurchase($billerCode, $serviceID, $variationCode, $amount, $phone, $ref, $subtype = 'renew')
    {
        $serviceID = strtolower($serviceID);

        $response = Http::withHeaders([
            'api-key' => config('app.vtpassApiKey'),
            'secret-key' => config('app.vtpassSecretKey'),
            'Content-Type' => 'application/json',

        ])->post(config('app.vtPassBaseUrl')  . "/pay", [
                'billersCode' => $billerCode,
                'serviceID' => $serviceID,
                'request_id' => $ref,
                'variation_code' => $variationCode,
                'amount' => $amount,
                'phone' => $phone,
                'subscription_type' => $subtype,
                'quantity' => 1
            ]);

        return $response->json();
    }

    public function VtPassRequery($request_id)
    {
        $response = Http::withHeaders([
            'api-key' => config('app.vtpassApiKey'),
            'secret-key' => config('app.vtpassSecretKey'),
            'Content-Type' => 'application/json',

        ])->post(config('app.vtPassBaseUrl')  . "/requery", [
                'request_id' => $request_id,

            ]);
        return $response->json();
    }

    public function smartSMSsend($request_id, $sender, $recipient, $message)
    {

        // $response = Http::withBody()->post(AppConstants::smartSMSBaseUrl . "/sms", [
        //     'token' => AppConstants::smartSMStoken,
        //     'sender' => $sender,
        //     'to' => $recipient,
        //     'message' => $message,
        //     'type' => 0,
        //     'routing' => 4,
        //     'ref_id' => $request_id
        // ]);
        // return $response->json();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => config('app.smartSMSBaseUrl') . "/sms/",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'token' => config('app.smartSMStoken'),

                'sender' => $sender,
                'to' => $recipient,
                'message' => $message,
                'type' => 0,
                'routing' => 4,
                'ref_id' => $request_id
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        //echo $response;

        return json_decode($response, true);
    }

    public function SIMServerBuy($productCode, $amount, $phone, $ref)
    {
        $response = Http::post(config('app.simServerBaseUrl'), [
            'process' => "buy",
            'api_key' => config('app.simServerApiKey'),
            'product_code' => $productCode,
            'amount' => $amount,
            'recipient' => $phone,
            'callback' => config('app.siteUrl'),
            'user_reference' => $ref
        ]);
        return $response->json();
    }

    public function EGMSPurchase($productCode, $amount, $phone, $ref)
    {
        $response = Http::withHeaders([
            'x-api-key' => config('app.egmsApiKey'),
        ])->post(config('app.egmsBaseUrl'), [
            'sponsorId' => "afrithinkers",
            'planId' => $productCode,
            'amount' => $amount,
            'msisdn' => $phone,
            'bucketId' => 11,
            'transId' => $ref,
            'quantity' => $amount,
            'ignoresms' => false,

        ]);
        return $response->json();
    }

    public function SIMServerRequery($order_id)
    {
        $response = Http::withBody()->post(AppConstants::simServerBaseUrl, [
            'process' => "check_status",
            'api_key' => AppConstants::simServerApiKey,
            'order_id' => $order_id,

        ]);
        return $response->json();
    }


    public static function computeSHA512TransactionHash($stringifiedData, $clientSecret)
    {
        $computedHash = hash_hmac('sha512', $stringifiedData, $clientSecret);
        return $computedHash;
    }


    // public function providusCreateAccount($accountName, $bvn)
    // {
    //     if($bvn==""){
    //         $bvn = "01234567891";
    //     }

    //     $response = Http::acceptJson()->timeout(60)->withHeaders([
    //         'Content-Type' => 'application/json',
    //         'Client-Id' => config('app.PROVIDUS_CLIENT_ID'),
    //         'X-Auth-Signature' => config('app.PROVIDUS_CLIENT_SECRET'),
    //     ])->post(config('app.providusBaseUrl')  . "PiPCreateReservedAccountNumber", [
    //         'account_name' => $accountName,
    //         'bvn' => $bvn,
    //     ]);

    //     return $response->json();
    // }
    
     public function providusCreateAccount($accountName, $bvn)
    {
        if($bvn==""){
            $bvn = "01234567891";
        }
        
        //----------------------------------------------------
        //AFTER ADDING NEW PROVIDUS ACCOUNTS TO DB. ENABLE THIS LINE
        //-----------------------------------------------------
        
        // $side_jobs = DB::table('providus_accounts')->where('status',0)->get();

        // foreach($side_jobs as $side_job){
        //     \Log::info($side_job->account_number);
        //     $user = User::where('providus_account',$side_job->account_number)->first();
        //     if(!is_null($user)){
        //         User::where('id',$user->id)->update(['providus_account' => null, 'providus_reserved_acct' => null]);
        //     //   DB::table('providus_accounts')->where('id',$side_job->id)->update(['status' => 0]);
        //     }

        // }
        
         //----------------------------------------------------
        // END HERE
        //-----------------------------------------------------
        
        
        
        // $side_jobs = DB::table('used_providus')->where('status',0)->get();
        // foreach($side_jobs as $side_job){
        //     $name = explode(" ",$side_job->account_name);
        //     $arr = json_encode(array("bankCode" => "","bankName" => "Providus Bank","accountNumber" => $side_job->account_number,"accountName" => "SWIFTLINKNG(".$side_job->account_name .")"));
        //     $user = User::where(['firstname' => $name[0],'lastname'=>$name[1]])->update(['providus_account' => $side_job->account_number,'providus_reserved_acct' => $arr ]);
        //     $done = DB::table('used_providus')->where('account_number',$side_job->account_number)->update(['status' => 1]);
        //     // if(!is_null($user)){
        //     //     User::where('id',$user->id)->update(['providus_account' => null, 'providus_reserved_acct' => null]);
        //     //     DB::table('providus_accounts')->where('id',$side_job->id)->update(['status' => 0]);
        //     // }

        // }
        
        //\Log::info("Old account restored");

        $providus_account_number = DB::table('providus_accounts')->where('status',0)->first();

        $account_number = $providus_account_number->account_number;
        $account_id = $providus_account_number->id;
        DB::table('providus_accounts')->where('id',$account_id)->update(['status' => 1]);


        $response = Http::acceptJson()->timeout(60)->withHeaders([
            'Content-Type' => 'application/json',
            'Client-Id' => config('app.PROVIDUS_CLIENT_ID'),
            'X-Auth-Signature' => config('app.PROVIDUS_CLIENT_SECRET'), 
        ])->post(config('app.providusBaseUrl')  . "PiPUpdateAccountName", [
            'account_number' => $account_number,
            'account_name' => $accountName,
        ]);

        $result = $response->json();

        // \Log::info(print_r($result,true));

        if($result['responseCode'] == "00"){
            $acc = array("requestSuccessful" => true,"account_number" => $account_number,"account_name" => "SWIFTLINKNG(".$accountName .")");
        }

        
        \Log::info(print_r($acc,true));

        return $acc;
    }

    public function RingofetchElect($serviceID)
    {

        $response =
            Http::acceptJson()->withHeaders([
                'Content-Type' => 'application/json',
                'email' => config('app.ringoEmail'),
                'password' => config('app.ringoPassword'),
            ])->post(config('app.ringoBaseUrl')  . "products", [
                'serviceCode' => $serviceID,
            ]);



        $result = $response->json();
        $items = array();
        $newarr = array();

        foreach ($result as $data) {
            array_push($items, array('product' => explode('_', $data['product'])[0]));
            // $items['p'] = explode('_', $data['product'])[0];
        }

        $res =  collect($items)->unique()->toArray();

        // $unique = array_unique($items['product']);
        // array_merge($newarr, $res->toArray());
        return $res;
    }


    public function RingofetchCable($serviceID)
    {

        $response =
            Http::acceptJson()->withHeaders([
                'email' => config('app.ringoEmail'),
                'password' => config('app.ringoPassword'),
            ])->post(config('app.ringoBaseUrl')  . "agent/p2", [
                'serviceCode' => "V-TV",
                "type" => $serviceID,
                "smartCardNo" => "10441003943"
            ]);

        $result = $response->json();
        $items = array();
        $newarr = array();

        foreach ($result['product'] as $data) {
            array_push($items, array('name' => $data['name'], 'variation_code' => $data['code'], 'variation_amount' => $data['price']));
        }

        $res =  collect($items)->unique()->toArray();

        // $unique = array_unique($items['product']);
        // array_merge($newarr, $res->toArray());
        return $res;
    }

    public function verifyRingo($billerCode, $serviceID, $type)
    {
        $str_contains = str_contains($serviceID, 'Electric');
        if ($str_contains) {
            $rec = explode('-', $serviceID);
            $serviceID = trim($rec[0]);
        }
        $response =
            Http::acceptJson()->withHeaders([
                'Content-Type' => 'application/json',
                'email' => config('app.ringoEmail'),
                'password' => config('app.ringoPassword'),
            ])->post(config('app.ringoBaseUrl')  . "agent/p2", [

                "serviceCode" => "V-ELECT",
                "disco" => strtoupper($serviceID),
                "meterNo" => $billerCode,
                "type" => strtoupper($type),
                "amount" => 200,
                "phonenumber" => "",
                "request_id" => random_int(100000, 999999)
            ]);

        $result = $response->json();
        $items = [];
        if (!is_null($result)) {
            $items['content']['Customer_Name'] = $result['customerName'];
        }
        return $items;
    }


    public function verifyRingoCable($billerCode, $serviceID)
    {

        $response =
            Http::acceptJson()->withHeaders([
                'Content-Type' => 'application/json',
                'email' => config('app.ringoEmail'),
                'password' => config('app.ringoPassword'),
            ])->post(config('app.ringoBaseUrl')  . "agent/p2", [

                "serviceCode" => "V-TV",
                "type" => strtoupper($serviceID),
                "smartCardNo" => $billerCode
            ]);

        $result = $response->json();
        $items = [];
        if (!is_null($result)) {
            $items['content']['Customer_Name'] = $result['customerName'];
        }
        return $items;
    }

    public function purchaseRingo($billerCode, $serviceID, $type, $amount, $phone, $ref, $subtype = 'renew')

    {
        $str_contains = str_contains($serviceID, 'Electric');
        if ($str_contains) {
            $rec = explode('-', $serviceID);
            $serviceID = trim($rec[0]);
        }
        $response =
            Http::acceptJson()->withHeaders([
                'Content-Type' => 'application/json',
                'email' => config('app.ringoEmail'),
                'password' => config('app.ringoPassword'),
            ])->post(config('app.ringoBaseUrl')  . "agent/p2", [
                "serviceCode" => "P-ELECT",
                "disco" => strtoupper($serviceID),
                "meterNo" => $billerCode,
                "type" => strtoupper($type),
                "amount" => intVal($amount),
                "phonenumber" => $phone,
                "request_id" => $ref
            ]);

        return $response->json();
    }


    public function purchaseRingoCable($billerCode, $serviceID, $type, $ref, $price = 0)
    {
        $serviceID = strtoupper($serviceID);
        $response =
            Http::acceptJson()->withHeaders([
                'Content-Type' => 'application/json',
                'email' => config('app.ringoEmail'),
                'password' => config('app.ringoPassword'),
            ])->post(
                config('app.ringoBaseUrl')  . "agent/p2",
                ($serviceID != "STARTIMES") ? [
                    "serviceCode" => "P-TV",
                    "type" => $serviceID,
                    "smartCardNo" => $billerCode,
                    "name" => $type,
                    "code" => $type,
                    "period" => 1,
                    "request_id" => $ref

                ] :
                    [
                        "serviceCode" => "P-TV",
                        "type" => $serviceID,
                        "smartCardNo" => $billerCode,
                        "price" => $price,
                        "request_id" => $ref

                    ]
            );

        return $response->json();
    }

    public function ringoReQuery($ref)
    {
        $response =
            Http::acceptJson()->withHeaders([
                'Content-Type' => 'application/json',
                'email' => config('app.ringoEmail'),
                'password' => config('app.ringoPassword'),
            ])->post(config('app.ringoBaseUrl')  . "b2brequery", [
                "request_id" => $ref
            ]);

        return $response->json();
    }
    public function ogaDamReQuery($ref)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->timeout(60)
            ->acceptJson()
            ->withToken(config('app.ogaDamToken'))
            ->get(config('app.ogaDamBaseUrl'). "v1/verify/transaction/$ref");

        return $response->json();

    }


    public function purchaseAirtimeNigeria($productCode, $amount, $phone, $ref)
    {

        $response =
            Http::acceptJson()->withHeaders([
                'Content-Type' => 'application/json',
            ])->withToken(config('app.airtimeNigeriaAPIKey'))->post(
                config('app.airtimeNigeria')  . "data/wallet",
                [
                    "phone" => $phone,
                    "package_code" => $productCode,
                    //"max_amount" => $amount,
                    "process_type" => "instant",
                    "customer_reference" => $ref,
                    "callback_url" => '',
                ]
            );

        return $response->json();
    }


    public function reQueryAirtimeNigeria($ref)
    {
        $response =
            Http::acceptJson()->withHeaders([
                'Content-Type' => 'application/json',
            ])->withToken(config('app.airtimeNigeriaAPIKey'))->get(
                config('app.airtimeNigeria')  . "delivery",
                [
                    "reference" => $ref,
                ]
            );

        return $response->json();
    }

    public function squadcoGT($email, $firstname, $lastname, $phone,$bvn)
    {
        $response =
            Http::acceptJson()->withHeaders([
                'Content-Type' => 'application/json',
            ])->withToken(config('app.gtsquadcoAPIKey'))->post(
                config('app.gtsquadcoBaseUrl')  . "virtual-account/business",
                [
                    "customer_identifier" => $email,
                    "business_name" => "Swiftlinkng" . $firstname . ' ' . $lastname,
                    "mobile_num" => $phone,
                    "beneficiary_account" => "0796265189",
                    "bvn" => $bvn,
                ]
            );
            
            $payload = array(
                "customer_identifier" => $email,
                    "business_name" => "Swiftlinkng" . $firstname . ' ' . $lastname,
                    "mobile_num" => $phone,
                    "beneficiary_account" => "0796265189",
                    "bvn" => $bvn,
            );
            \Log::info("SQUADCO PAYLOAD");
            \Log::info(print_r($payload,true));
            
    

        return $response->json();
    }
    public function updateSquadcoGT($customer_identifier,$bvn,$phone)
    {
        $response =
            Http::acceptJson()->withHeaders([
                'Content-Type' => 'application/json',
            ])->withToken(config('app.gtsquadcoAPIKey'))->patch(
                config('app.gtsquadcoBaseUrl')  . "virtual-account/update/bvn",
                [
                    "customer_identifier" => $customer_identifier,
                    "phone_number" => $phone,
                    "bvn" => $bvn,
                ]
            );

        return $response->json();
    }


    public function dataBayGetToken()
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-api-key' => config('app.dataBayAPIKey')
        ])->acceptJson()->post(config('app.dataBayBaseUrl') . 'merchant/login', [
            'email' => config('app.dataBayEmail'),
            'password' => config('app.dataBayPassword'),
        ]);
        return $response->json();
    }



    public function dataBayBuyData($plan_id, $phone)
    {
        $token =  $this->dataBayGetToken();

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-api-key' => config('app.dataBayAPIKey')
        ])->acceptJson()
            ->withToken($token['payload']['token'])
            ->post(config('app.dataBayBaseUrl') . 'merchant/data-purchase', [
                'plan' =>  $plan_id,
                'number' => $phone,

            ]);
        return $response->json();
    }


    public function ogaDamBuyData($network_id, $plan_id, $phone, $ref)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->timeout(60)
            ->acceptJson()
            ->withToken(config('app.ogaDamToken'))
            ->post(config('app.ogaDamBaseUrl') . 'v1/vend/data', [
                'planId' =>  $plan_id,
                'phoneNumber' => $phone,
                'networkId' => $network_id,
                'reference' => $ref
            ]);

        return $response->json();
    }

    public function providusRepush($settlement_id)
    {

        $response = Http::acceptJson()->timeout(60)->withHeaders([
            'Content-Type' => 'application/json',
            'Client-Id' => config('app.PROVIDUS_CLIENT_ID'),
            'X-Auth-Signature' => config('app.PROVIDUS_CLIENT_SECRET'),
        ])->post(config('app.providusBaseUrl')  . "PiP_RepushTransaction_SettlementId", [
            'settlement_id' => $settlement_id,
            'session_id' => "",
        ]);

        return $response->json();
    }

    //AirtelEduBuyData($phone,$request_body)

    public function AirtelEduBuyData($phone, $request_body)
    {
        $phone = str_replace(" ","",$phone);

        $cookie = Setting::where('key','AIRTEL_COOKIE')->first();
        $cookiefile = $cookie->value;
        $curl = curl_init();
        $body = '[{"MobileNumber":"' . $phone . '","Request":"' . $request_body . '","contractNumber":"3726249224"}]';
        // $body='[{"MobileNumber":"08122800051","Request":"100MB (7days Validity)","contractNumber":"3726249224"}]' ;

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://selfcare.airtel.com.ng/EducationSuite/Home/BundleRequestAjax',
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json; charset=utf-8",
                "cookie: edusuite=$cookiefile",
            ),
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.2309.372 Safari/537.36',
            CURLOPT_COOKIEFILE => $cookiefile,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_REFERER => 'https://selfcare.airtel.com.ng/educationsuite/account/login/',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,


        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);
    }


    public function gongozconceptData($network_id, $plan_id, $phone)
    {
        $phone = format_my_phone($phone);

        $response = Http::withHeaders([
            'Authorization' => "Token " . config('app.gongozconceptToken'),
            'Content-Type' => 'application/json',

        ])
            ->post(config('app.gongozconceptBaseUrl') . 'data/', [
                'plan' =>  $plan_id,
                'mobile_number' => $phone,
                'Ported_number' => true,
                'network' => 2
            ]);
        return $response->json();
    }

    public function ayinlakconnectData($network_id, $plan_id, $phone)
    {
        $phone = format_my_phone($phone);

        $response = Http::withHeaders([
            'Authorization' => "Token " . config('app.ayinlakconnectToken'),
            'Content-Type' => 'application/json',

        ])
            ->post(config('app.ayinlakconnectBaseUrl') . 'data/', [
                'plan' =>  $plan_id,
                'mobile_number' => $phone,
                'Ported_number' => true,
                'network' => $network_id
            ]);
        return $response->json();
    }

     public function jonetData($plan_id, $phone,$customer_id)
    {

        $phone = format_my_phone($phone);

        $response = Http::withHeaders([
            'Authorization' => config('app.jonetKey'),
            'Content-Type' => 'application/json',

        ])
            ->post(config('app.jonetBaseUrl') . 'purchase_data.php', [
                'code' =>  $plan_id,
                'phone' => $phone,
                'customer_id' => $customer_id
            ]);
        return $response->json();
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


    public function rehobothWebHookTransaction($trans_ref)
    {
        $reb = $this->rehobothLoginToken();
        $authSignature = $reb['Authorisation']['auth'];
        $token = $reb['Authorisation']['accesscode'];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => config('app.rehobothBaseUrl')."webhook/transaction/".$trans_ref,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                "X-Auth-Signature: $authSignature",
                "Authorization: Bearer $token",
                'Content-Type: application/json',
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        // echo $response;


        return json_decode($response,true);


    }

    public function autoPilotData($network_id,$datatype,$plan_id, $phone, $ref)
    {

        $phone = format_my_phone($phone);

        $response = Http::withHeaders([
            'Authorization' => "Bearer " . config('app.autoPilotKey'),
            'Content-Type' => 'application/json',

        ])
            ->post(config('app.autoPilotBaseUrl') . 'data', [
                'networkId' =>  $network_id,
                'dataType' => $datatype,
                'planId' => $plan_id,
                'phone' => $phone,
                'reference' => $ref
            ]);
        return $response->json();
    }
    
    public function autoPilotAirtime($network_id,$airtimeType,$amount, $phone, $ref)
    {

        $phone = format_my_phone($phone);

        $response = Http::withHeaders([
            'Authorization' => "Bearer " . config('app.autoPilotKey'),
            'Content-Type' => 'application/json',

        ])
            ->post(config('app.autoPilotBaseUrl') . 'airtime', [
                'networkId' =>  $network_id,
                'airtimeType' => $airtimeType,
                'amount' => $amount,
                'phone' => $phone,
                'reference' => $ref
            ]);
        return $response->json();
    }
    
    public function autoPilotAwuf($network_id,$datatype,$plan_id, $phone, $ref)
    {

        $phone = format_my_phone($phone);

        $response = Http::withHeaders([
            'Authorization' => "Bearer " . config('app.autoPilotKey'),
            'Content-Type' => 'application/json',

        ])
            ->post(config('app.autoPilotBaseUrl') . 'data', [
                'networkId' =>  $network_id,
                'dataType' => $datatype,
                'planId' => $plan_id,
                'phone' => $phone,
                'reference' => $ref
            ]);
        return $response->json();
    }
    
    public function tbchPortal($network_id,$product_id, $phone,$ref)
    {

        $phone = format_my_phone($phone);

        $response = Http::withHeaders([
            'Authorization' => "Bearer " . config('app.tbchportalToken'),
            'Content-Type' => 'application/json',

        ])
            ->post(config('app.tbchportalUrl') . 'data', [
                "network_id" => $network_id,
                "product_id" => $product_id,
                "phone" =>  $phone,
                "customer_ref" => $ref,
                "webhook_url" => "https://swiftlinkng.com/tbch-webhook"
            ]);
        return $response->json();
    }
    
    public function autoSyncPortal($request_ref,$phone,$product_id,$variation_code,$pin)
    {

        $response = Http::withHeaders([
            'Authorization' => "Bearer " . config('app.autoSyncToken'),
            'Content-Type' => 'application/json',

        ])->timeout(90)->post(config('app.autoSyncUrl') . 'data', [
                "request_ref" => $request_ref,
                "phone" => $phone,
                "product_id" =>  3,
                "variation_code" => $variation_code,
                "pin" => $pin,
                "ported_no" => true
            ]);
        return $response->json();
    }
    
    public function subArenaPortal($network,$phone,$plan_id)
    {
       
        $response = Http::withHeaders([
            'Authorization' => "Token " . config('app.subArenaToken'),
            'Content-Type' => 'application/json',

        ])
            ->post(config('app.subArenaUrl') . 'data/', [
                "network" => $network,
                "mobile_number" => $phone,
                "plan" =>  $plan_id,
                "Ported_number" => true,
            ]);
        return $response->json();
    }
    
     public function topupAccessPortal($plan_id,$pin,$phone_number)
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer " . config('app.topupAccessToken'),
            'Content-Type' => 'application/json',

        ])
            ->post(config('app.topupAccessUrl') . 'data/topup/', [
                "id" => $plan_id,
                "pin" => $pin,
                "number" => $phone_number,
            ]);
        return $response->json();
    }

    
    public function VerifyFlutterwave($trans_id)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('app.flwSecretKey'),

        ])->get("https://api.flutterwave.com/v3/transactions/$trans_id/verify");
        return $response->json();
    }
    
    public function buyDataSme($beneficiaryNum,$shareID,$pin)
    {
        //$sponsorNum = Storage::disk('local')->get('mtnnum.txt');

        //$jsonResponse = Storage::disk('local')->get('mtntokenweb.txt');
        $sponsorNum = Setting::where('key', 'MTN_SPONSOR_NUMBER')->first()->value;

        $jsonResponse = Setting::where('key','MTN_TOKEN')->first()->value;
        $responseData = json_decode($jsonResponse, true);
        $authToken = $responseData['token'];

        $body='{"msisdn":"'.$sponsorNum.'","pin":"'.$pin.'","beneficiary":"'.$beneficiaryNum.'","share_id":"ME2U_NG_Data2Share_'.$shareID.'"}';
        $inputKey="websmart@2020";
        //$authToken= CryptoJSAES::encrypt($authToken, $inputKey);
        $body=MyCrypto::encrypt($body, $inputKey);
        $newbody='{"data": "'.$body.'"}';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, 'https://mymtn.com.ng/app/api/sharedsmedata');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $headers = array(
        "Content-Type: application/json",
        "Authorization:Bearer ".$authToken."",
        "connection:Keep-Alive",
        "Referer:https://mymtn.com.ng/buybundles/smebundles",
        "Sec-Ch-Ua: \"Google Chrome\";v=\"117\", \"Not;A=Brand\";v=\"8\", \"Chromium\";v=\"117\"",
        "Sec-Ch-Ua-Mobile:?0",
        "Sec-Ch-Ua-Platform: \"Windows\"",
        "User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36",
        "Adrum: isAjax:true",
        "x-country-code:nga");

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$newbody);
        curl_setopt($ch, CURLOPT_POST,1);

        $resp = curl_exec($ch);
        if($resp != false) {
            $decryptedText = MyCrypto::decrypt($resp, $inputKey);
            curl_close($ch);
            $response = json_decode($decryptedText,true);
            return $response;
        }else{

           $resp =  Array(
                "0" => Array(
                    "RespDescription" => "Could not reach mtn website. Hence, we are unable to process your request",
                    "Status" => "FAILURE"
                ),
                "version" => "Patch",
                );

            curl_close($ch);

            return $resp;
        }
    }
    
    public function buyCG2($beneficiaryNum,$shareData)
    {
        //$sponsorNum = Storage::disk('local')->get('mtnnum.txt');

        //$jsonResponse = Storage::disk('local')->get('mtntokenweb.txt');
        $sponsorNum = Setting::where('key', 'MTN_SPONSOR_NUMBER_CG')->first()->value;

        $jsonResponse = Setting::where('key','MTN_TOKEN_CG')->first()->value;
        $responseData = json_decode($jsonResponse, true);
        $authToken = $responseData['token'];

        $packageName = "Internet/default option";
        $senderID = "131";
        $dataDuration = "30 days";

        $body='{"msisdn":"'.$sponsorNum.'","beneficiary":"'.$beneficiaryNum.'","pkgname":"'.$packageName.'","sharedata": "'.$shareData.'","senderID":"'.$senderID.'","dataDuration":"'.$dataDuration.'"}';
        
        $inputKey="websmart@2020";
        //$authToken= CryptoJSAES::encrypt($authToken, $inputKey);
        $body=MyCrypto::encrypt($body, $inputKey);
        $newbody='{"data": "'.$body.'"}';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, 'https://mymtn.com.ng/app/api/datasharesponsored');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $headers = array(
        "Content-Type: application/json",
        "Authorization:Bearer ".$authToken."",
        "connection:Keep-Alive",
        "Referer:https://mymtn.com.ng/buybundles/sponsoredwebpass",
        "Sec-Ch-Ua: \"Google Chrome\";v=\"117\", \"Not;A=Brand\";v=\"8\", \"Chromium\";v=\"117\"",
        "Sec-Ch-Ua-Mobile:?0",
        "Sec-Ch-Ua-Platform: \"Windows\"",
        "User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36",
        "Adrum: isAjax:true",
        "x-country-code:nga");

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$newbody);
        curl_setopt($ch, CURLOPT_POST,1);

        $resp = curl_exec($ch);
        if($resp != false) {
            $decryptedText = MyCrypto::decrypt($resp, $inputKey);
            curl_close($ch);
            $response = json_decode($decryptedText,true);
            return $response;
        }else{

           $resp =  Array(
                "0" => Array(
                    "RespDescription" => "Could not reach mtn website. Hence, we are unable to process your request",
                    "Status" => "FAILURE"
                ),
                "version" => "Patch",
                );

            curl_close($ch);

            return $resp;
        }
    }
    
    public function buyDataSmeApp($beneficiaryNum, $shareID, $pin)
    {
       

        $final_number = $this->addCountryCode($beneficiaryNum);
        // $sponsorNum = Storage::disk('local')->get('mtnnum.txt');

        $authToken = Setting::where('key', 'MTN_ACCESS_TOKEN')->first()->value;

        $body = '{
            "receiverMsisdn":"' . $final_number . '",
            "pin":"' . $pin . '",
            "productCode":"ME2U_NG_Data2Share_' . $shareID . '",
            "agentId": "MTNAPPNXG"
        }';
        
        \Log::info("BUY DATA SME APP");
        \Log::info($body);

        $ch1 = curl_init();
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch1, CURLOPT_URL, 'https://mtn-dxl-share-data.mymtnnxgeaprod.mtnnigeria.net/v1/transfer/customers');
        curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch1, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch1, CURLOPT_FORBID_REUSE, TRUE);
        curl_setopt($ch1, CURLOPT_FRESH_CONNECT, TRUE);
        $headers = array(
            "Content-Type: application/json",
            "authorization:Bearer " . $authToken . "",
            "channel:MTNAPPNXG",
            "host:mtn-dxl-share-data.mymtnnxgeaprod.mtnnigeria.net",
            "user-agent: okhttp/4.10.0",
            "msisdn-code:234",
            "x-country-code:nga"
        );

        curl_setopt($ch1, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch1, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch1, CURLOPT_POST, 1);

        $resp = curl_exec($ch1);
        curl_close($ch1);




        \Log::info($resp);
        if ($resp != false) {
            $response = json_decode($resp, true);
            return $response;
        } else {

            $resp = array(
                "0" => array(
                    "RespDescription" => "Could not reach mtn website. Hence, we are unable to process your request",
                    "Status" => "FAILURE"
                ),
                "version" => "Patch",
            );

            return $resp;
        }

    }
    
    public function buyDirectApp($beneficiaryNum, $product_id)
    {
        $final_number = $this->addCountryCode($beneficiaryNum);
        $mtn_logins = MtnLogin::where('status', 1)->first();

        $authToken =  $mtn_logins->mtn_access_token;
        $sponsorNum = $mtn_logins->phone_number;

        //MTN DIRECT

        $body = json_encode(array(
            "primaryMsisdn" => str_replace("+", "",$sponsorNum),
            "payment_source" => "CIS",
            "product_id" => $product_id,
            "product_type" => "DataPlan",
            "beneficiary_id" => $final_number,
            // "eligibility_check_id" => "",
            "payment_method" => "Airtime",
            // "price" => $amount,
            "renewal" => false,
            // "cvmoffer" => false
        ));

        $ch1 = curl_init();
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch1, CURLOPT_URL, 'https://mtn-dxl-transaction-core.mymtnnxgeaprod.mtnnigeria.net/api/v3/subscription');
        curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch1, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch1, CURLOPT_FORBID_REUSE, TRUE);
        curl_setopt($ch1, CURLOPT_FRESH_CONNECT, TRUE);

         //MTN DIRECT
         $headers = array(
            "Content-Type: application/json",
            "authorization:Bearer " . $authToken . "",
            "channel:MTNAPPNXG",
            "host:mtn-dxl-transaction-core.mymtnnxgeaprod.mtnnigeria.net",
            "user-agent: okhttp/4.10.0",
        );

        curl_setopt($ch1, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch1, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch1, CURLOPT_POST, 1);

        $resp = curl_exec($ch1);
        curl_close($ch1);

        \Log::info($resp);
        if ($resp != false) {
            $response = json_decode($resp, true);
            return $response;
        } else {

            $resp = array(
                "0" => array(
                    "RespDescription" => "Could not reach mtn website. Hence, we are unable to process your request",
                    "Status" => "FAILURE"
                ),
                "version" => "Patch",
            );

            return $resp;
        }

    }
    

    public function zoePortal($network,$phone,$plan_id)
    {

        $response = Http::withHeaders([
            'Authorization' => "Token " . config('app.zoeToken'),
            'Content-Type' => 'application/json',

        ])
            ->post(config('app.zoeUrl') . 'data/', [
                "network" => $network,
                "mobile_number" => $phone,
                "plan" =>  $plan_id,
                "Ported_number" => true,
            ]);
        return $response->json();
    }
    
        public function KeepCookieAlive()
    {
        $cookie = Setting::where('key', 'MTN_COOKIE')->first();
        $cookiefile = $cookie->value;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://selfservice.mtn.ng/api/auth/session',
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json; charset=utf-8",
                "User-Agent: User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:132.0) Gecko/20100101 Firefox/132.0",
                'Accept-Language: en-US,en;q=0.5',
                'Accept-Encoding: gzip, deflate, br, zstd',
                "Referer: https://selfservice.mtn.ng/dashboard/corporate-data-gifting",
                'Content-Type: application/json',
                'Priority: u=0',
                'Origin: https://selfservice.mtn.ng',
                'Connection: keep-alive',
                "cookie: $cookiefile",

            ),
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.2309.372 Safari/537.36',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEFILE => $cookiefile,
            CURLOPT_COOKIEJAR => $cookiefile,
            // CURLOPT_NOBODY => false,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,


        ));


        $response = curl_exec($curl);

        $q = curl_getinfo($curl, CURLINFO_COOKIELIST);

        $cookie_list = curl_getinfo($curl, CURLINFO_COOKIELIST);

        curl_close($curl);


        // COOKIES FROM DB

        $cookie_db = explode(";", $cookiefile);

        \Log::info("COOKIE DB");
        \Log::info($cookie_db);


        $cookies = [];
        foreach($cookie_list as $cookie)
        {
            $parts = explode("\t",$cookie);

            if(count($parts) > 5 && trim($parts[5] === "__Secure-my.mtn.ng.session-token.0" )){
                $cookie_db[2] = "__Secure-my.mtn.ng.session-token.0=".$parts[6];
            }

            if(count($parts) > 5 && trim($parts[5] === "__Secure-my.mtn.ng.session-token.1" )){
                $cookie_db[3] = "__Secure-my.mtn.ng.session-token.1=".$parts[6];
            }

            if(count($parts) > 5 && trim($parts[5] === "__Secure-my.mtn.ng.session-token.2" )){
                $cookie_db[4] = "__Secure-my.mtn.ng.session-token.2=".$parts[6];
            }

            if(count($parts) > 5 && trim($parts[5] === "__Secure-my.mtn.ng.session-token.3" )){
                $cookie_db[5] = "__Secure-my.mtn.ng.session-token.3=".$parts[6];
            }

            // if(stripos($headers, 'Set-Cookie:') === 0){
            //     $cookie = explode(';', substr($header, 12), 2)[0];
            //     $cookies[] = $cookie;
            // }
        }

        $cookies = implode("; ",$cookie_db);

        \Log::info("PARTS COMBINATION");
        \Log::info($cookies);



        \Log::info($response);

        Setting::where("key","MTN_COOKIE")->update(['value' => $cookies]);



        return $cookies;
    }
    
    public function buyCG($beneficiaryNum, $shareData)
    {

        $final_number = $this->addCountryCode($beneficiaryNum);

        $body = array(
            "primaryMsisdn" => "2349135715661",
            "receiverMsisdn" => array($final_number),
            "duplicateNo" =>  0,
            "dataValidity" =>  "30 days",
            "trafficType" => "Internet",
            "senderId" => "131",
            "requestedDataAmount" => $shareData
        );

        $body = json_encode($body);


      
        $cookie = Setting::where('key', 'MTN_COOKIE')->first();
        $cookiefile = $cookie->value;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://selfservice.mtn.ng/api/v1/cdg/v2/datashare',
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json; charset=utf-8",
                "User-Agent:  Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:132.0) Gecko/20100101 Firefox/132.0",
                'Accept-Language: en-US,en;q=0.5',
                'Accept-Encoding: gzip, deflate, br, zstd',
                "Referer: https://selfservice.mtn.ng/business/share-and-borrow/corporate-data-gifting",
                'Content-Type: application/json',
                'Priority: u=0',
                'Origin: https://selfservice.mtn.ng',
                'Connection: keep-alive',
                "cookie: $cookiefile",

            ),
            //CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.2309.372 Safari/537.36',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,


        ));

        $response = curl_exec($curl);

        curl_close($curl);



        if ($response != false) {
            $response = json_decode($response, true);
            return $response;
        } else {

             $resp = array(
                "0" => array(
                    "otherDetails" => "Could not reach mtn website. Hence, we are unable to process your request",
                    "Status" => "Failed"
                ),
            );


            return $resp;
        }

    }

public function generateNonceStr($length = 32)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $nonce = '';
        for ($i = 0; $i < $length; $i++) {
            $nonce .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $nonce;
    }


    public function params_sort(array $data): string {
        // Filter empty string from the array
        $filtered_data = array_filter($data, function($value) {
            return $value !== "";
        });

        // Sort the array by its keys in ascending order
        ksort($filtered_data);

        // Remove the sign key if it exists
        unset($filtered_data['sign']);
        // Build and return the query string
        return urldecode(http_build_query($filtered_data));
    }

    public function sha1_with_rsa(string $encry_data, string $private_key): string {
        // Retrieve the private key resource
        $privateKey = $this->validate_rsa_key($private_key, 'private');
        \Log::info($private_key);
        // Sign the data using the private key and SHA-1 algorithm
        openssl_sign($encry_data, $signature, $privateKey, OPENSSL_ALGO_SHA1);
        //encode the signature in base64 and return it
        return base64_encode($signature);
    }

    public function verify_callback_signature(array $data, string $signature, string $public_key) {
        // Retrieve the public key resource
        $publicKey = $this->validate_rsa_key($public_key, 'public');
        // Calculate the MD5 of the notification payload without the sign field. The param_sort function removes the sign field.
        $md5 = strtoupper(md5($this->params_sort($data)));
        // Verify the signature using the public key and SHA-1 algorithm
        $is_verified = openssl_verify($md5, base64_decode(urldecode($signature)), $publicKey, OPENSSL_ALGO_SHA1);

        return $is_verified;
    }


    public function validate_rsa_key($value, $key_type) {
        // Remove spaces from the private key
        $formatted_key = str_replace(' ', "", $value);

        // Remove trailing spaces from the private key
        $formatted_key = trim($formatted_key);

        // Split the key into chunks of 64 characters with newline breaks
        $formatted_key = chunk_split($formatted_key, 64, "\n");

        // Add appropriate header and footer based on key type
        if ($key_type === 'private') {
            $pem_formatted_key = "-----BEGIN RSA PRIVATE KEY-----\n$formatted_key-----END RSA PRIVATE KEY-----\n";
            $key_resource = openssl_pkey_get_private($pem_formatted_key);
        } else {
            $pem_formatted_key = "-----BEGIN PUBLIC KEY-----\n$formatted_key-----END PUBLIC KEY-----\n";
            $key_resource = openssl_pkey_get_public($pem_formatted_key);
        }

        return $key_resource;
    }

    public function generate_signature(array $data, string $private_key)
    {
        // Create an MD5 hash of the sorted parameters and convert it to uppercase
        \Log::info("Sorted String");
        \Log::info($this->params_sort($data));
        $encry_data = strtoupper(md5($this->params_sort($data)));
        
        \Log::info("MD5");
        \Log::info($encry_data);

        // Sign the hashed data using the private key with SHA-1 RSA
        $signature = $this->sha1_with_rsa($encry_data, $private_key);

        \Log::info("Signature");
        \Log::info($signature);

        return $signature;
    }

    public function palmPay($reference, $customer_name, $email, $licenseNumber)
    {
        $private_key = config('app.palmPayPrivateKey');

        $payload = array(
            "identityType" => "personal",
            "licenseNumber" => $licenseNumber,
            "virtualAccountName" => $customer_name,
            "customerName" => $customer_name,
            "version" => "V2.0",
            "email" => $email,
            "accountReference" => $reference,
            "nonceStr" => $this->generateNonceStr(),
            "requestTime" => round(microtime(true) * 1000)
        );
        ksort($payload);
        
        $response =
            Http::acceptJson()->withHeaders([
                'Content-Type' => 'application/json',
                'countryCode' => 'NG',
                'Signature' => $this->generate_signature($payload, $private_key)
            ])->withToken(config('app.palmPayAppID'))->post(
                    config('app.palmPayUrl') . "api/v2/virtual/account/label/create",
                    $payload
                );


        \Log::info("PALMPAY PAYLOAD");
        \Log::info(print_r($payload, true));



        return $response->json();



        return $response->json();

    }



    public function verifyPalmPayTrans($payload)
    {
        $public_key = config("app.palmPayPublicKey");
        \Log::info("signature");
        \Log::info($payload['sign']);
        \Log::info("public key");
        \Log::info($public_key);
        \Log::info("callback");
        \Log::info($this->verify_callback_signature($payload, $payload['sign'], $public_key));
        return $this->verify_callback_signature($payload, $payload['sign'], $public_key);

    }
    
public function koraPortal($network, $phone, $plan_id)
    {

        $response = Http::withHeaders([
            'Authorization' => "Token " . config('app.korakey'),
            'Content-Type' => 'application/json',

        ])
            ->post(config('app.koraUrl') . 'data/', [
                "type" => $network,
                "mobile_number" => $phone,
                "plan" => $plan_id,
            ]);
        return $response->json();
    }
    
public function royalPortal($network_id, $phone, $plan_id)
    {

        $response = Http::withHeaders([
            'Authorization' => "Bearer " . config('app.royalToken'),
            'Content-Type' => 'application/json',

        ])
            ->post(config('app.royalUrl') . 'v1/data', [
                "network_id" => $network_id,
                "phone" => $phone,
                "plan_id" => $plan_id,
            ]);
        return $response->json();
    }
    
public function subPortal($network, $phone, $plan_id)
    {
        
        $response = Http::withHeaders([
            'Authorization' => "Token " . config('app.subkey'),
            'Content-Type' => 'application/json',

        ])
            ->post(config('app.subUrl') . 'data/', [
                "type" => $network,
                "mobile_number" => $phone,
                "plan" => $plan_id,
            ]);
        return $response->json();
    }
    
public function dataMallPortal($network, $phone, $plan_id)
    {
      
        $response = Http::withHeaders([
            'Authorization' => "Token " . config('app.dataMallToken'),
            'Content-Type' => 'application/json',

        ])
            ->post(config('app.dataMallUrl') . 'data/', [
                "type" => $network,
                "mobile_number" => $phone,
                "plan" => $plan_id,
            ]);
        return $response->json();
    }

public function gtechPortal($network_id, $phone, $plan_id)
    {
        $response = Http::withHeaders([
            'Authorization' => "Token " . config('app.gtechToken'),
            'Content-Type' => 'application/json',

        ])
            ->post(config('app.gtechUrl') . 'data/', [
                "network" => $network_id,
                "mobile_number" => $phone,
                "plan" => $plan_id,
                "Ported_number" => true
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
    
    // CONVERT AIRTIME TO CASH

    public function GenerateOTP($phone_number)
    {
        $body = '{
            "phone_number": "' . $phone_number . '",
            "send": "code",
            "connection": "sms",
            "client_id": "WO5BbTyEWLSFvFPw5TsYoioTQqcq8Mq3"
        }';

        // \Log::info($body);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, 'https://auth.mtnonline.com/passwordless/start');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $headers = array(
            "Content-Type: application/json",
            "host:auth.mtnonline.com",
            "user-agent: okhttp/4.10.0"
        );

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_POST, 1);

        $resp = curl_exec($ch);

        \Log::info($resp);
        // echo $resp;

        return $resp;


    }

    public function ValidateOTP($otp, $phone_number)
    {
        $body = '{
            "username": "' . $phone_number . '",
            "otp": "' . $otp . '",
            "audience": "NextGenAPI",
            "scope": "openid profile email offline_access",
            "client_id": "WO5BbTyEWLSFvFPw5TsYoioTQqcq8Mq3",
            "realm": "sms",
            "grant_type": "http://auth0.com/oauth/grant-type/passwordless/otp"
        }';


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, 'https://auth.mtnonline.com/oauth/token');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $headers = array(
            "Content-Type: application/json",
            "host:auth.mtnonline.com",
            "user-agent: okhttp/4.10.0"
        );

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_POST, 1);

        $resp = curl_exec($ch);


        return $resp;
    }

    public function generateMtnOtp($primary_number)
    {
        \Log::info($this->addFullCountryCode($primary_number));
        $gen_otp = $this->GenerateOTP($this->addFullCountryCode($primary_number));
        return $gen_otp;
    }

    public function checkBalance($accessToken, $primary_number)
    {
        $authToken =  $accessToken;

        //MTN DIRECT
        $body = json_encode(array(
            "primaryMsisdn" => $primary_number,
        ));

        $ch1 = curl_init();
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch1, CURLOPT_URL, 'https://mtn-dxl-customer-balance-plans.mymtnnxgeaprod.mtnnigeria.net/v1/customer/customerBalances_new');
        curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch1, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch1, CURLOPT_FORBID_REUSE, TRUE);
        curl_setopt($ch1, CURLOPT_FRESH_CONNECT, TRUE);

         //MTN DIRECT
         $headers = array(
            "Content-Type: application/json",
            "authorization:Bearer " . $authToken . "",
            "channel:MTNAPPNXG",
            "host:mtn-dxl-customer-balance-plans.mymtnnxgeaprod.mtnnigeria.net",
            "user-agent: Mozilla/5.0 (Linux; Android 12; Redmi Note 10 Pro) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.6099.144 Mobile Safari/537.36",
        );

        curl_setopt($ch1, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch1, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch1, CURLOPT_POST, 1);

        $resp = curl_exec($ch1);
        curl_close($ch1);


        $response = json_decode($resp, true);
        return $response;
    }


    public function convertA2Cash($otp, $primary_number, $pin, $amount)
    {
        $otp_response = $this->ValidateOTP($otp, $this->addFullCountryCode($primary_number));
        $access_response = json_decode($otp_response, true);

        \Log::info($access_response);
        if (isset($access_response['error']) && $access_response['error'] == "invalid_grant") {
            return $access_response;

        } else {
            $receiver_number = Setting::find(27)->value;
            $primary_msisdn = $this->addCountryCode($primary_number);

            $previous_balance = $this->checkBalance($access_response['access_token'], $primary_msisdn);
            $old_balance = $previous_balance['data'][0]['balance'][0]['balanceDetail']['activeValue'];


            $payload = array(
                "primaryMsisdn" => $primary_msisdn,
                "receiverMsisdn" => $receiver_number,
                "type" => "airtime",
                "targetSystem" => "COMVIVA",
                "agentId" => "MTNAPPNXG",
                "pin" => $pin,
                "productCode" => "603",
                "transferAmount" => $amount,
                "callbackUrl" => "https://#"
            );

            \Log::info(print_r($payload, true));

            $ch1 = curl_init();
            curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch1, CURLOPT_URL, 'https://mtn-dxl-transfer-airtime.mymtnnxgeaprod.mtnnigeria.net/v1/transfer/airtimeShare');
            curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch1, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch1, CURLOPT_FORBID_REUSE, TRUE);
            curl_setopt($ch1, CURLOPT_FRESH_CONNECT, TRUE);

            //MTN DIRECT
            $headers = array(
                "Content-Type: application/json",
                "authorization:Bearer " . $access_response['access_token'] . "",
                "channel:MTNAPPNXG",
                "host:mtn-dxl-transfer-airtime.mymtnnxgeaprod.mtnnigeria.net",
                "user-agent: okhttp/4.10.0",
            );

            curl_setopt($ch1, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch1, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch1, CURLOPT_POST, 1);

            $resp = curl_exec($ch1);
            curl_close($ch1);

            \Log::info($resp);
            $response = json_decode($resp, true);

            $latest_balance = $this->checkBalance($access_response['access_token'], $primary_msisdn);

            $new_balance = $latest_balance['data'][0]['balance'][0]['balanceDetail']['activeValue'];

            $balance_arr = ["old_balance" => $old_balance, "new_balance" => $new_balance];

            $final_response = array_merge($response, $balance_arr);

            \Log::info("final response");
            \Log::info($final_response);

            return $final_response;

        }

    }
    
    
    
    public function wisperPortal($network, $phone, $plan_id)
    {
        $callbackUrl = "https://swiftlinkng.com/wisperWebhook";

        $response = Http::withHeaders([
            'x-api-key' => config('app.wisperKey'),
            'Content-Type' => 'application/json',

        ])
            ->post(config('app.wisperUrl') . "/buy/?callback=$callbackUrl", [
                "network" => $network,
                "plan_id" => $plan_id,
                "phone_number" => $phone,
            ]);
        return $response->json();
    }
    
    
    
    
    public function integrityPortal($product_code, $phone, $ref)
    {

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('app.integrityPortalKey'),
            'Content-Type' => 'application/json',

        ])
            ->post(config('app.integrityPortalUrl'), [
                "product_code" => $product_code,
                "phone_number" => $phone,
                "action" => "vend",
                "user_reference" => $ref,
                "bypass_network" => "yes"
            ]);
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
    
    public function generatePalmpayCardPayment($reference,$email,$phone, $amount)
    {
          $private_key = config('app.palmPayPrivateKey');

        $payload = array(
            "amount" => $amount * 100,
            "notifyUrl"=>"https://swiftlinkng.com/palmpay-webhook",
            "orderId" => $reference,
            "title" => "Switflink Bank Card Payment",
            "description" => "Wallet funding",
            "currency" => "NGN",
            "callBackUrl" => "https://swiftlinkng.com/dashboard/callback",
            "customerInfo" => json_encode(array(
                "phone" => $phone,
                "email" => $email
            )),
            "goodsDetails" => json_encode([
                [
                    "goodsName" => "Wallet Funding"
                ]
            ]),
            "remark" => "remark",
            "productType" => "bank_card",
            "version" => "V2.0",
            "nonceStr" => $this->generateNonceStr(),
            "requestTime" => round(microtime(true) * 1000)
        );
        ksort($payload);

        $response =
            Http::acceptJson()->withHeaders([
                'Content-Type' => 'application/json',
                'countryCode' => 'NG',
                'Signature' => $this->generate_signature($payload, $private_key)
            ])->withToken(config('app.palmPayAppID'))->post(
                    config('app.palmPayUrl') . "api/v2/payment/merchant/createorder",
                    $payload
                );


        \Log::info("PALMPAY PAYLOAD");
        \Log::info(print_r($payload, true));
        
        \Log::info(print_r($response,true));



        return $response->json();
    }
    
    
    public function simServer($product_code, $amount, $phone, $ref)
    {

        $response = Http::withHeaders([
            'Authorization' => "Bearer " . config('app.simServerApiKey'),
            'Content-Type' => 'application/json',

        ])
            ->post(config('app.simServerBaseUrl'), [
                'product_code' =>  $product_code,
                'amount' => $amount,
                'phone_number' => $phone,
                'action' => 'vend',
                'user_reference' => $ref,
                "async" => false,
                "callback" => "https://swiftlinkng.com/sim-server-webhook"
            ]);
        return $response->json();
    }
    
    
    public function generatePalmpayBankTransfer($reference,$email,$phone, $amount)
    {
        $private_key = config('app.palmPayPrivateKey');

        $payload = array(
            "amount" => $amount * 100,
            "notifyUrl"=>"https://swiftlinkng.com/palmpay-webhook",
            "orderId" => $reference,
            "title" => "Switflink Bank Card Payment",
            "description" => "Purchase with bank transfer",
            "currency" => "NGN",
            "callBackUrl" => "https://swiftlinkng.com/dashboard/callback",
            "userId" => $email,
            "userMobileNo" => $phone,
             "goodsDetails" => json_encode([
                [
                    "goodsName" => "Purchase with bank transfer"
                ]
            ]),
            "productType" => "bank_transfer",
            "version" => "V2.0",
            "nonceStr" => $this->generateNonceStr(),
            "orderExpireTime" => 2700,
            "requestTime" => round(microtime(true) * 1000)
        );
        ksort($payload);

        $response =
            Http::acceptJson()->withHeaders([
                'Content-Type' => 'application/json',
                'countryCode' => 'NG',
                'Signature' => $this->generate_signature($payload, $private_key)
            ])->withToken(config('app.palmPayAppID'))->post(
                    config('app.palmPayUrl') . "api/v2/payment/merchant/createorder",
                    $payload
                );


        \Log::info("PALMPAY PAYLOAD");
        \Log::info(print_r($payload, true));



        return $response->json();
    }
    
    public function olaadePortal($network_id, $phone, $plan_id)
    {
        $response = Http::withHeaders([
            'Authorization' => "Token " . config('app.olaadeToken'),
            'Content-Type' => 'application/json',

        ])
            ->post(config('app.olaadeUrl') . 'data/', [
                "network" => $network_id,
                "mobile_number" => $phone,
                "plan" => $plan_id,
                "Ported_number" => true
            ]);
        return $response->json();
    }
    
    public function autoSyncPortalAirtime($request_ref,$phone,$product_id, $amount, $pin)
    {
        \Log::info("Payload for autosyncPortal Airtime");
        
        $payload = array(
            "request_ref" => $request_ref,
                "phone" => $phone,
                "product_id" =>  $product_id,
                "amount" => $amount,
                "is_mtn_awuf" => false,
                "pin" => $pin,
                "webhook_url" => "https://swiftlinkng.com/autosync-webhook",
                "ported_no" => true
            
            );
            
        \Log::info($payload);
        
        $response = Http::withHeaders([
            'Authorization' => "Bearer " . config('app.autoSyncToken'),
            'Content-Type' => 'application/json',

        ])->timeout(60)->post(config('app.autoSyncUrl') . 'airtime', [
                "request_ref" => $request_ref,
                "phone" => $phone,
                "product_id" =>  $product_id,
                "amount" => $amount,
                "is_mtn_awuf" => false,
                "pin" => $pin,
                "webhook_url" => "https://swiftlinkng.com/autosync-webhook"
            ]);
            
        return $response->json();
    }
    
    public function vtuplugPortal($ref, $network_id, $phone, $plan_id)
    {
        $response = Http::withHeaders([
            'Authorization' => "Token " . config('app.vtuplugToken'),
            'Content-Type' => 'application/json',

        ])
            ->post(config('app.vtuplugUrl') . 'data/', [
                "network" => $network_id,
                "phone" => $phone,
                "data_plan" => $plan_id,
                "request-id" => $ref,
                "bypass" => false,
            ]);
        return $response->json();
    }
    
    public function naijasubPortal($network_id, $phone, $plan_id,$ref)
    {
        $response = Http::withHeaders([
            'Authorization' => "Token " . config('app.naijasubToken'),
            'Content-Type' => 'application/json',

        ])
            ->post(config('app.naijasubUrl') . 'data/', [
                'your_ref' => $ref,
                "network" => $network_id,
                "mobile_number" => $phone,
                "plan" => $plan_id,
                "Ported_number" => true
            ]);
        return $response->json();
    }
    
    public function amakasubPortal($network_id, $phone, $plan_id,$ref)
    {
        
        $response = Http::withHeaders([
            'x-contract-id' => config('app.amakasubContractId'),
            'Content-Type' => 'application/json',

        ])
            ->post(config('app.amakasubUrl') . 'vend-data/', [
                'reference' => $ref,
                "networkId" => $network_id,
                "phoneNumber" => $phone,
                "planId" => $plan_id,
            ]);
        return $response->json();
    }
    
    public function coolsubPortal($network_id, $phone, $plan_id,$ref)
    {
        $response = Http::withHeaders([
            'Authorization' => "Token " . config('app.coolsubToken'),
            'Content-Type' => 'application/json',

        ])
            ->post(config('app.coolsubUrl') . 'data/', [
                'customer_ref' => $ref,
                "network" => $network_id,
                "mobile_number" => $phone,
                "plan" => $plan_id,
                "Ported_number" => true
            ]);
        return $response->json();
    }
    
    public function zoeDataPortal($product_code, $phone, $ref)
    {
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('app.zoeDataKey'),
            'Content-Type' => 'application/json',

        ])
            ->post(config('app.zoeDataUrl'), [
                "product_code" => $product_code,
                "phone_number" => $phone,
                "action" => "vend",
                "user_reference" => $ref,
                "async" => false,
                "callback" => "https://swiftlinkng.com/zoe-data-webhook"
            ]);
        return $response->json();
    }
    
    public function afriPortal($network_id, $phone, $plan_id,$ref)
    {
        $response = Http::withHeaders([
            'Authorization' => "Token " . config('app.arifToken'),
            'Content-Type' => 'application/json',

        ])
            ->post(config('app.arifUrl') . 'data/', [
                'customer_ref' => $ref,
                "network" => $network_id,
                "mobile_number" => $phone,
                "plan" => $plan_id,
                "Ported_number" => true
            ]);
        return $response->json();
    }
    
}
