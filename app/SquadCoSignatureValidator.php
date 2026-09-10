<?php

namespace App;

use Illuminate\Http\Request;
use Spatie\WebhookClient\Exceptions\InvalidConfig;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

class SquadCoSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $signature = $request->header($config->signatureHeaderName);
        $signingSecret = $config->signingSecret;

        if (!$signature || empty($signingSecret)) {
            // return false;
            $data = array(
                "response_code" => 400,
                "transaction_reference" => $request->transaction_reference,
                "response_description" => "Validation failure",
            );
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($data);
            exit;
        }

        $computedSignature = hash_hmac('sha512', $request->getContent(), $signingSecret);
        $val = hash_equals($signature, $computedSignature);


        if (!$val) {
            $data = array(
                "response_code" => 400,
                "transaction_reference" => $request->transaction_reference,
                "response_description" => "Validation failure",
            );
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($data);
            exit;
        } else {
            return $val;
        }
    }
}
