<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WhatsAppRefundRecoveryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.connections.whatsapp_bot' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'services.whatsapp_bot_admin.base_url' => 'https://bot.test',
            'services.whatsapp_bot_admin.secret' => 'server-only-secret',
        ]);
        DB::purge('whatsapp_bot');
        Schema::connection('whatsapp_bot')->create('orders', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('status');
        });
        DB::connection('whatsapp_bot')->table('orders')->insert(['id' => 'order-1', 'status' => 'FAILED']);
        Schema::create('whats_app_transaction_admin_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('admin_user_id');
            $table->string('bot_order_id');
            $table->string('old_status');
            $table->string('new_status');
            $table->text('note');
            $table->timestamps();
        });
        Http::preventStrayRequests();
    }

    public function test_only_admins_can_preview_or_apply_recovery(): void
    {
        $this->getJson($this->url())->assertUnauthorized();
        $this->actingAs((new User)->forceFill(['id' => 9, 'role' => 0]), 'api');
        $this->getJson($this->url())->assertForbidden();
        $this->postJson($this->url(), $this->body())->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_preview_uses_bot_api_without_a_debit_or_admin_log(): void
    {
        $this->admin();
        Http::fake(['bot.test/*' => Http::response(['recovery_amount' => '17450.00', 'wallet_balance' => '17450.00', 'already_recovered' => false])]);
        $this->getJson($this->url())->assertOk()->assertJsonPath('data.recovery_amount', '17450.00');
        Http::assertSent(fn ($request) => $request->method() === 'GET' && $request->hasHeader('X-Swiftlink-Admin-Secret', 'server-only-secret'));
        $this->assertSame(0, DB::table('whats_app_transaction_admin_logs')->count());
    }

    public function test_apply_forwards_confirmation_and_records_admin_identity(): void
    {
        $this->admin();
        Http::fake(['bot.test/*' => Http::response(['message' => 'Recovered.', 'recovery_amount' => '17450.00', 'wallet_balance' => '0.00', 'already_recovered' => false])]);
        $this->postJson($this->url(), $this->body())->assertOk()->assertJsonPath('data.wallet_balance', '0.00');
        Http::assertSent(fn ($request) => $request->method() === 'POST' && $request['reason'] === 'Provider confirmed delivery.' && $request['provider_success_confirmed'] === true);
        $this->assertDatabaseHas('whats_app_transaction_admin_logs', ['admin_user_id' => 9, 'old_status' => 'FAILED', 'new_status' => 'COMPLETED', 'note' => 'REFUND RECOVERY: Provider confirmed delivery.']);
    }

    public function test_confirmation_is_required_and_retries_do_not_duplicate_audit_entries(): void
    {
        $this->admin();
        $this->postJson($this->url(), ['note' => 'Provider confirmed delivery.'])->assertUnprocessable();
        Http::assertNothingSent();
        Http::fake(['bot.test/*' => Http::response(['recovery_amount' => '0.00', 'wallet_balance' => '0.00', 'already_recovered' => true])]);
        $this->postJson($this->url(), $this->body())->assertOk()->assertJsonPath('data.already_recovered', true);
        $this->assertSame(0, DB::table('whats_app_transaction_admin_logs')->count());
    }

    public function test_insufficient_balance_error_is_shown_to_the_admin(): void
    {
        $this->admin();
        Http::fake(['bot.test/*' => Http::response(['message' => 'Validation failed.', 'errors' => ['wallet_balance' => ['Insufficient wallet balance.']]], 422)]);
        $this->postJson($this->url(), $this->body())->assertUnprocessable()->assertJsonPath('message', 'Insufficient wallet balance.');
        $this->assertSame(0, DB::table('whats_app_transaction_admin_logs')->count());
    }

    private function admin(): void
    {
        $this->actingAs((new User)->forceFill(['id' => 9, 'role' => 1]), 'api');
    }

    private function url(): string
    {
        return '/api/admin/whatsapp-transactions/order-1/refund-recovery';
    }

    private function body(): array
    {
        return ['note' => 'Provider confirmed delivery.', 'provider_success_confirmed' => true];
    }
}
