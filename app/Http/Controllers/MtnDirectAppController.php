<?php

namespace App\Http\Controllers;

use App\Models\MtnLogin;
use App\Models\Setting;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Traits\IntegrationsTrait;


class MtnDirectAppController extends Controller
{
    use IntegrationsTrait;
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
        return view("mtn_direct_app.mtn_login_app");
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

        return view("mtn_direct_app.verify_otp", ['otp_result' => $otp_result, 'phone_number' => $phone_number]);
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
            $expires_in = $responseData['expires_in'];

            if(MtnLogin::where('phone_number',$phone_number)->exists()){
                MtnLogin::where('status',1)->update(['mtn_access_token' => $accessToken, 'mtn_refresh_token' => $refreshToken, 'phone_number' => $phone_number, "expires_in" => $expires_in]);
            }else {
                $login_data = MtnLogin::create([
                    "phone_number" => $phone_number,
                    "mtn_access_token" => $accessToken,
                    "mtn_refresh_token" => $refreshToken,
                    "expires_in" => $expires_in
                ]);
                // \Log::info($login_data->id);

                \DB::table('mtn_balance')->insert([
                    "mtn_login_id" => $login_data->id,
                    "created_at" => now(),
                    "updated_at" => now()
                ]);
            }


            return view("mtn_direct_app.mtn_success_login_app", ['response' => $response]);

        } else {
            return view("mtn_direct_app.mtn_failed_login_app", ['response' => "An error occurred"]);

        }
    }

    public function makeAccountActive()
    {
        $results = $this->RefreshToken();
    
        \Log::info($results);
    
        return view("mtn_direct_app.account_active", [
            'results' => $results
        ]);
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
        $mtn_logins = MtnLogin::all();
        
        $results = [];

        foreach($mtn_logins as $mtn_login){
            try{
                // $old_accessToken = $mtn_login->mtn_access_token;
                $refreshToken = $mtn_login->mtn_refresh_token;
    
                // \Log::info($mtn_login->updated_at);
    
                $issuedAt = Carbon::parse($mtn_login->updated_at);
                // $expiresIn = $mtn_login->expires_in - 300; // in seconds
                $expiresIn = 1800; // in seconds
    
                \Log::info($expiresIn);
    
                if (Carbon::now()->greaterThanOrEqualTo($issuedAt->addSeconds($expiresIn))) {
    
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
    
                    \Log::info("MTN REFRESH DONE");
    
                    \Log::info(is_null($responseData));
                    
                    if (!empty($responseData) && isset($responseData['access_token'])) {
                        $accessToken = $responseData['access_token'];
                        $expires_in = $responseData['expires_in'];
        
                        MtnLogin::where('phone_number', $mtn_login->phone_number)
                            ->update([
                                'mtn_access_token' => $accessToken,
                                'expires_in' => $expires_in
                            ]);
        
                        $results[$mtn_login->phone_number] = [
                            'status' => 'success',
                            'access_token' => $accessToken
                        ];
                    } else {
                        $results[$mtn_login->phone_number] = [
                            'status' => 'failed',
                            'reason' => 'No access token returned'
                        ];
                    }
                
                    
                }
                
            } catch (\Throwable $e) {
            // Catch any unexpected errors and move on
            $results[$mtn_login->phone_number] = [
                'status' => 'error',
                'reason' => $e->getMessage()
            ];
        }

        }
        
        return $results;

    }




}
