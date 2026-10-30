<?php

namespace Tests\Feature;

use App\AppConstants;
use App\Traits\IntegrationsTrait;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpgradeConfigurationTest extends TestCase
{
    public function test_legacy_sim_server_requery_reads_runtime_configuration(): void
    {
        config(['app.simServerBaseUrl' => 'https://sim.example.test/api', 'app.simServerApiKey' => 'test-key']);
        Http::preventStrayRequests();
        Http::fake(['https://sim.example.test/api' => Http::response(['status' => 'delivered'])]);
        $integration = new class { use IntegrationsTrait; };
        $this->assertSame('test-key', AppConstants::get('simServerApiKey'));
        $this->assertSame(['status' => 'delivered'], $integration->SIMServerRequery('order-123'));
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request['api_key'] === 'test-key' && $request['order_id'] === 'order-123');
    }
}
