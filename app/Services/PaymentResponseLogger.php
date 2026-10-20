<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PaymentResponseLogger
{
    public const CONTEXT = 'payment_response_log_id';

    public static function active(): bool
    {
        return app()->bound('request') && request()->attributes->has(self::CONTEXT);
    }

    public static function record(string $event, array $context): void
    {
        if (!self::active()) {
            return;
        }
        try {
            Log::channel('payment_responses')->info('payment.'.$event, array_merge([
                'request_id' => request()->attributes->get(self::CONTEXT),
                'route' => request()->path(),
                'method' => request()->method(),
                'user_id' => optional(request()->user())->id,
            ], self::redact($context)));
        } catch (\Throwable $e) {
            // Diagnostics must not interrupt account creation or payment handling.
            error_log('Unable to write payment response diagnostics.');
        }
    }

    public static function redact($value, string $key = '')
    {
        $normalized = strtolower(preg_replace('/[^a-z0-9]/i', '', $key));
        if (preg_match('/token|secret|password|signature|privatekey|publickey|authorization|apikey|authkey|accesscode|bvn|nin|license|email|phone|mobile|address|birth|cardnumber|pan$|cvv|pin$|firstname|lastname|customername|accountname|walletname|depositcode|accountno|accountnumber|virtualaccno|virtualaccount|paramcontent|checkouturl|paymenturl|paymentlink/', $normalized)
            || in_array($normalized, ['name', 'sign', 'auth', 'customeridentifier', 'userid', 'customerinfo', 'reservedacct', 'wemareservedacct', 'gtbankreservedacct', 'providusreservedacct', 'rehobothreservedacct', 'opayreservedacct', 'palmpayreservedacct', 'moniepointreservedacct'], true)) {
            return '[REDACTED]';
        }
        if (is_object($value)) {
            $value = (array) $value;
        }
        if (is_array($value)) {
            $clean = [];
            foreach ($value as $field => $item) {
                $clean[$field] = self::redact($item, (string) $field);
            }
            return $clean;
        }
        if (is_string($value)) {
            // Some providers put a JSON envelope inside a string field.
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return self::redact($decoded);
            }
            $value = preg_replace('/-----BEGIN[^-]+-----.*?-----END[^-]+-----/s', '[REDACTED KEY]', $value);
            $value = preg_replace('/\bBearer\s+\S+/i', 'Bearer [REDACTED]', $value);
            $value = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', '[REDACTED EMAIL]', $value);
            $value = preg_replace('/\b\d{9,}\b/', '[REDACTED NUMBER]', $value);
            // Non-JSON bodies can echo submitted data. Keep structured errors only.
            if ($key === 'raw_body') {
                return '[Non-JSON response omitted; '.strlen($value).' bytes]';
            }
            return mb_substr($value, 0, 16000);
        }
        return $value;
    }

    public static function body(string $body): array
    {
        $decoded = json_decode($body, true);
        if (is_array($decoded) && isset($decoded['sign'], $decoded['timestamp']) && is_string($decoded['data'] ?? null)) {
            $decoded['data'] = '[Encrypted response; see provider_decrypted_response entry]';
        }
        return is_array($decoded) ? ['response' => $decoded] : ['raw_body' => $body];
    }

    public static function curl(string $provider, string $operation, $handle, $body): void
    {
        if (!self::active()) {
            return;
        }
        self::record('provider_response', array_merge([
            'provider' => $provider,
            'operation' => $operation,
            'http_status' => curl_getinfo($handle, CURLINFO_HTTP_CODE),
            'curl_errno' => curl_errno($handle),
            'transport_error' => curl_error($handle),
        ], self::body(is_string($body) ? $body : '')));
    }
}
