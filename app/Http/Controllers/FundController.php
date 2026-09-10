<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class FundController extends Controller
{
    //
    public function index()
    {
        $pageTitle =  "Fund Account";

        return view(
            'fund.index',
            ['pageTitle' =>  $pageTitle]
        );
    }


    public function store(Request $request)
    {
        $data = [
            "tx_ref" => rand(),
            "amount" => $request->amount,
            "currency" => $request->currency,
            "redirect_url" => route('flutterwave-callback'),
            'customer' => [
                'email' => $request->email,
                'phonenumber' => $request->phone_no,
                'name' => $request->first_name . $request->last_name
            ],
        ];
        $url = "https://api.flutterwave.com/v3/payments";
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer Secret_key' //Secret key of your account 
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 200);
        curl_setopt($curl, CURLOPT_TIMEOUT, 200);
        $response_body = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $result = json_decode($response_body, true);
        if ($err) {
            throw new \Exception($err);
        }

        if (isset($result['status']) && $result['status'] == 'success') {
            if (isset($result['data']['link']) && $result['data']['link'] != ' ') {
                return Redirect::to($result['data']['link']);
            }
        }

        throw new \Exception('Your transaction could not processed.');
    }

    public function callback(Request $request)
    {
        $response = $request->all();
        if ($response['status'] == 'successful') {
            $status = "SUCCESS";
        } else {
            $status = "FAIL";
        }
        //Store the transaction as per your requirement
    }
}
