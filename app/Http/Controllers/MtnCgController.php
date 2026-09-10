<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use App\Traits\IntegrationsTrait;
use App\Traits\MyCrypto;


class MtnCgController extends Controller
{
    use IntegrationsTrait;
    use MyCrypto;
    
    
    public function keepTokenAlive(Request $request)
    {
        $data = $this->KeepCookieAlive();

        return view("mtn_cg_website.account_active_cg",['final_cookies' => $data]);
    }

    public function automationLoginView(Request $request)
    {
        return view("mtn_cg_website.mtn_login_cg");
    }

    public function validateMtnProfile(Request $request)
    {
        $phone_number = $request->phone_number;
        try {
            $firstName = $this->ValidateProfile($phone_number);
        } catch (\Throwable $th) {
            $firstName = "";
        }


        return view("mtn_cg_website.otp_cg", ['first_name' => $firstName, 'phone_number' => $phone_number]);
    }
    public function generateMtnOtp(Request $request)
    {
        $phone_number = $request->phone_number;
        $firstName = $request->first_name;
        try {
            $otp_result = $this->GenerateOTP($phone_number, $firstName);

        } catch (\Throwable $th) {
            $otp_result = "";
        }

        return view("mtn_cg_website.verify_otp_cg", ['otp_result' => $otp_result, 'phone_number' => $phone_number]);
    }

    public function validateMtnOtp(Request $request)
    {
        $phone_number = $request->phone_number;
        $otp = $request->otp;
        $response = $this->ValidateOTP($otp, $phone_number);
        if ($response) {
            // $mtn_token = Storage::disk('local')->get('mtntokenweb.txt');
            // $mtn_num = Storage::disk('local')->get('mtnnum.txt');


            // $fp = fopen(storage_path('app/mtntokenweb.txt'), 'w');
            // fwrite($fp, $response);
            // fclose($fp);
            Setting::where('key', 'MTN_TOKEN_CG')->update(['value' => $response]);
            ///saving the phone number
            // $fp = fopen(storage_path('app/mtnnum.txt'), 'w');
            // fwrite($fp, $phone_number);
            // fclose($fp);

            Setting::where('key', 'MTN_SPONSOR_NUMBER_CG')->update(['value' => $phone_number]);


            return view("mtn_cg_website.mtn_success_login_cg", ['response' => $response]);

        } else {
            return view("mtn_cg_website.mtn_failed_login_cg", ['response' => "An error occurred"]);

        }
    }

