<?php

namespace Tests\Feature;

use App\Http\Controllers\API\UserController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PalmpayIdentityTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->booting(function () use ($app) {
            $app['config']->set('database.default', 'sqlite');
            $app['config']->set('database.connections.sqlite.database', ':memory:');
            $app['config']->set('cache.default', 'array');
            $app['config']->set('logging.default', 'null');
            $app['cache']->put('social_settings', (object) ['value' => '||'], 60);
        });
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function ($t) {
            $t->id();
            foreach (['firstname', 'lastname', 'email', 'bvn', 'nin', 'palmpay_account', 'palmpay_reserved_acct', 'withdrawal', 'commission'] as $column) $t->text($column)->nullable();
            $t->timestamps();
        });
        config(['app.palmPayUrl' => 'https://palm.test/', 'app.palmPayAppID' => 'test-key', 'app.palmPayPrivateKey' => 'test-key']);
        Http::preventStrayRequests();
        $this->providerResponse();
    }

    private function providerResponse(array $response = []): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        Http::fake(['https://palm.test/*' => Http::response($response ?: [
            'respCode' => '00000000', 'respMsg' => 'success', 'status' => true,
            'data' => ['virtualAccountNo' => '0000000001', 'virtualAccountName' => 'Test Customer', 'status' => 'Enabled'],
        ])]);
    }

    private function createAccount($bvn, $nin, $existing = null)
    {
        DB::table('users')->insert([
            'id' => 1, 'firstname' => 'Test', 'lastname' => 'Customer', 'email' => 'test@example.test',
            'bvn' => $bvn, 'nin' => $nin, 'palmpay_reserved_acct' => $existing,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        // Only signing is replaced; run the real provider payload, controller and persistence.
        $controller = new class extends UserController {
            public function generate_signature(array $data, string $private_key) { return 'test-signature'; }
        };
        $controller->user = User::find(1);
        return $controller->generate_reserved_account(new Request(['bank' => 'palmpay']));
    }

    private function encrypt(string $number): string
    {
        return openssl_encrypt($number, 'AES-128-CTR', 'SWIFtlInk1nG', 0, 'SWiftLiNknG89101');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('identities')]
    public function test_creates_using_the_correct_identity_type(bool $bvn, bool $nin, string $type, string $number): void
    {
        $response = $this->createAccount($bvn ? $this->encrypt('11111111111') : null, $nin ? $this->encrypt('22222222222') : null);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['success']);
        $this->assertSame('0000000001', DB::table('users')->value('palmpay_account'));
        $stored = json_decode(DB::table('users')->value('palmpay_reserved_acct'), true);
        $this->assertSame('0000000001', $stored['accountNumber']);
        Http::assertSent(fn ($request) => $request['identityType'] === $type && $request['licenseNumber'] === $number);
        Http::assertSentCount(1);
    }

    public static function identities(): array
    {
        return [[true, false, 'personal', '11111111111'], [false, true, 'personal_nin', '22222222222'], [true, true, 'personal', '11111111111']];
    }

    public function test_missing_identity_returns_actionable_error_without_provider_call(): void
    {
        $response = $this->createAccount('null', '');
        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('BVN or NIN', $response->getData(true)['data']);
        Http::assertNothingSent();
    }

    public function test_api_never_marks_a_missing_bvn_verified_when_nin_exists(): void
    {
        $user = new User();
        $user->bvn = 'null';
        $user->nin = $this->encrypt('22222222222');
        $this->assertNull($user->bvn);
        $this->assertNotNull($user->nin);
        $user->bvn = '';
        $this->assertNull($user->bvn);
    }

    public function test_invalid_saved_identity_does_not_send_dummy_identity(): void
    {
        $response = $this->createAccount($this->encrypt('123'), null);
        $this->assertSame(422, $response->getStatusCode());
        Http::assertNothingSent();
    }

    public function test_provider_rejection_is_not_saved_as_success(): void
    {
        $this->providerResponse(['respCode' => 'INVALID_ID', 'respMsg' => 'rejected', 'status' => false]);
        $response = $this->createAccount(null, $this->encrypt('22222222222'));
        $this->assertSame(502, $response->getStatusCode());
        $this->assertNull(DB::table('users')->value('palmpay_account'));
    }

    public function test_success_code_without_account_number_is_not_saved(): void
    {
        $this->providerResponse(['respCode' => '00000000', 'respMsg' => 'success', 'data' => []]);
        $response = $this->createAccount(null, $this->encrypt('22222222222'));
        $this->assertSame(502, $response->getStatusCode());
        $this->assertNull(DB::table('users')->value('palmpay_account'));
    }

    public function test_existing_account_is_returned_without_creating_another(): void
    {
        $response = $this->createAccount(null, null, json_encode(['accountNumber' => '0000000001']));
        $this->assertSame(200, $response->getStatusCode());
        Http::assertNothingSent();
    }
}
