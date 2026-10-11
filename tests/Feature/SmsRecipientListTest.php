<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SmsRecipientListTest extends TestCase
{
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
        Schema::create('users', function (Blueprint $table) { $table->id(); });
        \Illuminate\Support\Facades\DB::table('users')->insert([['id' => 1], ['id' => 2]]);
        (require database_path('migrations/2026_10_10_000001_create_sms_recipient_lists_table.php'))->up();
    }

    private function loginAs(int $id): void
    {
        $user = new User(); $user->id = $id;
        $this->actingAs($user, 'api');
    }

    public function test_lists_are_saved_explicitly_and_private_to_the_owner(): void
    {
        $this->loginAs(1);
        $this->getJson('/api/sms/recipient-lists')->assertOk()->assertJsonCount(0, 'data');
        $id = $this->postJson('/api/sms/recipient-lists', [
            'name' => 'Customers', 'recipients' => ['08123456789', '+2348123456789', '08012345678'], 'user_id' => 2,
        ])->assertCreated()->assertJsonCount(2, 'data.recipients')->json('data.id');
        $this->getJson('/api/sms/recipient-lists')->assertJsonPath('data.0.name', 'Customers');
        $this->loginAs(2);
        $this->getJson('/api/sms/recipient-lists')->assertJsonCount(0, 'data');
        $this->deleteJson('/api/sms/recipient-lists/'.$id)->assertNotFound();
        $this->loginAs(1);
        $this->deleteJson('/api/sms/recipient-lists/'.$id)->assertNoContent();
        $this->getJson('/api/sms/recipient-lists')->assertJsonCount(0, 'data');
    }

    public function test_invalid_empty_and_oversized_lists_are_rejected(): void
    {
        $this->loginAs(1);
        foreach ([[], ['bad'], array_fill(0, 1001, '08123456789')] as $numbers) {
            $this->postJson('/api/sms/recipient-lists', ['name' => 'Test', 'recipients' => $numbers])->assertUnprocessable();
        }
        $this->postJson('/api/sms/recipient-lists', ['name' => ' ', 'recipients' => ['08123456789']])->assertUnprocessable();
    }

    public function test_names_cannot_overwrite_an_existing_list_and_are_scoped_per_user(): void
    {
        $data = ['name' => 'Customers', 'recipients' => ['08123456789']];
        $this->loginAs(1);
        $this->postJson('/api/sms/recipient-lists', $data)->assertCreated();
        $this->postJson('/api/sms/recipient-lists', $data)->assertUnprocessable();
        $this->loginAs(2);
        $this->postJson('/api/sms/recipient-lists', $data)->assertCreated();
    }

    public function test_guests_cannot_access_lists(): void
    {
        $this->getJson('/api/sms/recipient-lists')->assertUnauthorized();
        $this->postJson('/api/sms/recipient-lists', [])->assertUnauthorized();
        $this->deleteJson('/api/sms/recipient-lists/1')->assertUnauthorized();
    }
}