    public function ValidateProfile($phone_number)
    {
        \Log::info($phone_number);
        $body = '{"msisdn":"' . $phone_number . '"}';

        $inputKey = "websmart@2020";

        $body = MyCrypto::encrypt($body, $inputKey);
        $newbody = '{"data": "' . $body . '"}';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, 'https://mymtn.com.ng/app/api/validateprofile');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $headers = array(
            "Content-Type: application/json",
            "connection:Keep-Alive",
            "host:https://mymtn.com.ng",
            "Referer:https://mymtn.com.ng/login",
            "Sec-Ch-Ua: \"Google Chrome\";v=\"117\", \"Not;A=Brand\";v=\"8\", \"Chromium\";v=\"117\"",
            "Sec-Ch-Ua-Mobile:?0",
            "Sec-Ch-Ua-Platform: \"Windows\"",
            "User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36",
        );

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $newbody);
        curl_setopt($ch, CURLOPT_POST, 1);

        $resp = curl_exec($ch);
        curl_close($ch);
        //echo $resp;
        $resp = MyCrypto::decrypt($resp, $inputKey);

        $data = json_decode($resp, true);

        // Access the firstName
        if ($data['firstName']) {
            $firstName = $data['firstName'];
            return $firstName;
        } else {
            return $resp;
        }

    }

    public function GenerateOTP($phone_number, $firstName)
    {
        $body = '{"msisdn":"' . $phone_number . '","emailFlag":"false","linkAccountFlag":"false","userLoginType":"gsm","eMailId":"myMTN@mtn.com","supp_id":"","ccmailid":"","firstName":"' . $firstName . '","otpRequestAction":"log-in","requestName":"otp"}';
        $inputKey = "websmart@2020";

        $body = MyCrypto::encrypt($body, $inputKey);
        $newbody = '{"data": "' . $body . '"}';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, 'https://mymtn.com.ng/app/api/generateotp');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $headers = array(
            "Content-Type: application/json",
            "connection:Keep-Alive",
            "host:https://mymtn.com.ng",
            "Referer:https://mymtn.com.ng/login",
            "Sec-Ch-Ua: \"Google Chrome\";v=\"117\", \"Not;A=Brand\";v=\"8\", \"Chromium\";v=\"117\"",
            "Sec-Ch-Ua-Mobile:?0",
            "Sec-Ch-Ua-Platform: \"Windows\"",
            "User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36",
        );

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $newbody);
        curl_setopt($ch, CURLOPT_POST, 1);

        $resp = curl_exec($ch);
        \Log::info($resp);
        //echo $resp;
        $resp = MyCrypto::decrypt($resp, $inputKey);
        //$data = json_decode($resp, true);

        return $resp;


    }

    public function ValidateOTP($otp, $phone_number)
    {
        $body = '{"otp":"' . $otp . '","msisdn":"' . $phone_number . '","userLoginType":"gsm"}';
        $inputKey = "websmart@2020";

        $body = MyCrypto::encrypt($body, $inputKey);
        $newbody = '{"data": "' . $body . '"}';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, 'https://mymtn.com.ng/app/api/validateOTP');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $headers = array(
            "Content-Type: application/json",
            "connection:Keep-Alive",
            "host:https://mymtn.com.ng",
            "Referer:https://mymtn.com.ng/login",
            "Sec-Ch-Ua: \"Google Chrome\";v=\"117\", \"Not;A=Brand\";v=\"8\", \"Chromium\";v=\"117\"",
            "Sec-Ch-Ua-Mobile:?0",
            "Sec-Ch-Ua-Platform: \"Windows\"",
            "User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36",
        );

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $newbody);
        curl_setopt($ch, CURLOPT_POST, 1);

        $resp = curl_exec($ch);
        //echo $resp;
        $resp = MyCrypto::decrypt($resp, $inputKey);
        //$data = json_decode($resp, true);

        return $resp;
    }

    public function makeAccountActive()
    {
        $phone_number = Setting::where('key', 'MTN_SPONSOR_NUMBER_CG')->first()->value;
        \Log::info($phone_number);
        // $phone_number = Storage::disk('local')->get('mtnnum.txt');

        try {
            $result = $this->getBalanceWeb($phone_number);
        } catch (\Throwable $th) {
            $result = 0;
        }

        \Log::info($result);

        // if ($result == 0) {
        //     $subcategory = SubCategory::find(1);
        //     $subcategory->description = "AUTOSYNCAWUF";
        //     $subcategory->save();
        // }

        try {
            $result2 = $this->getSmeBundles();
        } catch (\Throwable $th) {
            $result2 = "Couldn't reach MTNNG, probably token has expired or you have been logged out. Please try again or re-login again";
        }

        if ($result == 0) {
            $result2 = "Couldn't reach MTNNG, probably token has expired or you have been logged out. Please try again or re-login again";
        }

        // \Log::info($result);
        // \Log::info($result2);

        return view("mtn_cg_website.account_active_cg", ['balance' => $result, 'smebundles' => $result2]);
    }

    public function getBalanceWeb($num)
    {
        $jsonResponse = Setting::where('key', 'MTN_TOKEN_CG')->first()->value;
        //echo $num;
        $responseData = json_decode($jsonResponse, true);

        $authToken = $responseData['token'];

        $body = '{"msisdn":"' . $num . '"}';
        $inputKey = "websmart@2020";
        //$authToken= MyCrypto::encrypt($authToken, $inputKey);
        $body = MyCrypto::encrypt($body, $inputKey);
        $newbody = '{"data": "' . $body . '"}';
        //echo $authToken;
        //$newbody='{"data":"U2FsdGVkX19nTfarWTzluKVVrYg2x7sOfOFk6roCbQPYC8pCoRYSCVmRLdePpOhc"}';
        //echo $body;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, 'https://mymtn.com.ng/app/api/newbalance');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $headers = array(
            "Content-Type: application/json",
            "Authorization:Bearer " . $authToken . "",
            "connection:Keep-Alive",
            "Referer:https://mymtn.com.ng/buybundles/smebundles",
            "Sec-Ch-Ua: \"Google Chrome\";v=\"117\", \"Not;A=Brand\";v=\"8\", \"Chromium\";v=\"117\"",
            "Sec-Ch-Ua-Mobile:?0",
            "Sec-Ch-Ua-Platform: \"Windows\"",
            "User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36",
            "Adrum: isAjax:true",
            "x-country-code:nga"
        );

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $newbody);
        curl_setopt($ch, CURLOPT_POST, 1);

        $resp = curl_exec($ch);
        //echo $resp;
        /*$dataArray = json_decode($resp, true);
        $smeDataWallet = $dataArray['primaryNumber'][0]['2348167458555']['balance'][1]['wallets'][0];

        // Access the value and unit from the SME Data wallet.
        $value = $smeDataWallet['amount']['value']; // This will give you "24.05"
        $unit = $smeDataWallet['amount']['unit'];
        */
        //return $value." GB";
        $decryptedText = MyCrypto::decrypt($resp, $inputKey);
        // $decodedData = json_decode($decryptedText, true);
        //echo $decryptedText;
        // Decode the JSON data
        //echo $resp;
        $data = json_decode($decryptedText, true);

        // \Log::info("GET BALANCE WEB");
        // \Log::info($data);

        // Find the balance for daid 86
        $daidToFind = "148";
        $balance = null;
        if (is_array($data['da_result']) || is_object($data['da_result'])) {
            \Log::info($data['da_result']);

            foreach ($data['da_result'] as $da) {
                if ($da['daid'] === $daidToFind) {
                    $balance = $da['balance'];
                    break;
                }
            }
        } else {

        }

        if ($balance !== null) {
            $balanceid = $balance;
            $result = $this->divideAndRoundUp($balance, 52428.7996) . "GB";

        } else {
            $balanceid = "Null";
            $result = "Null";
        }

        //return "Balance of DAID 86: $daid86Balance <br/>";

        //return $result;
        // return  $decryptedText;
        $balance = !is_null($result) ? $result : 0;

        return $balance;
    }

    public function getSmeBundles()
    {

        $jsonResponse = Setting::where('key', 'MTN_TOKEN_CG')->first()->value;
        $responseData = json_decode($jsonResponse, true);

        $authToken = $responseData['token'];

        $ch1 = curl_init();
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch1, CURLOPT_URL, 'https://mymtn.com.ng/buybundles/smebundles');
        curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch1, CURLOPT_SSL_VERIFYHOST, FALSE);
        $headers = array(
            "Content-Type: text/html",
            "Authorization:Bearer " . $authToken . "",
            "connection:Keep-Alive",
            "cookies: session=" . $authToken . ";token=" . $authToken . "",
            "host:mymtn.com.ng",
            "Referer:https://mymtn.com.ng/buybundles/smebundles",
            "Sec-Ch-Ua: \"Google Chrome\";v=\"117\", \"Not;A=Brand\";v=\"8\", \"Chromium\";v=\"117\"",
            "Sec-Ch-Ua-Mobile:?0",
            "Sec-Ch-Ua-Platform: \"Windows\"",
            "User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36",
            "Adrum: isAjax:true",
            "x-country-code:nga"
        );

        curl_setopt($ch1, CURLOPT_HTTPHEADER, $headers);
        //curl_setopt($ch1, CURLOPT_POSTFIELDS,$newbody);
        //curl_setopt($ch1, CURLOPT_POST,1);

        $resp = curl_exec($ch1);
        \Log::info($resp);
        return $resp;
    }

}
