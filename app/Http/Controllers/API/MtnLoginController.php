<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\MtnLoginResource;
use App\Models\MtnLogin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController as BaseController;

class MtnLoginController extends BaseController
{
    //
    public function load_mtn_logins(Request $request)
    {
        $mtn_logins =  MtnLogin::all();
        foreach($mtn_logins as $data) {
            $balance = $this->checkBalance($data->mtn_access_token, $data->phone_number);
         //   \Log::info($balance);
            
            if(!is_null($balance) && isset($balance['data'][0]['balance'])){
                $new_balance = $balance['data'][0]['balance'][0]['balanceDetail']['activeValue'];

                \DB::table('mtn_balance')->where(['mtn_login_id' => $data->id])->update(['balance' => $new_balance]);
            }

        
        }
        $logins = MtnLoginResource::collection($mtn_logins);

        return $logins;
    }

    public function checkBalance($accessToken, $phone_number)
    {
        $authToken =  $accessToken;
        $sponsorNum = $phone_number;

        //MTN DIRECT

        $body = json_encode(array(
            "primaryMsisdn" => str_replace("+", "",$sponsorNum),
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
            "user-agent: okhttp/4.10.0",
        );

        curl_setopt($ch1, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch1, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch1, CURLOPT_POST, 1);

        $resp = curl_exec($ch1);
        curl_close($ch1);


        $response = json_decode($resp, true);
        return $response;
    }



    public function show($id)
    {
        $logins = MtnLogin::find($id);

        if (is_null($logins)) {
            return $this->sendError('Mtn Login not found.');
        }

        return $this->sendResponse(new MtnLoginResource($logins), 'Logins retrieved successfully.');
    }

   public function activate_number(Request $request, $id)
   {
        $logins = MtnLogin::find($id);
        $other_active_logins = MtnLogin::where('status',1)->first();

        if(MtnLogin::where('status',1)->exists()){
            $other_active_logins->status = 0;
            $other_active_logins->save();
        }

        $logins->status = 1;
        $logins->save();



        return $this->sendResponse([], 'Activated successfully.');
   }


    public function destroy($id)
    {
        $logins =  MtnLogin::findOrFail($id);
        $balances =  \DB::table('mtn_balance')->where(['mtn_login_id' => $logins->id])->delete();
        $logins->delete();

        return $this->sendResponse([], 'Logins deleted successfully.');
    }
}
