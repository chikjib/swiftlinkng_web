<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, DB, Http, Crypt, Hash};
use Tests\TestCase;
class InternationalApiTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';
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
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->decimal('wallet', 16, 2);
            $t->timestamps();
        });
        foreach (['category', 'subcategory'] as $name) {
            Schema::create($name, function (Blueprint $t) use ($name) {
                $t->id();
                $t->string('title');
                $t->boolean('status');
                if ($name === 'subcategory') {
                    $t->integer('category_id');
                    $t->text('description');
                }
                $t->timestamps();
            });
        }
        Schema::create('service_control_audits', function (Blueprint $t) {
            $t->id();
            $t->integer('admin_id');
            $t->text('before_values');
            $t->text('after_values');
            $t->timestamps();
        });
        (require database_path('migrations/2026_10_11_000001_create_international_topups.php'))->up();
        config(['international.api_key' => 'test-key']);
        Http::preventStrayRequests();
    }
    private function login(int $role = 0): void
    {
        $u = new User();
        $u->id = 1;
        $u->role = $role;
        $u->userlevel = 1;
        $u->transaction_pin = Hash::make('1234');
        $this->actingAs($u, 'api');
    }
    public function test_guests_and_customers_cannot_change_admin_settings(): void
    {
        $this->getJson('/api/admin/international/settings')->assertUnauthorized();
        $this->login();
        $this->putJson('/api/admin/international/settings', [])->assertForbidden();
    }
    public function test_key_is_encrypted_and_never_returned_or_logged(): void
    {
        $this->login(1);
        $this->putJson('/api/admin/international/settings', ['enabled' => true, 'multiplier' => '1.03', 'api_key' => 'private-provider-key', 'calling_codes' => ['GH' => '+233']])->assertOk()->assertJsonPath('data.api_key_configured', true)->assertJsonMissing(['api_key' => 'private-provider-key']);
        $stored = DB::table('international_settings')->value('api_key');
        $this->assertNotSame('private-provider-key', $stored);
        $this->assertSame('private-provider-key', Crypt::decryptString($stored));
        $this->assertStringNotContainsString('private-provider-key', DB::table('service_control_audits')->value('after_values'));
        $this->putJson('/api/admin/international/settings', ['enabled' => true, 'multiplier' => '1.1', 'api_key' => null, 'calling_codes' => []])->assertOk();
        $this->assertSame($stored, DB::table('international_settings')->value('api_key'));
    }
    public function test_multiplier_and_calling_codes_are_validated(): void
    {
        $this->login(1);
        $this->putJson('/api/admin/international/settings', ['enabled' => false, 'multiplier' => 0, 'calling_codes' => ['ghana' => '233']])->assertUnprocessable();
    }
    public function test_customer_purchase_requires_correct_pin(): void
    {
        $this->login();
        $this->postJson('/api/international/purchases', ['quote_id' => '11111111-1111-4111-8111-111111111111'])->assertUnprocessable()->assertJsonPath('code', 'INVALID_TRANSACTION_PIN');
        Http::assertNothingSent();
    }
    public function test_country_request_uses_form_post_and_bearer_token_and_overrides_code(): void
    {
        $this->login();
        DB::table('international_settings')->update(['enabled' => true, 'calling_codes' => '{"GH":"+234"}']);
        Http::fake(['*/countries' => Http::response(['status' => true, 'data' => [['isoName' => 'GH', 'name' => 'Ghana', 'callingCodes' => ['+233']]]])]);
        $this->getJson('/api/international/countries')->assertOk()->assertJsonPath('data.0.calling_code', '+234');
        Http::assertSent(fn($r) => $r->method() === 'POST' && $r->hasHeader('Authorization', 'Bearer test-key') && $r->hasHeader('Content-Type', 'application/x-www-form-urlencoded'));
    }
}
