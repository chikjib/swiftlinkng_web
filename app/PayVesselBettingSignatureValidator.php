<?php
namespace App;
use Illuminate\Http\Request;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;
class PayVesselBettingSignatureValidator implements SignatureValidator {
    public function isValid(Request $request, WebhookConfig $config): bool {
        $secret=$config->signingSecret;
        // Support the documented literal header and its standard HTTP spelling.
        $signature=$request->header('HTTP_PAYVESSEL_HTTP_SIGNATURE') ?: $request->header('payvessel-http-signature');
        return strlen($request->getContent())<=65536 && $secret!=='' && is_string($signature)
            && in_array($request->ip(),config('betting.webhook_ips',[]),true)
            && hash_equals(hash_hmac('sha512',$request->getContent(),$secret),$signature);
    }
}
