<?php
namespace Tests\Feature;

use App\Services\{InternationalTopup, Sochitel};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Crypt, DB, Http, Schema};
use Tests\Support\SochitelFixtures as F;

class SochitelApiTest extends \Tests\Support\InternationalApiTestCase
{
    private int $transactionStatus = 9;
    private bool $timeout = false;
    private bool $mismatch = false;
    private ?int $rejectionCode = null;
    private bool $lookupMissing = false;
    private int $posts = 0;
    private array $transaction = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['international.sochitel_base_url' => 'https://sochitel.test/api']);
        DB::table('international_settings')->update(['enabled' => true, 'provider' => 'sochitel', 'sochitel_protocol' => 'rest', 'sochitel_user_id' => '123456', 'sochitel_private_key' => Crypt::encryptString(F::keys()['private']), 'sochitel_currency' => 'USD', 'sochitel_ngn_rate' => 1500]);
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            foreach (['ref', 'plan', 'phone', 'channel', 'description'] as $f) $t->string($f);
            foreach (['user_id', 'subcategory_id', 'quantity', 'status'] as $f) $t->integer($f);
            foreach (['amount', 'subtotal', 'total', 'prev_bal', 'bal'] as $f) $t->decimal($f, 16, 2);
            $t->timestamps();
        });
        DB::table('users')->insert(['id' => 1, 'wallet' => 10000]);
        Http::fake(function ($r) {
            if (str_ends_with($r->url(), '/balance')) return Http::response(['errno' => 0, 'balance' => '100.00', 'currency' => 'USD']);
            if (str_ends_with($r->url(), '/operators')) return Http::response(['errno' => 0, 'operators' => F::operators()]);
            if ($r->method() === 'POST' && str_ends_with($r->url(), '/transaction')) {
                $this->posts++;
                $this->transaction = json_decode($r->body(), true);
                if ($this->timeout) throw new \Illuminate\Http\Client\ConnectionException('Simulated timeout');
                if ($this->rejectionCode !== null) return Http::response(['errno' => $this->rejectionCode], 403);
                return Http::response($this->transactionResponse());
            }
            if (str_contains($r->url(), '/transaction/user/')) return $this->lookupMissing ? Http::response(['errno' => 18], 404) : Http::response($this->transactionResponse());
            throw new \RuntimeException('Unexpected request: ' . $r->url());
        });
    }
    private function input(): array
    {
        return ['provider' => 'sochitel', 'country_code' => 'GH', 'operator_id' => 7, 'product_id' => 11, 'type' => 'data', 'amount' => '50.00', 'phone' => '0241234567'];
    }
    private function transactionResponse(): array
    {
        return ['errno' => 0, 'id' => '123456789', 'reference' => $this->mismatch ? 'anotherPayment' : $this->transaction['reference'], 'recipient' => $this->transaction['recipient'], 'operator' => ['id' => $this->transaction['operator'], 'currency' => $this->transaction['currency']], 'product' => $this->transaction['product'], 'amount' => ['operator' => $this->transaction['amount'], 'user' => '4.00'], 'status' => ['id' => $this->transactionStatus, 'type' => $this->transactionStatus === 0 ? 0 : 2]];
    }
    public function test_rsa_signature_digest_nonce_and_exact_request_path(): void
    {
        app(Sochitel::class)->request(app(InternationalTopup::class)->settings(), 'GET', '/balance');
        app(Sochitel::class)->request(app(InternationalTopup::class)->settings(), 'POST', '/transaction', ['operator' => '7', 'product' => '11', 'recipient' => '233241234567', 'amount' => '50.00', 'currency' => 'GHS', 'reference' => 'testSignature']);
        $seen = [];
        Http::assertSent(function ($r) use (&$seen) {
            $nonce = $r->header('Nonce')[0];
            $this->assertMatchesRegularExpression('/^' . gmdate('N') . '[0-9]{17}$/', $nonce);
            $this->assertNotContains($nonce, $seen); $seen[] = $nonce;
            $digest = 'SHA-256=' . base64_encode(hash('sha256', $r->body(), true));
            $this->assertSame($digest, $r->header('Digest')[0]);
            $signing = '(request-target): ' . strtolower($r->method()) . ' ' . parse_url($r->url(), PHP_URL_PATH) . "\nhost: sochitel.test\ndate: " . $r->header('Date')[0] . "\nnonce: $nonce\ndigest: $digest";
            preg_match('/signature="([^"]+)"/', $r->header('Authorization')[0], $match);
            $this->assertSame(1, openssl_verify($signing, base64_decode($match[1]), F::keys()['public'], OPENSSL_ALGO_SHA256));
            return true;
        });
        $this->assertCount(2, $seen);
    }

    public function test_admin_page_requests_do_not_exhaust_the_connection_test_limit(): void
    {
        $this->login(1);
        for ($i = 0; $i < 6; $i++) $this->getJson('/api/admin/international/settings')->assertOk();
        for ($i = 0; $i < 5; $i++) $this->postJson('/api/admin/international/sochitel/test')->assertOk();
        $response = $this->postJson('/api/admin/international/sochitel/test')->assertStatus(429)->assertHeader('Retry-After');
        $this->assertStringContainsString('Too many Sochitel connection tests.', $response->json('message'));
        $this->getJson('/api/admin/international/settings')->assertOk();
    }

    public function test_admin_sees_safe_rest_authentication_and_endpoint_diagnostics(): void
    {
        $this->login(1);
        foreach ([
            [Http::response('<html>Not Found</html>', 404), 'SOCHITEL_BASE_URL'],
            [Http::response(['errno' => 9, 'error' => 'secret provider text'], 401), 'RSA signature was rejected'],
            [Http::response(['errno' => 10], 401), 'public IP address'],
        ] as [$response, $expected]) {
            Http::swap(new \Illuminate\Http\Client\Factory());
            Http::preventStrayRequests();
            Http::fake(['*' => $response]);
            $result = $this->postJson('/api/admin/international/sochitel/test')->assertStatus(422);
            $this->assertStringContainsString($expected, $result->json('message'));
            $this->assertStringNotContainsString('secret provider text', $result->getContent());
            $this->assertNull(DB::table('international_settings')->value('sochitel_verified_at'));
        }
    }

    public function test_operator_logos_use_provider_metadata_overrides_and_country_scoped_fallbacks(): void
    {
        $provider = app(Sochitel::class);
        $operator = ['id' => '7', 'country' => 'NG', 'name' => 'Nigeria MTN', 'logo' => 'https://cdn.example.com/mtn.png'];
        $images = $provider->operatorLogos($operator);
        $this->assertSame('https://cdn.example.com/mtn.png', $images['logo']);
        $this->assertSame('/template/images/services/mtn.jpeg', $images['fallback_logo']);
        $this->assertSame('https://custom.example.com/mtn.png', $provider->operatorLogos($operator, 'https://custom.example.com/mtn.png')['logo']);
        $operator['logo'] = 'javascript:alert(1)';
        $this->assertSame('/template/images/services/mtn.jpeg', $provider->operatorLogos($operator)['logo']);
        $operator['country'] = 'GH';
        $this->assertNull($provider->operatorLogos($operator)['logo']);
        $operator['logoUrls'] = ['http://unsafe.example.com/logo.png', 'https://cdn.example.com/ghana.png'];
        $this->assertSame('https://cdn.example.com/ghana.png', $provider->operatorLogos($operator)['logo']);
    }
    public function test_catalog_country_search_metadata_and_distinct_named_products(): void
    {
        $this->login();
        $this->getJson('/api/international/countries')->assertOk()->assertJsonPath('data.0.name', 'Ghana')->assertJsonPath('data.0.calling_code', '+233')->assertJsonPath('data.1.name', 'Nigeria');
        $this->getJson('/api/international/operators?country_code=GH&type=data')->assertOk()->assertJsonCount(1, 'data')->assertJsonCount(2, 'data.0.plans')->assertJsonPath('data.0.plans.0.name', '5GB 30 days')->assertJsonPath('data.0.plans.1.product_id', 12)->assertJsonMissingPath('data.0._products');
        $this->getJson('/api/international/operators?country_code=GH&type=airtime')->assertOk()->assertJsonPath('data.0.id', 21)->assertJsonPath('data.0.min', '1.00');
        $this->postJson('/api/international/preview', $this->input())->assertOk()->assertJsonPath('data.amount_kobo', 618000);
        $this->postJson('/api/international/preview', array_merge($this->input(), ['product_id' => 12]))->assertOk()->assertJsonPath('data.amount_kobo', 772500);
        $this->postJson('/api/international/preview', array_merge($this->input(), ['product_id' => null]))->assertUnprocessable();
    }
    public function test_airtime_cost_and_currency_conversion(): void
    {
        $this->login();
        $this->postJson('/api/international/preview', array_merge($this->input(), ['type' => 'airtime', 'operator_id' => 21, 'product_id' => null]))->assertOk()->assertJsonPath('data.amount_kobo', 618000);
        DB::table('international_settings')->update(['sochitel_currency' => 'NGN', 'sochitel_ngn_rate' => 2000]);
        $this->postJson('/api/international/preview', $this->input())->assertOk()->assertJsonPath('data.amount_kobo', 412);
    }
    public function test_pending_then_success_uses_original_route_after_admin_switch(): void
    {
        $service = app(InternationalTopup::class);
        $q = $service->quote(1, $this->input());
        $p = $service->purchase(1, $q->id);
        $this->assertSame('processing', $p->status);
        $this->assertEquals(3820, DB::table('users')->value('wallet'));
        $this->assertLessThanOrEqual(30, strlen($this->transaction['reference']));
        DB::table('international_settings')->update(['provider' => 'vtugate']);
        $this->transactionStatus = 0;
        $p = $service->refresh($p);
        $this->assertSame('success', $p->status);
        $this->assertSame('sochitel', $p->provider);
        $this->assertSame(1, $this->posts);
        $this->assertSame($p->id, $service->purchase(1, $q->id)->id);
    }
    public function test_timeout_recovers_by_client_reference_without_resending(): void
    {
        $this->timeout = true;
        $service = app(InternationalTopup::class);
        $q = $service->quote(1, $this->input());
        $p = $service->purchase(1, $q->id);
        $this->assertNull($p->transaction_id);
        $this->assertSame('processing', $service->purchase(1, $q->id)->status);
        $this->transactionStatus = 0;
        $this->assertSame('success', $service->refresh($p)->status);
        $this->assertSame(1, $this->posts);
    }
    public function test_manual_verification_stays_pending_and_confirmed_failure_refunds_once(): void
    {
        $this->transactionStatus = 59;
        $service = app(InternationalTopup::class);
        $q = $service->quote(1, $this->input());
        $p = $service->purchase(1, $q->id);
        $this->assertSame('processing', $p->status);
        $this->assertSame('processing', $service->refresh($p)->status);
        $this->transactionStatus = 24;
        $p = $service->refresh($p);
        $this->assertSame('reversed', $p->status);
        $service->refresh($p);
        $this->assertEquals(10000, DB::table('users')->value('wallet'));
    }
    public function test_mismatched_response_cannot_settle_payment(): void
    {
        $this->mismatch = true; $this->transactionStatus = 0;
        $service = app(InternationalTopup::class);
        $q = $service->quote(1, $this->input());
        $this->assertSame('processing', $service->purchase(1, $q->id)->status);
        $this->assertEquals(3820, DB::table('users')->value('wallet'));
    }
    public function test_route_change_invalidates_quote_before_wallet_debit(): void
    {
        $service = app(InternationalTopup::class);
        $q = $service->quote(1, $this->input());
        DB::table('international_settings')->update(['provider' => 'vtugate']);
        try { $service->purchase(1, $q->id); $this->fail('Expected route rejection'); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { $this->assertSame(422, $e->getStatusCode()); }
        $this->assertSame(0, $this->posts);
        $this->assertEquals(10000, DB::table('users')->value('wallet'));
    }
    public function test_admin_must_verify_credentials_before_activation_and_secrets_stay_private(): void
    {
        $this->login(1);
        $body = ['enabled' => true, 'provider' => 'sochitel', 'multiplier' => '1.03', 'calling_codes' => []];
        $this->putJson('/api/admin/international/settings', $body)->assertUnprocessable();
        $this->postJson('/api/admin/international/sochitel/test')->assertOk()->assertJsonPath('data.currency', 'USD');
        $this->putJson('/api/admin/international/settings', $body)->assertOk()->assertJsonPath('data.provider', 'sochitel')->assertJsonPath('data.sochitel_verified', true)->assertJsonMissingPath('data.sochitel_private_key');
        $this->assertStringNotContainsString('PRIVATE KEY', DB::table('service_control_audits')->get()->toJson());
        $this->putJson('/api/admin/international/settings', $body + ['sochitel_private_key' => 'invalid key'])->assertUnprocessable();
    }
    public function test_test_connection_checks_currency_and_customer_cannot_use_it(): void
    {
        $this->login();
        $this->postJson('/api/admin/international/sochitel/test')->assertForbidden();
        Http::assertNothingSent();
        $this->login(1);
        DB::table('international_settings')->update(['sochitel_currency' => 'GBP']);
        $this->postJson('/api/admin/international/sochitel/test')->assertUnprocessable();
        $this->assertNull(DB::table('international_settings')->value('sochitel_verified_at'));
    }
    public function test_definite_request_rejection_refunds_once_but_duplicate_reference_stays_pending(): void
    {
        $service = app(InternationalTopup::class);
        $q = $service->quote(1, $this->input());
        $this->rejectionCode = 110;
        $p = $service->purchase(1, $q->id);
        $this->assertSame('reversed', $p->status);
        $service->purchase(1, $q->id);
        $this->assertEquals(10000, DB::table('users')->value('wallet'));
        $this->rejectionCode = 104;
        $q = $service->quote(1, $this->input());
        $p = $service->purchase(1, $q->id);
        $this->assertSame('processing', $p->status);
        $this->lookupMissing = true;
        $this->assertSame('processing', $service->refresh($p)->status);
        $this->assertEquals(3820, DB::table('users')->value('wallet'));
    }
    public function test_changed_exchange_rate_requires_reconfirmation_before_debit(): void
    {
        $service = app(InternationalTopup::class);
        $q = $service->quote(1, $this->input());
        DB::table('international_settings')->update(['sochitel_ngn_rate' => 1600]);
        try { $service->purchase(1, $q->id); $this->fail('Expected price rejection'); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { $this->assertSame(422, $e->getStatusCode()); }
        $this->assertSame(0, $this->posts);
        $this->assertEquals(10000, DB::table('users')->value('wallet'));
    }
    public function test_saving_sochitel_key_encrypts_it_and_preserves_it_when_blank(): void
    {
        $this->login(1);
        $body = ['enabled' => false, 'provider' => 'vtugate', 'multiplier' => '1.03', 'calling_codes' => [], 'sochitel_private_key' => F::keys()['private'], 'sochitel_protocol' => 'rest', 'sochitel_user_id' => '123456', 'sochitel_currency' => 'USD', 'sochitel_ngn_rate' => '1500'];
        $this->putJson('/api/admin/international/settings', $body)->assertOk()->assertJsonPath('data.sochitel_key_configured', true)->assertJsonMissingPath('data.sochitel_private_key');
        $encrypted = DB::table('international_settings')->value('sochitel_private_key');
        $this->assertNotSame(F::keys()['private'], $encrypted);
        $this->assertSame(trim(F::keys()['private']), Crypt::decryptString($encrypted));
        $body['sochitel_private_key'] = null;
        $this->putJson('/api/admin/international/settings', $body)->assertOk();
        $this->assertSame($encrypted, DB::table('international_settings')->value('sochitel_private_key'));
        $this->assertStringNotContainsString('PRIVATE KEY', DB::table('service_control_audits')->get()->toJson());
    }
    public function test_unknown_status_and_manual_verification_never_refund(): void
    {
        $provider = app(Sochitel::class);
        foreach ([9, 46, 59, 999] as $status) $this->assertNull($provider->outcome(['status' => ['id' => $status, 'type' => 2]]));
        $this->assertSame('success', $provider->outcome(['status' => 0]));
        $this->assertSame('reversed', $provider->outcome(['status' => 24]));
        $this->assertNull($provider->outcome(['errno' => 0]));
    }

}
