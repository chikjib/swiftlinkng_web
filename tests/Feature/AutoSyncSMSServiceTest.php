<?php

namespace Tests\Feature;

use App\Services\AutoSyncSMSService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AutoSyncSMSServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.autosync_sms' => [
            'base_url' => 'https://autosyncng.com/api/v1/',
            'api_token' => 'test-token',
            'pin' => '0123',
            'webhook_url' => 'https://example.com/autosync-webhook',
        ]]);
        Http::preventStrayRequests();
    }

    public function test_it_sends_one_authenticated_bulk_request_with_the_order_reference(): void
    {
        Http::fake(['*' => Http::response([
            'status' => 'ok', 'data' => ['transaction' => ['status' => 'successful']],
        ])]);

        $result = AutoSyncSMSService::sendBulkSMS([' 08012345678 ', '2348098765432'], 'Hello', 'Swiftlink', 'order-123');

        $this->assertSame('successful', $result['status']);
        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://autosyncng.com/api/v1/sms'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && $request->hasHeader('Accept', 'application/json')
                && $request['recipients'] === '08012345678,2348098765432'
                && $request['message'] === 'Hello'
                && $request['sender_id'] === 'Swiftlink'
                && $request['request_ref'] === 'order-123'
                && $request['pin'] === '0123'
                && $request['webhook_url'] === 'https://example.com/autosync-webhook';
        });
    }

    /** @dataProvider responseProvider */
    public function test_it_distinguishes_definite_failure_from_uncertain_delivery($body, int $httpStatus, string $expected): void
    {
        Http::fake(['*' => Http::response($body, $httpStatus)]);
        $result = AutoSyncSMSService::sendBulkSMS(['08012345678'], 'Hello', 'Swiftlink', 'order-123');
        $this->assertSame($expected, $result['status']);
    }

    public function responseProvider(): array
    {
        return [
            'pending' => [['status' => 'ok', 'data' => ['transaction' => ['status' => 'pending']]], 200, 'pending'],
            'failed transaction' => [['status' => 'ok', 'data' => ['transaction' => ['status' => 'failed']]], 200, 'failed'],
            'rejected' => [['status' => 'error', 'message' => 'Invalid sender'], 422, 'failed'],
            'API error on HTTP 200' => [['status' => 'error'], 200, 'failed'],
            'server error' => [['status' => 'error'], 500, 'pending'],
            'duplicate request' => [['status' => 'error'], 409, 'pending'],
            'HTTP timeout' => [['status' => 'error'], 408, 'pending'],
            'rate limit' => [['status' => 'error'], 429, 'pending'],
            'malformed' => ['<html>gateway error</html>', 200, 'pending'],
            'missing transaction' => [['status' => 'ok'], 200, 'pending'],
            'unknown status' => [['status' => 'ok', 'data' => ['transaction' => ['status' => 'processing']]], 200, 'pending'],
        ];
    }

    /** @dataProvider invalidRequestProvider */
    public function test_it_rejects_invalid_input_before_contacting_provider(array $recipients, string $message, string $sender): void
    {
        Http::fake();
        try {
            AutoSyncSMSService::sendBulkSMS($recipients, $message, $sender, 'order-123');
            $this->fail('Invalid input should be rejected.');
        } catch (ValidationException $e) {
            Http::assertNothingSent();
        }
    }

    public function invalidRequestProvider(): array
    {
        return [
            'empty list' => [[], 'Hello', 'Swiftlink'],
            'too many recipients' => [array_fill(0, 1001, '08012345678'), 'Hello', 'Swiftlink'],
            'invalid number' => [['123'], 'Hello', 'Swiftlink'],
            'empty recipient' => [['08012345678', ''], 'Hello', 'Swiftlink'],
            'empty message' => [['08012345678'], '', 'Swiftlink'],
            'long message' => [['08012345678'], str_repeat('a', 1001), 'Swiftlink'],
            'long sender' => [['08012345678'], 'Hello', str_repeat('a', 21)],
        ];
    }

    public function test_missing_credentials_do_not_send_a_request(): void
    {
        Http::fake();
        config(['services.autosync_sms.pin' => null]);
        try {
            AutoSyncSMSService::sendBulkSMS(['08012345678'], 'Hello', 'Swiftlink', 'order-123');
            $this->fail('Missing PIN should be rejected.');
        } catch (\RuntimeException $e) {
            Http::assertNothingSent();
        }
    }

    public function test_connection_errors_propagate_without_retrying(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;
            throw new ConnectionException('Timed out');
        });
        try {
            AutoSyncSMSService::sendBulkSMS(['08012345678'], 'Hello', 'Swiftlink', 'order-123');
            $this->fail('Connection failure should remain uncertain.');
        } catch (ConnectionException $e) {
            $this->assertSame(1, $attempts);
        }
    }
}
