<?php

namespace Tests\Feature;

use App\Jobs\ProcessWebhookJob;
use App\Models\Order;
use App\Models\User;
use App\Services\FundingRecoveryService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Spatie\WebhookClient\Models\WebhookCall;
use Tests\TestCase;

class MonnifyRepushTest extends TestCase
{
    private array $payment;

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
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'app.monnifyBaseUrl' => 'https://monnify.test', 'app.monnifyAPIKey' => 'key',
            'app.monnifySecretKey' => 'secret', 'funding_recoveries' => []]);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('email'); $table->decimal('wallet', 16, 2); $table->integer('role')->default(0); $table->timestamps();
        });
        Schema::create('subcategory', function (Blueprint $table) {
            $table->id(); $table->string('title'); $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            foreach (['ref', 'monnify_reference', 'plan', 'description', 'response', 'channel'] as $column) $table->string($column)->nullable();
            foreach (['user_id', 'subcategory_id', 'quantity', 'status'] as $column) $table->integer($column)->nullable();
            foreach (['amount', 'subtotal', 'total', 'prev_bal', 'bal'] as $column) $table->decimal($column, 16, 2)->nullable();
            $table->timestamps();
        });
        DB::table('users')->insert(['id' => 1, 'email' => 'customer@example.com', 'wallet' => 10, 'role' => 0]);
        DB::table('subcategory')->insert(['id' => 1, 'title' => 'Monnify']);
        // Referral side effects are unrelated to deposit persistence.
        Order::unsetEventDispatcher();
        $this->payment = ['transactionReference' => 'MNFY|67|123', 'paymentStatus' => 'PAID',
            'settlementAmount' => '90.00', 'amountPaid' => '100.00', 'currency' => 'NGN',
            'customer' => ['email' => 'customer@example.com'], 'product' => ['type' => 'RESERVED_ACCOUNT']];
        Http::preventStrayRequests();
        $this->fakeMonnify();
    }

    protected function tearDown(): void
    {
        Order::setEventDispatcher($this->app['events']);
        parent::tearDown();
    }

    private function fakeMonnify(array $overrides = [], bool $success = true): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        Http::fake([
            'https://monnify.test/api/v1/auth/login' => Http::response(['requestSuccessful' => true, 'responseBody' => ['accessToken' => 'token']]),
            'https://monnify.test/api/v2/transactions/*' => Http::response(['requestSuccessful' => $success,
                'responseCode' => $success ? '0' : 99, 'responseMessage' => 'Verification failed',
                'responseBody' => array_replace_recursive($this->payment, $overrides)]),
        ]);
    }

    private function repush()
    {
        $admin = new User(); $admin->id = 99; $admin->role = 1;
        $this->actingAs($admin, 'api');
        return $this->postJson('/api/monnify/repush', ['transaction_reference' => $this->payment['transactionReference']]);
    }

    private function webhook(): void
    {
        $call = new WebhookCall();
        $call->payload = ['eventData' => ['transactionReference' => $this->payment['transactionReference']]];
        (new ProcessWebhookJob($call))->handle();
    }

    public function test_it_credits_customer_and_records_both_references_only_once(): void
    {
        $this->repush()->assertOk()->assertJsonPath('data.amount', 90)->assertJsonPath('data.email', 'customer@example.com');
        $this->repush()->assertStatus(422);
        $this->webhook();
        $this->assertEquals(100, DB::table('users')->value('wallet'));
        $this->assertSame(1, DB::table('orders')->count());
        $this->assertSame($this->payment['transactionReference'], DB::table('orders')->value('monnify_reference'));
        Http::assertSent(fn ($request) => $request->url() === 'https://monnify.test/api/v2/transactions/MNFY%7C67%7C123'
            && $request->hasHeader('Authorization', 'Bearer token'));
    }

    /** @dataProvider productTypes */
    public function test_webhook_before_repush_does_not_credit_twice(string $type): void
    {
        $this->fakeMonnify(['product' => ['type' => $type]]);
        $this->webhook();
        $this->repush()->assertStatus(422);
        $this->assertEquals(100, DB::table('users')->value('wallet'));
        $this->assertSame(1, DB::table('orders')->count());
    }

    public function productTypes(): array
    {
        return [['RESERVED_ACCOUNT'], ['WEB_SDK'], ['MOBILE_SDK'], ['API_NOTIFICATION']];
    }

    public function test_blocked_customer_cannot_be_funded(): void
    {
        DB::table('users')->where('id', 1)->update(['email' => 'salihusanusi853@gmail.com']);
        $this->fakeMonnify(['customer' => ['email' => 'salihusanusi853@gmail.com']]);
        $this->repush()->assertStatus(422);
        $this->assertEquals(10, DB::table('users')->value('wallet'));
        $this->assertSame(0, DB::table('orders')->count());
    }

    public function test_missing_reference_is_rejected_before_contacting_monnify(): void
    {
        $admin = new User(); $admin->id = 99; $admin->role = 1;
        $this->actingAs($admin, 'api')->postJson('/api/monnify/repush', [])->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_legacy_monnify_reference_prevents_credit(): void
    {
        DB::table('orders')->insert(['ref' => 'legacy', 'monnify_reference' => $this->payment['transactionReference']]);
        $this->repush()->assertStatus(422);
        $this->webhook();
        $this->assertEquals(10, DB::table('users')->value('wallet'));
    }

    /** @dataProvider invalidPayments */
    public function test_invalid_payment_never_funds(array $overrides, bool $success = true): void
    {
        $this->fakeMonnify($overrides, $success);
        $this->repush()->assertStatus(422);
        $this->assertEquals(10, DB::table('users')->value('wallet'));
        $this->assertSame(0, DB::table('orders')->count());
    }

    public function invalidPayments(): array
    {
        return [
            'failure' => [[], false], 'pending' => [['paymentStatus' => 'PENDING']],
            'mismatch' => [['transactionReference' => 'different']],
            'unknown customer' => [['customer' => ['email' => 'unknown@example.com']]],
            'currency' => [['currency' => 'USD']], 'negative amount' => [['settlementAmount' => '-1']],
            'missing amount' => [['settlementAmount' => null]], 'invalid amount' => [['settlementAmount' => 'NaN']],
        ];
    }

    public function test_funding_failure_rolls_back_wallet_and_order(): void
    {
        $this->mock(FundingRecoveryService::class)->shouldReceive('apply')->once()->andThrow(new \RuntimeException('test failure'));
        $this->repush()->assertStatus(502);
        $this->assertEquals(10, DB::table('users')->value('wallet'));
        $this->assertSame(0, DB::table('orders')->count());
    }

    public function test_customer_and_guest_cannot_repush(): void
    {
        $this->postJson('/api/monnify/repush', ['transaction_reference' => 'test'])->assertUnauthorized();
        $this->actingAs(User::find(1), 'api');
        $this->postJson('/api/monnify/repush', ['transaction_reference' => 'test'])->assertForbidden();
        Http::assertNothingSent();
    }
}
