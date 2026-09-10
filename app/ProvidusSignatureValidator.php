<?php

namespace App;

use Illuminate\Http\Request;
use Lcobucci\JWT\Signer\Rsa\Sha512;
use Spatie\WebhookClient\WebhookConfig;
use Spatie\WebhookClient\Exceptions\InvalidConfig;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;

class ProvidusSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        // $signature = $request->header($config->signatureHeaderName);

        // if (!$signature) {


        //     $data = array(
        //         "requestSuccessful" => true,
        //         "sessionId" => $request->sessionId,
        //         "responseMessage" => "success",
        //         "responseCode" => "00",
        //     );

        //     // $data = array(
        //     //     "requestSuccessful" => true,
        //     //     "sessionId" => $request->sessionId,
        //     //     "responseMessage" => "rejected transaction",
        //     //     "responseCode" => "02",
        //     // );
        //     header('Content-Type: application/json; charset=utf-8');

        //     echo json_encode($data);
        //     exit;
        //     // return false;

        // }

        // $signingSecret = $config->signingSecret;

        // if (empty($signingSecret)) {

        //     $data = array(
        //         "requestSuccessful" => true,
        //         "sessionId" => $request->sessionId,
        //         "responseMessage" => "rejected transaction",
        //         "responseCode" => "02",
        //     );
        //     header('Content-Type: application/json; charset=utf-8');

        //     echo json_encode($data);
        //     exit;
        //     //   throw InvalidConfig::signingSecretNotSet();
        // }


        // $computedSignature = $signingSecret;

        // //hash_hmac('sha512', "", $signingSecret);

        // $val =  hash_equals($signature, $computedSignature);

        // if (!$val) {
        //     $data = array(
        //         "requestSuccessful" => true,
        //         "sessionId" => $request->sessionId,
        //         "responseMessage" => "rejected transaction",
        //         "responseCode" => "02",
        //     );
        //     header('Content-Type: application/json; charset=utf-8');

        //     echo json_encode($data);
        //     exit;
        // } else {
        //     return $val;
        // }

        $signature = $request->header($config->signatureHeaderName);

        if (!$signature) {
            return false;
        }

        $signingSecret = $config->signingSecret;

        if (empty($signingSecret)) {
            throw InvalidConfig::signingSecretNotSet();
        }


        // $computedSignature = hash_hmac('sha512', $request->getContent(), $signingSecret);

        return hash_equals($signature, $signingSecret);
    }
}
