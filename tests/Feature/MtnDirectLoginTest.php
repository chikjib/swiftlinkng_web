<?php

namespace Tests\Feature;

use App\Http\Controllers\MtnDirectAppController;
use App\Models\MtnLogin;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class MtnDirectLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->text('value')->nullable();
        });
        DB::table('settings')->insert(['key' => 'SOCIAL', 'value' => '||']);
        Schema::create('mtn_logins', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number')->unique();
            $table->text('mtn_access_token');
            $table->text('mtn_refresh_token');
            $table->integer('expires_in');
            $table->boolean('status')->default(false);
            $table->timestamps();
        });
        Schema::create('mtn_balance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mtn_login_id')->unique();
            $table->decimal('balance', 15, 2);
            $table->timestamps();
        });
    }

    private function credentials(): array
    {
        return ['access_token' => 'test-access', 'refresh_token' => 'test-refresh', 'expires_in' => 3600];
    }

    private function submit($response, string $phone = '+2348000000001')
    {
        $controller = \Mockery::mock(MtnDirectAppController::class)->makePartial();
        $controller->shouldReceive('ValidateOTP')->once()->with('123456', $phone)->andReturn($response);
        return $controller->validateMtnOtp(Request::create('/mtn-direct-app-validate-otp', 'POST', [
            'phone_number' => $phone, 'otp' => '123456',
        ]));
    }

    public function test_sme_and_direct_forms_submit_to_their_own_handlers(): void
    {
        foreach (['mtn_direct_app' => 'mtn-direct-app', 'mtn_sme_app' => 'mtn-sme-app'] as $view => $path) {
            $login = view($view.'.mtn_login_app')->render();
            $otp = view($view.'.verify_otp', ['otp_result' => '', 'phone_number' => '+2348000000001'])->render();
            $this->assertStringContainsString('action="'.url('/'.$path.'-generate-otp').'"', $login);
            $this->assertStringContainsString('action="'.url('/'.$path.'-validate-otp').'"', $otp);
        }
    }

    public function test_success_creates_both_rows_and_does_not_display_tokens(): void
    {
        $view = $this->submit(json_encode($this->credentials()));
        $this->assertSame('mtn_direct_app.mtn_success_login_app', $view->name());
        $this->assertStringNotContainsString('test-access', $view->getData()['response']);
        $login = MtnLogin::sole();
        $this->assertSame('+2348000000001', $login->phone_number);
        $this->assertDatabaseHas('mtn_balance', ['mtn_login_id' => $login->id, 'balance' => 0]);
    }

    public function test_relogin_only_updates_the_submitted_account_and_preserves_balance(): void
    {
        $active = MtnLogin::create(['phone_number' => '+2348000000002', 'mtn_access_token' => 'old-active', 'mtn_refresh_token' => 'refresh-active', 'expires_in' => 1800, 'status' => 1]);
        $this->submit(json_encode($this->credentials()));
        $target = MtnLogin::where('phone_number', '+2348000000001')->sole();
        DB::table('mtn_balance')->where('mtn_login_id', $target->id)->update(['balance' => 125]);
        $this->submit(json_encode(array_replace($this->credentials(), ['access_token' => 'renewed'])));
        $this->assertSame('old-active', $active->fresh()->mtn_access_token);
        $this->assertSame('renewed', $target->fresh()->mtn_access_token);
        $this->assertEquals(0, $target->fresh()->status);
        $this->assertDatabaseCount('mtn_logins', 2);
        $this->assertDatabaseCount('mtn_balance', 1);
        $this->assertDatabaseHas('mtn_balance', ['mtn_login_id' => $target->id, 'balance' => 125]);
    }

    public function test_relogin_repairs_a_missing_balance_row(): void
    {
        $this->submit(json_encode($this->credentials()));
        DB::table('mtn_balance')->delete();
        $this->submit(json_encode($this->credentials()));
        $this->assertDatabaseCount('mtn_logins', 1);
        $this->assertDatabaseHas('mtn_balance', ['mtn_login_id' => MtnLogin::sole()->id, 'balance' => 0]);
    }

    public function test_invalid_provider_responses_do_not_create_records_or_report_success(): void
    {
        foreach ([false, '', 'invalid json', '{"error":"invalid_grant"}', '{"access_token":"partial"}', json_encode(array_replace($this->credentials(), ['expires_in' => 0]))] as $response) {
            $this->assertSame('mtn_direct_app.mtn_failed_login_app', $this->submit($response)->name());
        }
        $this->assertDatabaseCount('mtn_logins', 0);
        $this->assertDatabaseCount('mtn_balance', 0);
    }

    public function test_failed_balance_insert_rolls_back_the_login_insert(): void
    {
        Schema::drop('mtn_balance');
        try {
            $this->submit(json_encode($this->credentials()));
            $this->fail('Expected the missing balance table to reject the save.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertDatabaseCount('mtn_logins', 0);
        }
    }
}
