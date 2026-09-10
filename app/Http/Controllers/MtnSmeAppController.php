<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use App\Traits\IntegrationsTrait;
use App\Traits\MyCrypto;


class MtnSmeAppController extends Controller
{
    use IntegrationsTrait;
    use MyCrypto;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware(['auth', 'verified']);
    }


    public function automationLoginView(Request $request)
    {
        return view("mtn_sme_app.mtn_login_app");
    }

    public function generateMtnOtp(Request $request)
    {
        $phone_number = $request->phone_number;
        $firstName = $request->first_name;
        try {
            $otp_result = $this->GenerateOTP($phone_number);

            \Log::info("otp_result");
            \Log::info($otp_result);

        } catch (\Throwable $th) {
            $otp_result = "";
        }

        return view("mtn_sme_app.verify_otp", ['otp_result' => $otp_result, 'phone_number' => $phone_number]);
    }

    public function validateMtnOtp(Request $request)
    {
        $phone_number = $request->phone_number;
        $otp = $request->otp;
        $response = $this->ValidateOTP($otp, $phone_number);

        if ($response) {
            $responseData = json_decode($response, true);

            // Access the token
            $accessToken = $responseData['access_token'];
            $refreshToken = $responseData['refresh_token'];

            Setting::where('key', 'MTN_ACCESS_TOKEN')->update(['value' => $accessToken]);

            Setting::where('key', 'MTN_REFRESH_TOKEN')->update(['value' => $refreshToken]);

            Setting::where('key', 'MTN_APP_SPONSOR_NUM')->update(['value' => $phone_number]);


            return view("mtn_sme_app.mtn_success_login_app", ['response' => $response]);

        } else {
            return view("mtn_sme_app.mtn_failed_login_app", ['response' => "An error occurred"]);

        }
    }

    public function makeAccountActive()
    {
        // $result = $this->RefreshToken();

        try {
           $result1 = $this->RefreshToken();
        } catch (\Throwable $th) {
            $result2 = "Token not retrieved";
        }



        // \Log::info($result);
        // \Log::info($result2);

        return view("mtn_sme_app.account_active",['result' => isset($result1) ? $result1 : "No new token retrieved yet"]);
    }


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
            //"accept:application/json",
            //"accept-encoding: gzip",
            //"auth0-client: eyJuYW1lIjoicmVhY3QtbmF0aXZlLWF1dGgwIiwidmVyc2lvbiI6IjIuMTEuMCJ9",
            //"connection: Keep-Alive",
            //"content-length: 113",
            //"cookie: did=s%3Av0%3A4b71b8e0-ad26-11ed-891a-f7454db0a1ca.9kjZd8BGkLgzAbKNHYu9x8Cmaj0a43sgIXxtVCjP5vs; did_compat=s%3Av0%3A4b71b8e0-ad26-11ed-891a-f7454db0a1ca.9kjZd8BGkLgzAbKNHYu9x8Cmaj0a43sgIXxtVCjP5vs",
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


    public function RefreshToken()
    {
        $old_accessToken = Setting::where('key', 'MTN_ACCESS_TOKEN')->first();
        \Log::info("Checking Time");

        // \Log::info(time() - 1800);
        // \Log::info(strtotime($old_accessToken->updated_at));
        // $time_now = time() - 1800;
        // \Log::info(strtotime($old_accessToken->updated_at) <=$time_now);
        if (strtotime($old_accessToken->updated_at) <= (time() - 1800)) {
            $refreshToken = Setting::where('key', 'MTN_REFRESH_TOKEN')->first()->value;

            $body = '{
            "refresh_token":"' . $refreshToken . '",
            "scope":"openid profile email offline_access",
            "client_id":"WO5BbTyEWLSFvFPw5TsYoioTQqcq8Mq3",
            "grant_type":"refresh_token"
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

            $responseData = json_decode($resp, true);

            \Log::info($responseData);




            // Access the refresh token
            $accessToken = $responseData['access_token'];
           // $refreshToken = $responseData['id_token'];

            Setting::where('key', 'MTN_ACCESS_TOKEN')->update(['value' => $accessToken]);

            //Setting::where('key', 'MTN_REFRESH_TOKEN')->update(['value' => $refreshToken]);

            return $responseData;

        }

    }


}
