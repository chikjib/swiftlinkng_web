<?php

namespace Tests\Feature;

use App\Jobs\IntegrityPortalWebhookJob;
use App\Models\Order;
use App\Traits\IntegrationsTrait;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Spatie\WebhookClient\Models\WebhookCall;
use Tests\TestCase;

class IntegrityPortalWebhookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->decimal('wallet', 12, 2);
            $table->timestamps();
        });
        Schema::create('subcategory', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('ref');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('subcategory_id');
            $table->integer('status');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('prev_bal', 12, 2);
            $table->decimal('bal', 12, 2);
            $table->text('response')->nullable();
            $table->timestamps();
        });
        Schema::create('refund_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('credited_amount', 12, 2);
            $table->timestamps();
        });
        DB::table('users')->insert(['id' => 1, 'wallet' => 900]);
        DB::table('subcategory')->insert(['id' => 1]);
        DB::table('orders')->insert([
            'id' => 1, 'ref' => 'integrity-test', 'user_id' => 1,
            'subcategory_id' => 1, 'status' => 0, 'subtotal' => 100,
            'prev_bal' => 1000, 'bal' => 900,
        ]);
    }

    public function test_purchase_sends_callback_using_the_zoe_pattern(): void
    {
        config(['app.integrityPortalUrl' => 'https://integrity.example.test/api', 'app.integrityPortalKey' => 'test-key']);
        Http::preventStrayRequests();
        Http::fake(['https://integrity.example.test/api' => Http::response(['text_status' => 'PENDING'])]);
        $integration = new class { use IntegrationsTrait; };
        $this->assertSame(['text_status' => 'PENDING'], $integration->integrityPortal('plan-1', '08012345678', 'integrity-test'));
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer test-key')
            && $request['product_code'] === 'plan-1'
            && $request['phone_number'] === '08012345678'
            && $request['user_reference'] === 'integrity-test'
            && $request['action'] === 'vend'
            && $request['bypass_network'] === 'yes'
            && $request['async'] === false
            && $request['callback'] === 'https://swiftlinkng.com/integrity-portal-webhook');
    }

    public function test_route_uses_integrity_configuration_and_is_exempt_from_csrf(): void
    {
        $route = app('router')->getRoutes()->match(\Illuminate\Http\Request::create('/integrity-portal-webhook', 'POST'));
        $this->assertSame('webhook-client-integrity-portal-webhook', $route->getName());
        $config = collect(config('webhook-client.configs'))->firstWhere('name', 'integrity-portal-webhook');
        $this->assertSame(IntegrityPortalWebhookJob::class, $config['process_webhook_job']);
        $this->assertSame(\App\IntegrityPortalSignatureValidator::class, $config['signature_validator']);
        $middleware = new class(app(), app('encrypter')) extends \App\Http\Middleware\VerifyCsrfToken {
            public function excludesIntegrity(): bool
            {
                return $this->inExceptArray(\Illuminate\Http\Request::create('/integrity-portal-webhook', 'POST'));
            }
        };
        $this->assertTrue($middleware->excludesIntegrity());
    }

    public function test_completed_callback_updates_pending_order_once(): void
    {
        foreach (['COMPLETED', 'Done'] as $status) {
            DB::table('orders')->where('id', 1)->update(['status' => 0]);
            $this->process($status);
            $this->assertDatabaseHas('orders', ['id' => 1, 'status' => 1, 'response' => 'Provider response']);
            $this->process('FAILED');
            $this->assertDatabaseCount('refund_logs', 0);
            $this->assertDatabaseHas('users', ['id' => 1, 'wallet' => 900]);
        }
    }

    public function test_failed_callback_refunds_only_once(): void
    {
        $this->process('FAILED');
        $this->process('Failed');
        $this->assertDatabaseHas('orders', ['id' => 1, 'status' => 4]);
        $this->assertDatabaseHas('orders', ['ref' => 'integrity-test', 'status' => 2, 'response' => 'Provider response', 'bal' => 1000, 'prev_bal' => 900]);
        $this->assertDatabaseHas('users', ['id' => 1, 'wallet' => 1000]);
        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseCount('refund_logs', 1);
    }

    public function test_pending_and_unknown_references_do_not_change_orders_or_wallets(): void
    {
        $this->process('PENDING');
        $this->process('COMPLETED', 'unknown-ref');
        $this->assertDatabaseHas('orders', ['id' => 1, 'status' => 0, 'response' => null]);
        $this->assertDatabaseHas('users', ['id' => 1, 'wallet' => 900]);
        $this->assertDatabaseCount('refund_logs', 0);
    }

    private function process(string $status, string $ref = 'integrity-test'): void
    {
        $call = new WebhookCall;
        $call->payload = ['user_reference' => $ref, 'status' => $status, 'true_response' => 'Provider response'];
        // Exercise the webhook's status/refund handling without unrelated reward
        // and outbound customer webhook listeners.
        Order::withoutEvents(fn () => (new IntegrityPortalWebhookJob($call))->handle());
    }
}
