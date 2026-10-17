<?php

namespace Tests\Support;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, DB, Http, Crypt, Hash};
use Tests\TestCase;
abstract class InternationalApiTestCase extends TestCase
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
        (require database_path('migrations/2026_10_17_000001_add_sochitel_international_provider.php'))->up();
        config(['international.api_key' => 'test-key']);
        Http::preventStrayRequests();
    }
    protected function login(int $role = 0): void
    {
        $u = new User();
        $u->id = 1;
        $u->role = $role;
        $u->userlevel = 1;
        $u->transaction_pin = Hash::make('1234');
        $this->actingAs($u, 'api');
    }
}
