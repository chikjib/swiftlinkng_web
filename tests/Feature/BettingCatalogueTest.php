<?php

namespace Tests\Feature;

use App\Services\PayVesselBetting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Http, Schema};
use Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BettingCatalogueTest extends TestCase
{
    private array $catalogue = [
        ['biller_id' => 2269, 'biller_name' => 'Bet9ja', 'status' => 1],
        ['biller_id' => 65539491345190, 'biller_name' => 'Other betting', 'status' => 0],
        ['biller_id' => 999, 'biller_name' => 'Unselected biller', 'status' => 1],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        config(['betting.api_key' => 'test-key', 'betting.api_secret' => 'test-secret', 'betting.base_url' => 'https://provider.test']);
        Schema::create('service_controls', function (Blueprint $table) {
            $table->id();
            $table->boolean('betting_enabled');
            $table->text('betting_biller_ids');
        });
        DB::table('service_controls')->insert(['id' => 1, 'betting_enabled' => true, 'betting_biller_ids' => '["2269","65539491345190"]']);
        Http::preventStrayRequests();
        Http::fake([
            '*/billers' => fn () => Http::response(['status' => true, 'data' => $this->catalogue]),
            '*/billers/2269/items' => Http::response(['status' => true, 'data' => [
                ['item_id' => 12345, 'item_name' => 'Fund wallet'],
            ]]),
        ]);
    }

    public function test_numeric_provider_ids_match_text_admin_selections_and_exclude_disabled_billers(): void
    {
        $provider = app(PayVesselBetting::class);
        $this->assertSame([['biller_id' => '2269', 'biller_name' => 'Bet9ja', 'status' => 1]], $provider->billers());
        $this->assertSame('2269', $provider->biller('2269')['biller_id']);
        $this->assertSame('12345', $provider->items('2269')[0]['item_id']);
    }

    public function test_legacy_numeric_admin_selections_match_provider_ids(): void
    {
        DB::table('service_controls')->where('id', 1)->update(['betting_biller_ids' => '[2269]']);
        $this->assertSame('2269', app(PayVesselBetting::class)->billers()[0]['biller_id']);
    }

    public function test_stale_admin_selection_reports_a_configuration_problem(): void
    {
        DB::table('service_controls')->where('id', 1)->update(['betting_biller_ids' => '["old-id"]']);
        $this->assertUnavailable('Betting platforms need to be refreshed by Swiftlink support. Please try again later.');
    }

    public function test_disabled_selected_billers_report_provider_unavailability(): void
    {
        DB::table('service_controls')->where('id', 1)->update(['betting_biller_ids' => '["65539491345190"]']);
        $this->assertUnavailable('The selected betting platforms are temporarily unavailable from the provider. Please try again later.');
    }

    public function test_empty_provider_catalogue_reports_no_platforms(): void
    {
        $this->catalogue = [];
        $this->assertUnavailable('The betting provider returned no platforms. Please try again shortly or contact Swiftlink support.');
    }

    private function assertUnavailable(string $message): void
    {
        try {
            app(PayVesselBetting::class)->billers();
            $this->fail('An unavailable catalogue must return a retryable error.');
        } catch (HttpException $e) {
            $this->assertSame(503, $e->getStatusCode());
            $this->assertSame($message, $e->getMessage());
        }
    }
}
