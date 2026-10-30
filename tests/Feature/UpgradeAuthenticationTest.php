<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, File, Hash, Http, Route, Schema};
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\TestCase;

class UpgradeAuthenticationTest extends TestCase
{
    private const CLIENT_ID = '61ef9610-0cd1-40c7-817e-111111111111';
    private string $keyDirectory;
    private string $privateKey = '';

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->unsignedInteger('status')->default(0);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
        // Use the project's existing OAuth schema, not Passport 13's new schema.
        foreach (['000001_create_oauth_auth_codes_table', '000002_create_oauth_access_tokens_table',
            '000003_create_oauth_refresh_tokens_table', '000004_create_oauth_clients_table',
            '000005_create_oauth_personal_access_clients_table'] as $migration) {
            (require database_path('migrations/2016_06_01_'.$migration.'.php'))->up();
        }
        DB::table('users')->insert([
            'id' => 1, 'email' => 'customer@example.test',
            'password' => Hash::make('test-password'),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('oauth_clients')->insert([
            'id' => self::CLIENT_ID, 'name' => 'Existing personal access client',
            'secret' => 'existing-plaintext-secret', 'provider' => null,
            'redirect' => 'http://localhost', 'personal_access_client' => true,
            'password_client' => false, 'revoked' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->keyDirectory = sys_get_temp_dir().'/swiftlink-auth-test-'.Str::uuid();
        File::makeDirectory($this->keyDirectory, 0700);
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $this->privateKey);
        File::put($this->keyDirectory.'/oauth-private.key', $this->privateKey);
        File::put($this->keyDirectory.'/oauth-public.key', openssl_pkey_get_details($key)['key']);
        chmod($this->keyDirectory.'/oauth-private.key', 0600);
        chmod($this->keyDirectory.'/oauth-public.key', 0660);
        config(['passport.private_key' => null, 'passport.public_key' => null]);
        Passport::loadKeysFrom($this->keyDirectory);
        Route::get('/_upgrade/token', fn (Request $request) => ['id' => $request->user()->id])
            ->middleware('auth:api');
        Route::get('/_upgrade/session', fn (Request $request) => ['id' => $request->user()->id])
            ->middleware(['web', 'auth']);
    }

    protected function tearDown(): void
    {
        if (isset($this->keyDirectory)) {
            File::deleteDirectory($this->keyDirectory);
        }
        Passport::loadKeysFrom(storage_path());
        parent::tearDown();
    }

    public function test_existing_php_serialized_file_session_survives_multiple_requests(): void
    {
        $this->assertSame('php', config('session.serialization'));
        config(['session.driver' => 'file', 'session.files' => $this->keyDirectory]);
        $id = Str::random(40);
        File::put($this->keyDirectory.'/'.$id, serialize([
            '_token' => Str::random(40),
            'login_web_'.sha1(SessionGuard::class) => 1,
        ]));
        $this->withCredentials()->withCookie(config('session.cookie'), $id)
            ->getJson('/_upgrade/session')->assertOk()->assertJsonPath('id', 1);
        Auth::forgetGuards();
        $this->getJson('/_upgrade/session')->assertOk()->assertJsonPath('id', 1);
    }

    public function test_existing_rs256_token_remains_valid_until_revoked(): void
    {
        $id = Str::random(80);
        DB::table('oauth_access_tokens')->insert([
            'id' => $id, 'user_id' => 1, 'client_id' => self::CLIENT_ID,
            'name' => 'Existing mobile login', 'scopes' => '[]', 'revoked' => false,
            'created_at' => now(), 'updated_at' => now(), 'expires_at' => now()->addHour(),
        ]);
        $encode = fn ($value) => rtrim(strtr(base64_encode(json_encode($value)), '+/', '-_'), '=');
        $payload = $encode(['typ' => 'JWT', 'alg' => 'RS256']).'.'.$encode([
            'aud' => self::CLIENT_ID, 'jti' => $id, 'iat' => time() - 60,
            'nbf' => time() - 60, 'exp' => time() + 3600, 'sub' => '1', 'scopes' => [],
        ]);
        openssl_sign($payload, $signature, $this->privateKey, OPENSSL_ALGO_SHA256);
        $token = $payload.'.'.rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
        $this->withToken($token)->getJson('/_upgrade/token')->assertOk()->assertJsonPath('id', 1);
        DB::table('oauth_access_tokens')->where('id', $id)->update(['revoked' => true]);
        Auth::forgetGuards();
        $this->getJson('/_upgrade/token')->assertUnauthorized();
    }

    public function test_new_login_and_logout_preserve_other_devices_tokens(): void
    {
        $this->artisan('passport:hash', ['--force' => true])->assertSuccessful();
        $user = User::findOrFail(1);
        $otherToken = $user->createToken('Other device');
        $response = $this->postJson('/api/login', [
            'email' => $user->email, 'password' => 'test-password', 'remember_me' => false,
        ])->assertOk()->assertJsonPath('success', true);
        $newToken = $response->json('data.token');
        $this->assertNotEmpty($newToken);
        Auth::forgetGuards();
        $this->withToken($newToken)->getJson('/_upgrade/token')->assertOk();
        Auth::forgetGuards();
        $this->postJson('/api/logout')->assertOk();
        Auth::forgetGuards();
        $this->getJson('/_upgrade/token')->assertUnauthorized();
        Auth::forgetGuards();
        $this->withToken($otherToken->accessToken)->getJson('/_upgrade/token')->assertOk();
        $this->assertFalse($otherToken->token->fresh()->revoked);
    }

    public function test_secret_conversion_keeps_client_ids_and_is_repeatable(): void
    {
        $this->artisan('passport:hash', ['--force' => true])->assertSuccessful();
        $secret = DB::table('oauth_clients')->where('id', self::CLIENT_ID)->value('secret');
        $this->assertTrue(Hash::check('existing-plaintext-secret', $secret));
        $this->artisan('passport:hash', ['--force' => true])->assertSuccessful();
        $this->assertSame($secret, DB::table('oauth_clients')->where('id', self::CLIENT_ID)->value('secret'));
        $this->assertSame(1, DB::table('oauth_clients')->count());
    }

    public function test_deployment_check_blocks_plaintext_clients_then_passes_after_conversion(): void
    {
        config(['app.debug' => false, 'session.driver' => 'file', 'session.files' => $this->keyDirectory]);
        $this->artisan('upgrade:check')->assertFailed();
        $this->artisan('passport:hash', ['--force' => true])->assertSuccessful();
        $this->artisan('upgrade:check')->assertSuccessful();
    }

    public function test_existing_password_client_can_issue_and_refresh_tokens_after_secret_conversion(): void
    {
        $clientId = '61ef9610-0cd1-40c7-817e-222222222222';
        DB::table('oauth_clients')->insert([
            'id' => $clientId, 'name' => 'Existing password client',
            'secret' => 'original-client-secret', 'provider' => 'users',
            'redirect' => 'http://localhost', 'personal_access_client' => false,
            'password_client' => true, 'revoked' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->artisan('passport:hash', ['--force' => true])->assertSuccessful();
        $issued = $this->postJson('/oauth/token', [
            'grant_type' => 'password', 'client_id' => $clientId,
            'client_secret' => 'original-client-secret',
            'username' => 'customer@example.test', 'password' => 'test-password',
        ])->assertOk()->assertJsonStructure(['access_token', 'refresh_token']);
        $refreshed = $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token', 'client_id' => $clientId,
            'client_secret' => 'original-client-secret', 'refresh_token' => $issued->json('refresh_token'),
        ])->assertOk()->assertJsonStructure(['access_token', 'refresh_token']);
        $this->withToken($refreshed->json('access_token'))->getJson('/_upgrade/token')->assertOk();
        $this->assertTrue(Route::has('passport.clients.index'));
    }
}
