<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AtmFundingTest extends TestCase
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
        config(['app.monnifyBaseUrl'=>'https://monnify.test','app.monnifyAPIKey'=>'test-key',
            'app.monnifySecretKey'=>'test-secret','app.monnifyContractCode'=>'test-contract']);
        Http::preventStrayRequests();
    }
    private function login(): void
    {
        $user=new User();$user->id=1;$user->first_name='Test';$user->last_name='Customer';$user->email='customer@example.com';
        $this->actingAs($user,'api');
    }
    private function gateway(array $response, int $status=200): void
    {
        Http::fake([
            'https://monnify.test/api/v1/auth/login' => Http::response(['requestSuccessful'=>true,'responseBody'=>['accessToken'=>'token']]),
            'https://monnify.test/api/v1/merchant/transactions/init-transaction' => Http::response($response,$status),
        ]);
    }
    public function test_atm_funding_returns_checkout_url_and_card_payment_request(): void
    {
        $this->login();$this->gateway(['requestSuccessful'=>true,'responseBody'=>['transactionReference'=>'MNFY|TEST','checkoutUrl'=>'https://checkout.monnify.com/test']]);
        $this->postJson('/api/generate-payment-link',['amount'=>1000])->assertOk()->assertJsonPath('data.responseBody.checkoutUrl','https://checkout.monnify.com/test');
        Http::assertSent(fn($r)=>str_ends_with($r->url(),'/init-transaction') && $r['amount']==1000 && $r['paymentMethods']===['CARD'] && $r['customerEmail']==='customer@example.com' && $r['redirectUrl']==='https://swiftlinkng.com/dashboard/callback' && $r->hasHeader('Authorization','Bearer token'));
    }
    /** @dataProvider invalidAmounts */
    public function test_invalid_amount_is_rejected_without_gateway_call($amount): void
    {
        $this->login();$this->postJson('/api/generate-payment-link',['amount'=>$amount])->assertUnprocessable();Http::assertNothingSent();
    }
    public function invalidAmounts(): array {return [[null],[0],[-10],[99],['invalid']];}
    public function test_provider_rejection_is_reported_as_failure(): void
    {
        $this->login();$this->gateway(['requestSuccessful'=>false,'responseMessage'=>'Card funding unavailable']);
        $this->postJson('/api/generate-payment-link',['amount'=>1000])->assertStatus(502)->assertJsonPath('success',false);
    }
    public function test_authentication_failure_returns_a_recoverable_error(): void
    {
        $this->login();Http::fake(['*'=>Http::response(['requestSuccessful'=>false],401)]);
        $this->postJson('/api/generate-payment-link',['amount'=>1000])->assertStatus(502)->assertJsonPath('success',false);
        Http::assertSentCount(1);
    }
    public function test_checkout_missing_from_success_response_is_not_accepted(): void
    {
        $this->login();$this->gateway(['requestSuccessful'=>true,'responseBody'=>[]]);
        $this->postJson('/api/generate-payment-link',['amount'=>1000])->assertStatus(502);
    }
    public function test_guest_cannot_initialize_payment(): void
    {
        $this->postJson('/api/generate-payment-link',['amount'=>1000])->assertUnauthorized();Http::assertNothingSent();
    }
    public function test_callback_verifies_the_payment_reference_from_url(): void
    {
        $this->login();Http::fake([
            'https://monnify.test/api/v1/auth/login'=>Http::response(['requestSuccessful'=>true,'responseBody'=>['accessToken'=>'token']]),
            'https://monnify.test/api/v2/merchant/transactions/query*'=>Http::response(['requestSuccessful'=>true,'responseBody'=>['paymentStatus'=>'PAID']]),
        ]);
        $this->getJson('/api/verify-payment/2813922764?reference_type=paymentReference')->assertOk()->assertJsonPath('data.responseBody.paymentStatus','PAID');
        Http::assertSent(fn($r)=>str_contains($r->url(),'paymentReference=2813922764'));
    }
}
