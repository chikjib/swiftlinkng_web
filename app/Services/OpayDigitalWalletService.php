<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OpayDigitalWalletService
{
    private const VERSION = 'V1.0.1';

    public function createWallet(array $parameters): array
    {
        $this->validateCreateWalletParameters($parameters);
        return $this->request('/api/v2/third/depositcode/generateStaticDepositCode', $parameters);
    }

    public function walletDetails(string $depositCode): array
    {
        return $this->request('/api/v2/third/depositcode/queryStaticDepositCodeInfo', [
            'opayMerchantId' => $this->branchId(),
            'depositCode' => $depositCode,
        ]);
    }

    public function walletBalance(string $depositCode): array
    {
        return $this->request('/api/v2/third/depositcode/queryWalletBalance', [
            'opayMerchantId' => $this->branchId(),
            'depositCode' => $depositCode,
        ]);
    }

    public function transactionHistory(array $filters = []): array
    {
        $payload = array_filter(array_merge([
            'merchantId' => config('services.opay_wallet.root_merchant_id'),
            'operator' => config('services.opay_wallet.operator'),
            'pageIndex' => 1,
            'pageSize' => 50,
        ], $filters), static fn ($value) => $value !== null && $value !== '');

        return $this->request('/api/v2/third/depositcode/queryStaticDepositCodeTransactionList', $payload);
    }

    public function decryptWebhookEnvelope(array $envelope): array
    {
        foreach (['paramContent', 'sign', 'timestamp', 'clientAuthKey'] as $field) {
            if (blank($envelope[$field] ?? null)) {
                throw new RuntimeException('OPay webhook envelope is missing '.$field.'.');
            }
        }

        $expectedClientAuthKey = (string) config('services.opay_wallet.client_auth_key');
        if ($expectedClientAuthKey === '' || !hash_equals(
            $expectedClientAuthKey,
            (string) $envelope['clientAuthKey']
        )) {
            throw new RuntimeException('OPay webhook clientAuthKey does not match configuration.');
        }

        $signature = base64_decode((string) $envelope['sign'], true);
        if ($signature === false || openssl_verify(
            (string) $envelope['paramContent'].(string) $envelope['timestamp'],
            $signature,
            $this->opayPublicKey(),
            OPENSSL_ALGO_SHA256
        ) !== 1) {
            throw new RuntimeException('OPay webhook RSA signature verification failed.');
        }

        $decrypted = $this->decryptInChunks(
            (string) $envelope['paramContent'],
            $this->merchantPrivateKey()
        );
        $payload = json_decode($decrypted, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($payload)) {
            throw new RuntimeException('OPay webhook decrypted to an invalid payload.');
        }

        return $payload;
    }

    public function branchId(): string
    {
        return (string) config('services.opay_wallet.branch_id');
    }

    private function request(string $path, array $parameters): array
    {
        $this->assertConfigured();
        $shouldLogPayloads = (bool) config(
            'services.opay_wallet.log_request_payload',
            false
        );
        $timestamp = (string) round(microtime(true) * 1000);
        $plainText = json_encode(
            $this->canonicalize($parameters),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
        $paramContent = $this->encryptInChunks($plainText, $this->opayPublicKey());
        $signature = $this->sign($paramContent.$timestamp, $this->merchantPrivateKey());
        $requestBody = [
            'paramContent' => $paramContent,
            'sign' => $signature,
        ];

        if ($shouldLogPayloads) {
            Log::info('opay.api.request_payload', [
                'path' => $path,
                'payload' => $this->canonicalize($parameters),
            ]);
        }

        $response = $this->http($timestamp)->post(
            rtrim((string) config('services.opay_wallet.base_url'), '/').$path,
            $requestBody
        );

        if (!$response->successful()) {
            throw new RuntimeException('OPay request failed with HTTP '.$response->status().'.');
        }

        $body = $response->json();
        if ($shouldLogPayloads) {
            Log::info('opay.api.response_payload', [
                'path' => $path,
                'http_status' => $response->status(),
                'payload' => is_array($body)
                    ? $body
                    : ['raw_body' => $response->body()],
            ]);
        }
        if (!is_array($body)) {
            throw new RuntimeException('OPay returned an invalid JSON response.');
        }

        if ((string) ($body['code'] ?? '') !== '00000') {
            $code = (string) ($body['code'] ?? 'UNKNOWN');
            $message = (string) ($body['message'] ?? 'OPay rejected the request.');
            Log::warning('opay.api.request_rejected', [
                'path' => $path,
                'http_status' => $response->status(),
                'opay_code' => $code,
                'opay_message' => $message,
                'request_fields' => array_keys($parameters),
                'ref_id_length' => strlen((string) ($parameters['refId'] ?? '')),
                'phone_length' => strlen((string) ($parameters['phone'] ?? '')),
                'has_email' => !blank($parameters['email'] ?? null),
                'account_type' => $parameters['accountType'] ?? null,
                'merchant_id_suffix' => substr((string) ($parameters['opayMerchantId'] ?? ''), -4),
            ]);
            throw new RuntimeException(
                'OPay rejected the request ('.$code.'): '.$message
            );
        }

        if (!isset($body['data'], $body['timestamp'], $body['sign'])) {
            throw new RuntimeException('OPay response is missing encrypted response fields.');
        }

        if (config('services.opay_wallet.verify_responses', true) && !$this->verifyResponse($body)) {
            throw new RuntimeException('OPay response signature verification failed.');
        }

        $decrypted = $this->decryptInChunks((string) $body['data'], $this->merchantPrivateKey());
        $data = json_decode($decrypted, true, 512, JSON_THROW_ON_ERROR);
        if ($shouldLogPayloads) {
            Log::info('opay.api.decrypted_response_payload', [
                'path' => $path,
                'payload' => is_array($data) ? $data : ['value' => $data],
            ]);
        }

        // OPay wraps the business response inside the decrypted payload's
        // own code/message/data envelope. Expose the inner data consistently
        // to controllers (for example, data.depositCode when creating a wallet).
        $businessData = is_array($data) && is_array($data['data'] ?? null)
            ? $data['data']
            : $data;

        return [
            'code' => is_array($data) && isset($data['code'])
                ? (string) $data['code']
                : (string) $body['code'],
            'message' => is_array($data) && isset($data['message'])
                ? (string) $data['message']
                : (string) ($body['message'] ?? 'SUCCESSFUL'),
            'data' => $businessData,
        ];
    }

    private function http(string $timestamp): PendingRequest
    {
        return Http::acceptJson()->asJson()->timeout(25)->retry(2, 300)->withHeaders([
            'clientAuthKey' => (string) config('services.opay_wallet.client_auth_key'),
            'version' => self::VERSION,
            'bodyFormat' => 'JSON',
            'timestamp' => $timestamp,
        ]);
    }

    private function validateCreateWalletParameters(array $parameters): void
    {
        foreach (['opayMerchantId', 'name', 'accountType'] as $field) {
            if (blank($parameters[$field] ?? null)) {
                throw new RuntimeException('Invalid OPay wallet request: '.$field.' is required.');
            }
        }

        $refId = (string) ($parameters['refId'] ?? '');
        if ($refId !== '' && !preg_match('/^[A-Za-z0-9]{1,15}$/', $refId)) {
            throw new RuntimeException(
                'Invalid OPay wallet request: refId must contain no more than 15 letters or numbers.'
            );
        }
        if ($refId === '' && blank($parameters['email'] ?? null) && blank($parameters['phone'] ?? null)) {
            throw new RuntimeException(
                'Invalid OPay wallet request: refId, email or phone is required.'
            );
        }
        if (!in_array((string) $parameters['accountType'], ['Merchant', 'User'], true)) {
            throw new RuntimeException(
                'Invalid OPay wallet request: accountType must be Merchant or User.'
            );
        }
        if (isset($parameters['sendPassWordFlag'])
            && !in_array((string) $parameters['sendPassWordFlag'], ['Y', 'N'], true)) {
            throw new RuntimeException(
                'Invalid OPay wallet request: sendPassWordFlag must be Y or N.'
            );
        }
    }

    private function verifyResponse(array $body): bool
    {
        $sign = (string) $body['sign'];
        unset($body['sign']);
        $body = $this->canonicalize($body);
        $parts = [];
        foreach ($body as $key => $value) {
            $parts[] = $key.'='.(is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_SLASHES));
        }
        return openssl_verify(implode('&', $parts), base64_decode($sign, true), $this->opayPublicKey(), OPENSSL_ALGO_SHA256) === 1;
    }

    private function sign(string $data, string $privateKey): string
    {
        $resource = openssl_pkey_get_private($privateKey);
        if (!$resource) {
            throw new RuntimeException(
                'OPAY_WALLET_MERCHANT_PRIVATE_KEY is not a valid unencrypted RSA private key.'
            );
        }
        if (!openssl_sign($data, $signature, $resource, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Could not sign the OPay request.');
        }
        return base64_encode($signature);
    }

    private function encryptInChunks(string $plainText, string $publicKey): string
    {
        $resource = openssl_pkey_get_public($publicKey);
        $details = $resource ? openssl_pkey_get_details($resource) : false;
        if (!$resource || !$details) {
            throw new RuntimeException('The configured OPay public key is invalid.');
        }
        $chunkSize = intdiv((int) $details['bits'], 8) - 11;
        $encrypted = '';
        foreach (str_split($plainText, $chunkSize) as $chunk) {
            if (!openssl_public_encrypt($chunk, $block, $resource, OPENSSL_PKCS1_PADDING)) {
                throw new RuntimeException('Could not encrypt the OPay request.');
            }
            $encrypted .= $block;
        }
        return base64_encode($encrypted);
    }

    private function decryptInChunks(string $cipherText, string $privateKey): string
    {
        $resource = openssl_pkey_get_private($privateKey);
        $details = $resource ? openssl_pkey_get_details($resource) : false;
        $binary = base64_decode($cipherText, true);
        if (!$resource || !$details || $binary === false) {
            throw new RuntimeException('The OPay encrypted response or merchant private key is invalid.');
        }
        $blockSize = intdiv((int) $details['bits'], 8);
        $plainText = '';
        foreach (str_split($binary, $blockSize) as $block) {
            if (strlen($block) !== $blockSize || !openssl_private_decrypt($block, $chunk, $resource, OPENSSL_PKCS1_PADDING)) {
                throw new RuntimeException('Could not decrypt the OPay response.');
            }
            $plainText .= $chunk;
        }
        return $plainText;
    }

    private function canonicalize($value)
    {
        if (!is_array($value)) {
            return $value;
        }
        $isList = $value === [] || array_keys($value) === range(0, count($value) - 1);
        if ($isList) {
            $items = array_map(fn ($item) => $this->canonicalize($item), $value);
            usort($items, fn ($a, $b) => strcmp(json_encode($a), json_encode($b)));
            return $items;
        }
        ksort($value, SORT_STRING);
        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }
        return $value;
    }

    private function assertConfigured(): void
    {
        foreach (['client_auth_key', 'branch_id', 'root_merchant_id', 'operator'] as $key) {
            if (blank(config('services.opay_wallet.'.$key))) {
                throw new RuntimeException('Missing OPay configuration: '.$key.'.');
            }
        }
        $this->opayPublicKey();
        $this->merchantPrivateKey();
    }

    private function opayPublicKey(): string
    {
        $key = $this->resolveKeyFile('opay_public_key_path', 'OPay public key');
        if (!openssl_pkey_get_public($key)) {
            throw new RuntimeException(
                'OPAY_WALLET_OPAY_PUBLIC_KEY is not a valid RSA public key.'
            );
        }
        return $key;
    }

    private function merchantPrivateKey(): string
    {
        $key = $this->resolveKeyFile(
            'merchant_private_key_path',
            'merchant private key'
        );
        if (!openssl_pkey_get_private($key)) {
            throw new RuntimeException(
                'OPAY_WALLET_MERCHANT_PRIVATE_KEY is not a valid unencrypted RSA private key. '
                .'Confirm that the merchant private key—not either public key—was pasted.'
            );
        }
        return $key;
    }

    private function resolveKeyFile(string $configKey, string $label): string
    {
        $configuredPath = trim((string) config('services.opay_wallet.'.$configKey));
        if ($configuredPath === '') {
            throw new RuntimeException('Missing path for the '.$label.'.');
        }

        $path = str_starts_with($configuredPath, '/')
            ? $configuredPath
            : base_path($configuredPath);
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException(
                'The configured '.$label.' file does not exist or is not readable: '
                .$configuredPath
            );
        }

        $value = trim((string) file_get_contents($path));
        if ($value === '') {
            throw new RuntimeException('The configured '.$label.' file is empty.');
        }

        return $value;
    }
}
