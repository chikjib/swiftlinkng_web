<?php

namespace Tests\Feature;

use App\Http\Middleware\LogPaymentResponses;
use App\Services\PaymentResponseLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class PaymentResponseLoggingTest extends TestCase
{
    private array $entries = [];

    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->booting(function () use ($app) {
            $app['config']->set('database.default', 'sqlite');
            $app['config']->set('database.connections.sqlite.database', ':memory:');
            $app['config']->set('cache.default', 'array');
            $app['cache']->put('social_settings', (object) ['value' => '||'], 60);
        });
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $writer = Mockery::mock();
        $writer->shouldReceive('info')->andReturnUsing(function ($event, $context) {
            $this->entries[] = [$event, $context];
        });
        Log::shouldReceive('channel')->with('payment_responses')->andReturn($writer);
    }

    private function request(string $path = '/api/reserve/account/moniepoint'): Request
    {
        $request = Request::create($path, 'POST');
        $this->app->instance('request', $request);
        $request->setUserResolver(fn () => (object) ['id' => 42]);
        return $request;
    }

    public function test_logs_rejected_provider_and_app_responses_with_the_same_request_id(): void
    {
        Http::fake(['provider.test/*' => Http::response([
            'respCode' => 'INVALID_BVN', 'respMsg' => 'Identity rejected',
            'data' => ['bvn' => '12345678901', 'email' => 'customer@example.com'],
        ], 422)]);
        $response = (new LogPaymentResponses)->handle($this->request('/api/palmpay-bank-transfer'), function () {
            Http::post('https://provider.test/create');
            return response()->json(['success' => false, 'message' => 'Try later'], 502);
        });
        $this->assertSame(['payment.started', 'payment.provider_response', 'payment.app_response'], array_column($this->entries, 0));
        $provider = $this->entries[1][1];
        $this->assertSame(422, $provider['http_status']);
        $this->assertSame('INVALID_BVN', $provider['response']['respCode']);
        $this->assertSame('[REDACTED]', $provider['response']['data']['bvn']);
        $this->assertSame('[REDACTED]', $provider['response']['data']['email']);
        $this->assertSame(502, $this->entries[2][1]['http_status']);
        $this->assertSame($provider['request_id'], $response->headers->get('X-Payment-Request-ID'));
        $this->assertSame(42, $provider['user_id']);
    }

    public function test_captures_exceptions_without_changing_them(): void
    {
        $exception = new \RuntimeException('Provider rejected bvn 12345678901');
        try {
            (new LogPaymentResponses)->handle($this->request(), function () use ($exception) { throw $exception; });
            $this->fail('Expected original exception');
        } catch (\RuntimeException $e) {
            $this->assertSame($exception, $e);
        }
        $this->assertSame('payment.exception', $this->entries[1][0]);
        $this->assertStringNotContainsString('12345678901', $this->entries[1][1]['message']);
    }

    public function test_redacts_nested_tokens_identity_and_non_json_bodies_but_keeps_error_codes(): void
    {
        $clean = PaymentResponseLogger::redact([
            'responseCode' => 'C_1113', 'responseMessage' => 'not within ip white list',
            'responseBody' => ['accessToken' => 'secret-token', 'accountNumber' => '0123456789', 'bankName' => 'Moniepoint'],
            'nested' => json_encode(['nin' => '12345678901', 'status' => 'failed']),
            'raw_body' => '<html>secret-token</html>',
        ]);
        $this->assertSame('C_1113', $clean['responseCode']);
        $this->assertSame('not within ip white list', $clean['responseMessage']);
        $this->assertSame('Moniepoint', $clean['responseBody']['bankName']);
        $this->assertSame('[REDACTED]', $clean['responseBody']['accessToken']);
        $this->assertSame('[REDACTED]', $clean['responseBody']['accountNumber']);
        $this->assertSame('[REDACTED]', $clean['nested']['nin']);
        $this->assertStringNotContainsString('secret-token', $clean['raw_body']);
    }

    public function test_records_curl_transport_errors_and_decrypted_opay_errors(): void
    {
        (new LogPaymentResponses)->handle($this->request('/api/opay/wallet'), function () {
            $handle = curl_init();
            PaymentResponseLogger::curl('monnify', 'create_reserved_account', $handle, false);
            curl_close($handle);
            PaymentResponseLogger::record('provider_decrypted_response', [
                'provider' => 'opay', 'response' => ['code' => 'C_1113', 'message' => 'IP rejected', 'data' => ['depositCode' => '1234567890']],
            ]);
            return response()->json(['success' => false], 502);
        });
        $this->assertSame(0, $this->entries[1][1]['http_status']);
        $this->assertSame('monnify', $this->entries[1][1]['provider']);
        $this->assertSame('C_1113', $this->entries[2][1]['response']['code']);
        $this->assertSame('[REDACTED]', $this->entries[2][1]['response']['data']['depositCode']);
    }

    public function test_unrelated_requests_do_not_log_provider_responses(): void
    {
        Http::fake(['provider.test/*' => Http::response(['success' => true])]);
        (new LogPaymentResponses)->handle($this->request('/api/profile'), function () {
            Http::post('https://provider.test/create');
            return response()->json(['ok' => true]);
        });
        $this->assertSame([], $this->entries);
    }

    public function test_failed_logging_does_not_fail_the_payment(): void
    {
        $brokenLogger = Mockery::mock();
        $brokenLogger->shouldReceive('channel')->with('payment_responses')->andThrow(new \RuntimeException('Disk unavailable'));
        Log::swap($brokenLogger);
        $response = (new LogPaymentResponses)->handle($this->request(), fn () => response()->json(['success' => true]));
        $this->assertSame(200, $response->getStatusCode());
    }
}
