<?php

namespace App;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Exceptions\InvalidConfig;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

class ZoeDataSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        return true;

        // $signature = $request->header($config->signatureHeaderName);

        // if (!$signature) {
        //     return false;
        // }

        // $signingSecret = $config->signingSecret;

        // if (empty($signingSecret)) {
        //     throw InvalidConfig::signingSecretNotSet();
        // }

        // $computedSignature = hash_hmac('sha512', $request->getContent(), $signingSecret);

        // return hash_equals($signature, $computedSignature);
    }
}
