<?php

namespace App;

use App\Services\OpayDigitalWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;
use Throwable;

class OpayWalletWebhookValidator implements SignatureValidator
{
    public function __construct(private OpayDigitalWalletService $opay)
    {
    }

    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $merchantId = (string) $request->header('merchantId', '');
        $headerTransactionId = (string) $request->header('X-Opay-Tranid', '');
        $requestPayload = $request->all();

        $allowedMerchantIds = array_filter([
            (string) config('services.opay_wallet.branch_id'),
            (string) config('services.opay_wallet.root_merchant_id'),
        ]);

        try {
            $payload = isset($requestPayload['paramContent'])
                ? $this->opay->decryptWebhookEnvelope($requestPayload)
                : (is_array($request->input('data'))
                    ? $request->input('data')
                    : $requestPayload);
        } catch (Throwable $exception) {
            Log::warning('opay.webhook.decryption_failed', [
                'merchant_id_matches_configuration' => $merchantId !== ''
                    && in_array($merchantId, $allowedMerchantIds, true),
                'header_transaction_id' => $headerTransactionId,
                'envelope_fields' => array_keys($requestPayload),
                'error' => $exception->getMessage(),
            ]);
            return false;
        }

        $transactionPayload = is_array($payload['data'] ?? null)
            ? $payload['data']
            : $payload;
        if ((bool) config('services.opay_wallet.log_request_payload', false)) {
            Log::info('opay.webhook.decrypted_payload', [
                'headers' => [
                    'merchantId' => $merchantId,
                    'X-Opay-Tranid' => $headerTransactionId,
                    'Content-Type' => (string) $request->header('Content-Type', ''),
                ],
                'payload' => $payload,
            ]);
        }

        // Spatie stores request input after validation. Replace the encrypted
        // envelope so webhook_calls and the queued job receive only useful,
        // decrypted transaction data (and never clientAuthKey/sign).
        $request->replace($payload);
        $transactionId = (string) ($transactionPayload['transactionId'] ?? '');
        $status = strtoupper((string) ($transactionPayload['status'] ?? ''));
        $merchantIsValid = $merchantId !== ''
            && in_array($merchantId, $allowedMerchantIds, true);
        $transactionHeaderIsValid = $transactionId !== ''
            && $headerTransactionId !== ''
            && hash_equals($transactionId, $headerTransactionId);
        $statusIsValid = in_array($status, ['SUCCESS', 'FAIL', 'PENDING'], true);
        $isValid = $merchantIsValid
            && $transactionHeaderIsValid
            && $statusIsValid;

        if (!$isValid) {
            Log::warning('opay.webhook.validation_failed', [
                'merchant_id_present' => $merchantId !== '',
                'merchant_id_matches_configuration' => $merchantIsValid,
                'body_transaction_id' => $transactionId,
                'header_transaction_id' => $headerTransactionId,
                'transaction_ids_match' => $transactionHeaderIsValid,
                'status' => $status,
                'status_is_valid' => $statusIsValid,
                'payload_fields' => array_keys($transactionPayload),
            ]);
        }

        return $isValid;
    }
}
