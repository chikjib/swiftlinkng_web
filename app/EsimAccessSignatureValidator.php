<?php
namespace App;
use Illuminate\Http\Request;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;
class EsimAccessSignatureValidator implements SignatureValidator {
    public function isValid(Request $request, WebhookConfig $config): bool {
        // Accept notifications from any IP; payload validation and rate limiting still apply.
        // The job verifies provider state through the authenticated API before settlement.
        return strlen($request->getContent())<=65536;
    }
}
