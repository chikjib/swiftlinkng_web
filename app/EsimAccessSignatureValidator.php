<?php
namespace App;
use Illuminate\Http\Request;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;
class EsimAccessSignatureValidator implements SignatureValidator {
    public function isValid(Request $request, WebhookConfig $config): bool {
        // eSIM Access documents sender IPs, not an inbound HMAC signature.
        // Request::ip honors only explicitly configured trusted proxies.
        return strlen($request->getContent())<=65536 && in_array($request->ip(),config('esim.webhook_ips',[]),true);
    }
}
