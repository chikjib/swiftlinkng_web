<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class AutoSyncSMSService
{
    public static function validate(array $recipients, string $message, string $senderId): array
    {
        $recipients = array_map('trim', $recipients);
        Validator::make([
            'recipients' => $recipients,
            'message' => $message,
            'sender_id' => $senderId,
        ], [
            'recipients' => ['required', 'array', 'min:1', 'max:1000'],
            'recipients.*' => ['required', 'string', 'regex:/^(?:234|0)[789][01][0-9]{8}$/'],
            'message' => ['required', 'string', 'max:1000'],
            'sender_id' => ['required', 'string', 'max:20'],
        ])->validate();

        return $recipients;
    }

    public static function ensureConfigured(): void
    {
        if (!config('services.autosync_sms.api_token') ||
            !preg_match('/^[0-9]{4}$/', (string) config('services.autosync_sms.pin'))) {
            throw new RuntimeException('AutoSync SMS credentials are not configured.');
        }
    }

    public static function sendBulkSMS(array $recipients, string $message, string $senderId, string $reference): array
    {
        self::ensureConfigured();
        $recipients = self::validate($recipients, $message, $senderId);
        Validator::make(['request_ref' => $reference], [
            'request_ref' => ['required', 'string', 'max:50'],
        ])->validate();

        // A single request preserves AutoSync's bulk pricing and idempotency.
        $response = Http::withToken(config('services.autosync_sms.api_token'))
            ->acceptJson()->asJson()->timeout(60)
            ->post(rtrim(config('services.autosync_sms.base_url'), '/') . '/sms', [
                'request_ref' => $reference,
                'recipients' => implode(',', $recipients),
                'message' => $message,
                'sender_id' => $senderId,
                'pin' => (string) config('services.autosync_sms.pin'),
                'webhook_url' => config('services.autosync_sms.webhook_url'),
            ]);

        $transactionStatus = $response->json('data.transaction.status');
        $status = 'pending';
        if ($response->successful() && $response->json('status') === 'ok' &&
            in_array($transactionStatus, ['successful', 'failed'], true)) {
            $status = $transactionStatus;
        } elseif ($response->status() < 500 &&
            $response->json('status') === 'error' && $transactionStatus === null &&
            !in_array($response->status(), [408, 409, 429], true)) {
            $status = 'failed';
        }

        // Unknown responses must not trigger refunds for potentially accepted SMS.
        return ['status' => $status];
    }
}
