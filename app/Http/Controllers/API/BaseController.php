<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Illuminate\Support\Facades\Auth;

class BaseController extends Controller
{


    public $token;
    public $user;

    function __construct()
    {
        // $this->user = Auth::user();

        $this->middleware(function ($request, $next) {
            $this->user = Auth::user(); // returns user
            return $next($request);
        });
    }

    public function sendResponse($result, $message)
    {
        $response = [
            'success' => true,
            'data'    => $result,
            'message' => $message,
        ];


        return response()->json($response, 200);
    }

    public function sendResponse2($ref, $custom_reference, $amount, $result, $message)
    {

        $response = [
            'success' => true,
            'transaction_reference' => $ref,
            'custom_reference' => $custom_reference,
            'amount' => $amount,
            'data'    => $result,
            'message' => $message,
        ];


        return response()->json($response, 200);
    }
    
     public function sendResponse3($ref, $custom_reference, $amount, $result, $message,$token)
    {

        $response = [
            'success' => true,
            'transaction_reference' => $ref,
            'custom_reference' => $custom_reference,
            'amount' => $amount,
            'data'    => $result,
            'token' => $token,
            'message' => $message,
        ];


        return response()->json($response, 200);
    }


    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($error, $errorMessages = [], $code = 404)
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];


        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }


        return response()->json($response, $code);
    }

     public function sendError2($ref, $custom_reference, $resp_msg, $error, $errorMessages = [], $code = 404)
    {
        $response = [
            'success' => false,
            'transaction_reference' => $ref,
            'custom_reference' => $custom_reference,
            'description' => $resp_msg,
            'message' => $error,
        ];


        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }


        return response()->json($response, $code);
    }
     public function sendError3($ref, $custom_reference, $resp_msg, $error, $errorMessages = [], $code = 404)
    {
        $response = [
            'success' => false,
            'transaction_reference' => $ref,
            'custom_reference' => $custom_reference,
            'description' => $resp_msg,
            'message' => $error,
        ];


        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }


        return response()->json($response, $code);
    }
}
