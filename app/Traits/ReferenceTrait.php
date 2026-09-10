<?php

namespace App\Traits;

use App\Models\Order;
use App\Providers\PhoneNumberValidatorClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait ReferenceTrait
{

    /**
     * @param Request $request
     * @return $this|false|string
     */


    public function referenceCode()
    {
        $bytes = openssl_random_pseudo_bytes(5, $cstrong);
        $code   = bin2hex($bytes);

        //\Log::info(Auth::user->id)
        // return strtoupper(uniqid('OR'));
        //  return $unique_id = time() . mt_rand() . $userid;
        // get the timestamp + last order + random int + user id;
        //$last_order = Order::orderByDesc('id')->first('id');
        //$code = time()+$last_order->id+random_int(100000, 999999)+Auth::user()->id;
        //$code = uniqid();
        return $code;
        // do {
        //     $code = random_int(100000, 999999);
        // } while (Order::where("ref", "=", $code)->first());
        // return $code;
    }

    public function vtPassreferenceCode()
    {
        // YYYYMMDDHHII 
        date_default_timezone_set('Africa/Lagos');
        return  date('YmdHi') . $this->referenceCode();
    }

    public function ringoReferenceCode()
    {
        date_default_timezone_set('Africa/Lagos');
        return "RNG". date('YHi') . $this->referenceCode();
    }

    public function jonetReferenceCode()
    {
        $rnd = random_int(100000, 999999);
        date_default_timezone_set('Africa/Lagos');
        return  date('YmdHi') . $rnd;
    }

    public function autoPilotReferenceCode()
    {
        $rnd = random_int(100000, 999999);
        date_default_timezone_set('Africa/Lagos');
        return  date('YmdHi') . $rnd. $this->referenceCode();
    }

    public function getUserLevel($content, $level)
    {
    
        if ($level == 0) {
            return $content->amount1;
        } else if ($level == 1) {
            return $content->amount2;
        } else if ($level == 2) {
            return $content->amount3;
        } else if ($level == 3) {
            return $content->amount4;
        }
    }

    public function pluckAmountByUserLevel($level)
    {
        if ($level == 0) {
            return 'amount1';
        } else if ($level == 1) {
            return 'amount2';
        } else if ($level == 2) {
            return 'amount3';
        } else if ($level == 3) {
            return 'amount4';
        }
    }

    public function translateLevel($userlevel)
    {
        if ($userlevel == 0) {
            return "Normal";
        } else if ($userlevel == 1) {
            return "Agent";
        } else if ($userlevel == 2) {
            return "Whatsapp";
        }
        if ($userlevel == 3) {
            return "API";
        }
    }
    
    public function getBucketTitle($bucket_id)
    {
        switch($bucket_id){
            case 1:
                $wallet_col = "mtn_sme_wallet";
                break;

            case 2:
                $wallet_col = "airtel_eds_wallet";
                break;

            case 3:
                $wallet_col = "glo_cg_wallet";
                break;

            case 4:
                $wallet_col = "nmobile_cg_wallet";
                break;

            case 5:
                $wallet_col = "mtn_smart_wallet";
                break;

            case 6:
                $wallet_col = "airtel_awoof_wallet";
                break;

            case 7:
                $wallet_col = "glo_awoof_wallet";
                break;

        }

        return $wallet_col;

    }

    public function translateSMEPlugAirtimeNetwork($network)
    {
        if ($network == "MTN") {
            return 1;
        } else if ($network == "GLO") {
            return 4;
        } else if ($network == "Airtel") {
            return 2;
        }
        if ($network == "9mobile") {
            return 3;
        }
    }


    public function isValidPhoneProvider($network, $phone)
    {

        $ValidNetwork =  new PhoneNumberValidatorClass($phone);
        $result = false;
        if (!is_numeric($phone)) {
            return false;
        }

        if ($network == 'mtn') {
            $result = $ValidNetwork->isMtn();
        }
        if ($network == 'airtel') {
            $result = $ValidNetwork->isAirtel();
        }
        if ($network == 'glo') {
            $result = $ValidNetwork->isGlo();
        }
        if ($network == '9mobile') {
            $result = $ValidNetwork->is9mobile();
        }

        return $result;
    }


    public function subplan($userlevel, $products)
    {
        $result = null;

        $productCollection = collect(json_decode($products));
        $result =
            $productCollection
                ->map(
                    function ($item, $key) use ($userlevel) {
                        // $item = $item->first(); //as item is a collection of models
                        $new = [];
                        $new['plan']                 = $item->plan;
                        $new['amount']          = $this->getUserLevel($item, $userlevel);
                        return $new;
                    }
                );



        return $result;
    }
}
