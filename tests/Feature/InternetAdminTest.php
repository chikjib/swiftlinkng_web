<?php

namespace Tests\Feature;

use App\Models\{Subcategory, User};
use App\Services\{InternetAdmin, InternetService};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Http, Schema};

class InternetAdminTest extends InternetServiceTest
{
    private function replaceHttp(array $responses): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        Http::fake($responses);
    }

    protected function smileFake(string $status = 'delivered', bool $timeout = false): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        parent::smileFake($status, $timeout);
    }

    private function root(string $provider = 'smile'): string
    {
        return '/api/admin/internet/providers/'.$provider;
    }

    public function test_every_admin_endpoint_rejects_customer_access(): void
    {
        $this->login();
        $this->getJson('/api/admin/internet/providers')->assertForbidden();
        $this->getJson($this->root().'/packages')->assertForbidden();
        $this->putJson($this->root(), ['enabled' => false])->assertForbidden();
        $this->putJson($this->root().'/packages/1', ['enabled' => false])->assertForbidden();
        $this->postJson($this->root().'/sync')->assertForbidden();
        $this->postJson($this->root().'/test')->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_sync_retains_switches_and_overrides_when_provider_renames_a_package(): void
    {
        $this->login(1);
        $this->smileFake();
        $this->postJson($this->root().'/sync')->assertOk();
        $row = DB::table('internet_packages')->first();
        $this->putJson($this->root().'/packages/'.$row->id, ['enabled' => false, 'price_override' => 1400])->assertOk();
        $this->replaceHttp(['*' => Http::response(['content' => ['variations' => [['name' => 'Renamed Smile bundle', 'variation_code' => '123', 'variation_amount' => 1000]]]])]);
        $this->postJson($this->root().'/sync')->assertOk();
        $row = DB::table('internet_packages')->first();
        $this->assertSame('Renamed Smile bundle', $row->name);
        $this->assertEquals(0, $row->enabled);
        $this->assertEquals(1400, $row->price_override);
        $this->assertSame(1, DB::table('internet_packages')->count());
        $this->assertGreaterThanOrEqual(3, DB::table('service_control_audits')->count());
    }

    public function test_provider_pricing_is_used_in_customer_catalogue_and_purchase(): void
    {
        $this->login(1);
        $this->smileFake();
        $this->putJson($this->root(), ['enabled' => true, 'multiplier' => 1.2, 'fixed_fee' => 50])->assertOk();
        $service = app(InternetService::class);
        $catalogue = $service->catalogue(User::find(1));
        $smile = collect($catalogue)->firstWhere('network', 'smile');
        $this->assertSame(1250, $smile['products'][0]['amount']);
        $row = $service->purchase(User::find(1), array_replace($this->input(), ['amount' => 1250]));
        $this->assertSame('success', $row->status);
        $this->assertEquals(8750, User::find(1)->wallet);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/pay') && $request['amount'] == 1000);
    }

    public function test_provider_enable_also_restores_a_legacy_disabled_source_product(): void
    {
        $this->login(1);
        Subcategory::where('description', 'INTERNET_SMILE')->update(['status' => false]);
        $this->getJson('/api/admin/internet/providers')->assertOk()->assertJsonPath('data.1.enabled', false);
        $this->putJson($this->root(), ['enabled' => true, 'multiplier' => null, 'fixed_fee' => 0])->assertOk();
        $this->assertEquals(1, Subcategory::where('description', 'INTERNET_SMILE')->value('status'));
    }

    public function test_provider_prices_are_independent(): void
    {
        $this->login(1);
        $this->putJson($this->root('spectranet'), ['enabled' => true, 'multiplier' => 1.1, 'fixed_fee' => 10])->assertOk();
        $admin = app(InternetAdmin::class);
        $plan = ['plan' => '8:31', 'amount' => 1030, 'cost' => 1000];
        $this->assertSame(1110, $admin->apply('spectranet', [$plan])[0]['amount']);
        $this->assertSame(1030, $admin->apply('nitel', [$plan])[0]['amount']);
    }

    public function test_pending_purchase_retains_original_price_after_admin_changes(): void
    {
        $this->smileFake('pending');
        $service = app(InternetService::class);
        $row = $service->purchase(User::find(1), $this->input());
        $this->assertSame('pending', $row->status);
        $this->login(1);
        $this->putJson($this->root(), ['enabled' => false, 'multiplier' => 2, 'fixed_fee' => 100])->assertOk();
        $row = $service->record($row, ['code' => '000', 'content' => ['transactions' => ['status' => 'delivered']]]);
        $this->assertSame('success', $row->status);
        $this->assertEquals(1000, DB::table('orders')->where('id', $row->order_id)->value('amount'));
        $this->assertEquals(9000, User::find(1)->wallet);
    }

    public function test_package_override_wins_over_provider_markup_and_fee(): void
    {
        $this->login(1);
        $this->smileFake();
        $this->postJson($this->root().'/sync')->assertOk();
        $this->putJson($this->root(), ['enabled' => true, 'multiplier' => 1.2, 'fixed_fee' => 50])->assertOk();
        $id = DB::table('internet_packages')->value('id');
        $this->putJson($this->root().'/packages/'.$id, ['enabled' => true, 'price_override' => 1500])->assertOk()->assertJsonPath('data.0.selling_price', 1500);
        $this->putJson($this->root().'/packages/'.$id, ['enabled' => true, 'price_override' => 900])->assertStatus(422);
        $this->assertEquals(1500, DB::table('internet_packages')->value('price_override'));
    }

    public function test_disabled_package_cannot_be_bought_from_a_stale_customer_screen(): void
    {
        $this->smileFake();
        app(InternetAdmin::class)->sync('smile', 1);
        DB::table('internet_packages')->update(['enabled' => false]);
        $this->login();
        $this->postJson('/api/purchase/internet', $this->input(), ['X-Transaction-Pin' => '1234'])->assertStatus(422);
        $this->assertEquals(10000, User::find(1)->wallet);
        $this->assertSame(0, DB::table('orders')->count());
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }

    public function test_failed_sync_preserves_previous_catalogue_and_overrides(): void
    {
        $this->login(1);
        $this->smileFake();
        $this->postJson($this->root().'/sync')->assertOk();
        DB::table('internet_packages')->update(['price_override' => 1400]);
        $syncedAt = DB::table('internet_provider_settings')->where('provider', 'smile')->value('synced_at');
        $this->replaceHttp(['*' => Http::response(['error' => 'unavailable'], 500)]);
        $this->postJson($this->root().'/sync')->assertStatus(502);
        $row = DB::table('internet_packages')->first();
        $this->assertEquals(1, $row->available);
        $this->assertEquals(1400, $row->price_override);
        $this->assertSame($syncedAt, DB::table('internet_provider_settings')->where('provider', 'smile')->value('synced_at'));
    }

    public function test_removed_package_is_unavailable_but_keeps_its_saved_controls(): void
    {
        $this->smileFake();
        $admin = app(InternetAdmin::class);
        $admin->sync('smile', 1);
        DB::table('internet_packages')->update(['enabled' => false, 'price_override' => 1400]);
        $this->replaceHttp(['*' => Http::response(['content' => ['variations' => []]])]);
        $admin->sync('smile', 1);
        $this->assertEquals(0, DB::table('internet_packages')->value('available'));
        $this->smileFake();
        $admin->sync('smile', 1);
        $row = DB::table('internet_packages')->first();
        $this->assertEquals(1, $row->available);
        $this->assertEquals(0, $row->enabled);
        $this->assertEquals(1400, $row->price_override);
    }

    public function test_new_provider_packages_wait_for_the_next_admin_sync(): void
    {
        $this->smileFake();
        $admin = app(InternetAdmin::class);
        $admin->sync('smile', 1);
        $this->replaceHttp(['*' => Http::response(['content' => ['variations' => [
            ['name' => 'Smile 10GB', 'variation_code' => '123', 'variation_amount' => 1000],
            ['name' => 'Smile 20GB', 'variation_code' => '456', 'variation_amount' => 2000],
        ]]])]);
        $raw = app(InternetService::class)->providerPlans('smile', true);
        $this->assertCount(1, $admin->apply('smile', $raw));
        $admin->sync('smile', 1);
        $this->assertCount(2, $admin->apply('smile', $raw));
    }

    public function test_fibre_controls_also_apply_to_the_original_data_catalogue_and_endpoint(): void
    {
        $this->login(1);
        $source = Subcategory::create(['category_id' => 1, 'title' => 'MTN Data', 'description' => 'AUTOSYNCAWUF', 'status' => 1, 'products' => json_encode([
            ['plan' => 'Fibre X 50GB', 'amount1' => 5000, 'amount2' => 4500, 'cost_price' => 4000],
            ['plan' => 'MTN SME 1GB', 'amount1' => 500, 'amount2' => 450],
        ])]);
        app(InternetAdmin::class)->sync('fibrex', 1);
        $id = DB::table('internet_packages')->value('id');
        $this->putJson($this->root('fibrex').'/packages/'.$id, ['enabled' => true, 'price_override' => 5500])->assertOk();
        $this->assertEquals(5500, $source->fresh()->plan->first()['amount']);
        $this->putJson($this->root('fibrex').'/packages/'.$id, ['enabled' => false, 'price_override' => 5500])->assertOk();
        $plans = $source->fresh()->plan;
        $this->assertCount(1, $plans);
        $this->assertSame('MTN SME 1GB', $plans->first()['plan']);
        Schema::create('bucket', function (Blueprint $table) { $table->id(); $table->string('title'); $table->boolean('status'); });
        $this->login();
        $this->postJson('/api/purchase/data', ['subcategory_id' => $source->id, 'plan_id' => 'Fibre X 50GB', 'phonenumber' => '08012345678', 'amount' => 5500], ['X-Transaction-Pin' => '1234'])->assertStatus(422);
        $this->assertEquals(10000, User::find(1)->wallet);
    }

    public function test_provider_switch_stops_all_packages_and_invalid_markup_is_rejected(): void
    {
        $this->login(1);
        $this->smileFake();
        $this->putJson($this->root(), ['enabled' => true, 'multiplier' => 0.5, 'fixed_fee' => 0])->assertStatus(422);
        $this->putJson($this->root(), ['enabled' => false, 'multiplier' => null, 'fixed_fee' => 0])->assertOk();
        $this->assertSame([], app(InternetAdmin::class)->apply('smile', app(InternetService::class)->providerPlans('smile')));
    }

    public function test_connection_tests_are_read_only_and_record_success_or_failure(): void
    {
        $this->login(1);
        $this->smileFake();
        $this->postJson($this->root().'/test')->assertOk()->assertJsonPath('status', 'success');
        config(['app.autoSyncUrl' => 'https://autosync.test/api/v1/', 'app.autoSyncToken' => 'test-token']);
        $this->replaceHttp(['https://autosync.test/api/v1/me' => Http::response(['status' => 'ok', 'data' => []])]);
        $this->postJson($this->root('fibrex').'/test')->assertOk();
        Http::assertSent(fn ($request) => $request->url() === 'https://autosync.test/api/v1/me' && $request->method() === 'GET');
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
        $this->replaceHttp(['*' => Http::response([], 403)]);
        $this->postJson($this->root('fibrex').'/test')->assertStatus(502);
        $this->assertSame('failed', DB::table('internet_provider_settings')->where('provider', 'fibrex')->value('test_status'));
    }

    public function test_admin_package_response_omits_internal_provider_payload(): void
    {
        $this->login(1);
        $this->smileFake();
        $this->postJson($this->root().'/sync')->assertOk();
        $this->getJson($this->root().'/packages')->assertOk()->assertJsonMissingPath('data.0.snapshot')->assertJsonMissingPath('data.0.payload')->assertJsonMissingPath('data.0.code');
    }
}
