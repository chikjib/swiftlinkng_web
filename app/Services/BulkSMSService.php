<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class BulkSMSService
{
    /**
     * Send SMS to one recipient.
     */
    public static function sendSMS(
        string $to,
        string $message,
        ?string $senderId = null
    ): array {
        $baseUrl = rtrim(config('services.bulksms.base_url'), '/');
        $apiToken = config('services.bulksms.api_token');
        $defaultSenderId = config('services.bulksms.sender_id');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiToken,
            'Accept' => 'application/json',
        ])
            ->timeout(30)
            ->post($baseUrl . '/v2/sms', [
                'from' => $senderId ?: $defaultSenderId,
                'to' => $to,
                'body' => $message,
                'gateway' => 'direct-refund',
            ]);

        if ($response->successful()) {
            $data = $response->json();

            Log::info('SMS sent successfully', [
                'recipient' => $to,
                'message_id' => isset($data['data']['id'])
                    ? $data['data']['id']
                    : null,
                'cost' => isset($data['data']['cost'])
                    ? $data['data']['cost']
                    : null,
            ]);

            return is_array($data) ? $data : [];
        }

        $errorMessage = $response->json('message');

        if (!$errorMessage) {
            $errorMessage = $response->body() ?: 'Unknown error';
        }

        Log::error('SMS sending failed', [
            'recipient' => $to,
            'status' => $response->status(),
            'error' => $response->json() ?: $response->body(),
        ]);

        throw new RuntimeException(
            'Failed to send SMS: ' . $errorMessage
        );
    }

    /**
     * Send SMS to multiple recipients.
     */
    public static function sendBulkSMS(
        array $recipients,
        string $message,
        ?string $senderId = null
    ): array {
        $results = [
            'total' => count($recipients),
            'successful' => 0,
            'failed' => 0,
            'results' => [],
        ];

        foreach ($recipients as $recipient) {
            try {
                $result = self::sendSMS(
                    $recipient,
                    $message,
                    $senderId
                );

                $results['successful']++;

                $results['results'][] = [
                    'recipient' => $recipient,
                    'status' => 'success',
                    'data' => $result,
                ];
            } catch (Throwable $e) {
                $results['failed']++;

                $results['results'][] = [
                    'recipient' => $recipient,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];

                Log::error('Bulk SMS recipient failed', [
                    'recipient' => $recipient,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $results['message'] = self::friendlyResponse($results);

        return $results;
    }

    /**
     * Turn the provider's bulk result into a message suitable for customers.
     */
    public static function friendlyResponse(array $response): string
    {
        $total = max(0, (int) ($response['total'] ?? 0));
        $successful = max(0, (int) ($response['successful'] ?? 0));
        $failed = max(0, (int) ($response['failed'] ?? 0));

        if ($total === 0) {
            return 'No valid phone numbers were supplied. Please check the numbers and try again.';
        }

        if ($successful === $total) {
            return 'Your SMS was sent successfully to ' . $total . ' ' .
                self::recipientLabel($total) . '.';
        }

        if ($successful === 0) {
            return "We couldn't send your SMS to " . $total . ' ' .
                self::recipientLabel($total) . '. Please check the ' .
                ($total === 1 ? 'number' : 'numbers') . ' and try again.';
        }

        return 'Your SMS was sent to ' . $successful . ' of ' . $total .
            ' recipients. Delivery failed for ' . $failed . ' ' .
            self::recipientLabel($failed) . '.';
    }

    private static function recipientLabel(int $count): string
    {
        return $count === 1 ? 'recipient' : 'recipients';
    }
}
