<?php
namespace Tests\Unit;
use Illuminate\Container\Container;
use Illuminate\Config\Repository;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
abstract class EsimPurchaseTestCase extends TestCase {
    protected Capsule $db;
    protected function setUp(): void {
        $container = new class extends Container {
            public function abort($code, $message = '', $headers = []) { throw new \Symfony\Component\HttpKernel\Exception\HttpException($code, $message); }
        };
        Container::setInstance($container);
        $container->instance('validator', new \Illuminate\Validation\Factory(new \Illuminate\Translation\Translator(new \Illuminate\Translation\ArrayLoader, 'en'), $container));
        $container->instance('config', new Repository(['betting' => ['subcategory_id' => 1], 'esim_terms' => ['draft'=>'Draft eSIM terms']]));
        $container->instance('cache', new \Illuminate\Cache\Repository(new \Illuminate\Cache\ArrayStore));
        $container->instance('encrypter', new \Illuminate\Encryption\Encrypter(str_repeat('a',32),'AES-256-CBC'));
        $responseFactory=$this->createMock(\Illuminate\Contracts\Routing\ResponseFactory::class);
        $responseFactory->method('json')->willReturnCallback(fn ($data, $status=200) => new \Illuminate\Http\JsonResponse($data,$status));
        $container->instance(\Illuminate\Contracts\Routing\ResponseFactory::class,$responseFactory);
        $this->db = new Capsule($container);
        $this->db->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $this->db->setAsGlobal(); $this->db->bootEloquent();
        $container->instance('db', $this->db->getDatabaseManager());
        $container->bind('db.schema', fn () => $this->db->schema());
        Facade::clearResolvedInstances(); Facade::setFacadeApplication($container);
        $this->db->schema()->create('users', function (Blueprint $t) { $t->id(); $t->decimal('wallet', 16, 2); $t->string('phone')->nullable(); $t->timestamps(); });
        $this->db->schema()->create('category', function (Blueprint $t) { $t->id(); $t->string('title'); $t->boolean('status'); $t->timestamps(); });
        $this->db->schema()->create('subcategory', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('category_id'); $t->string('title'); $t->text('description')->nullable(); $t->boolean('status'); $t->timestamps(); });
        $this->db->schema()->create('orders', function (Blueprint $t) {
            $t->id(); foreach (['ref','plan','phone','channel','description'] as $f) $t->string($f);
            foreach (['user_id','subcategory_id','quantity','status'] as $f) $t->integer($f);
            foreach (['amount','subtotal','total','prev_bal','bal'] as $f) $t->decimal($f,16,2);
            $t->timestamps();
        });
        (require __DIR__.'/../../database/migrations/2026_10_02_000001_create_betting_tables.php')->up();
        $this->db->table('users')->insert(['id'=>1,'wallet'=>10000]);
        (require __DIR__.'/../../database/migrations/2026_10_02_000002_create_esim_and_service_controls.php')->up();
        $this->db->table('service_controls')->update(['betting_enabled'=>1,'esim_enabled'=>1,'usd_ngn'=>'1500.0000','betting_biller_ids'=>'["sportybet"]']);
    }
}


use App\Services\EsimAccess;
use App\Services\EsimPurchases;
use App\Services\ServiceControls;
use Illuminate\Support\Facades\Crypt;

class EsimPurchasesTest extends EsimPurchaseTestCase {
    private function package(): array {return ['packageCode'=>'GB5','name'=>'United Kingdom 5GB','price'=>15000,'currencyCode'=>'USD','volume'=>5368709120,'duration'=>30,'durationUnit'=>'DAY','dataType'=>1,'supportTopUpType'=>2];}
    private function provider(string $outcome='success'): EsimAccess {
        $provider=$this->createMock(EsimAccess::class);
        $provider->method('packages')->willReturn([$this->package()]);
        $provider->method('request')->willReturnCallback(function ($path,$body) use ($outcome) {
            if ($outcome==='timeout') throw new \RuntimeException('network timeout');
            if ($outcome==='failed') return ['success'=>false,'errorCode'=>'200011','obj'=>null];
            return ['success'=>true,'obj'=>['orderNo'=>'ORDER1','transactionId'=>$body['transactionId']]];
        });
        $provider->method('data')->willReturn(['esimList'=>[['orderNo'=>'ORDER1','transactionId'=>'SLE'.str_repeat('a',28),'esimTranNo'=>'ESIM1','iccid'=>'012345','ac'=>'LPA:1$example.test$private-code','qrCodeUrl'=>'https://p.qrsim.net/test.png','esimStatus'=>'GOT_RESOURCE','supportTopUpType'=>2]]]);
        return $provider;
    }
    private function buy(EsimPurchases $service): object {
        $quote=$service->quote(1,['package_code'=>'GB5','location'=>'GB']);
        return $service->purchase(1,$quote->id,'SLE'.str_repeat('a',28));
    }
    public function test_exchange_rate_uses_provider_units_and_rounds_up_to_kobo(): void {
        $this->assertSame(225000,ServiceControls::kobo(15000,'1500.0000'));
        $this->assertSame(16,ServiceControls::kobo(1,'1500.1234'));
        $this->assertSame(1234567,ServiceControls::kobo(100000,'1234.567'));
    }
    public function test_purchase_is_debited_once_and_profile_is_encrypted(): void {
        $provider=$this->provider();$provider->expects($this->once())->method('request');
        $service=new EsimPurchases($provider);
        $row=$this->buy($service);
        $again=$service->purchase(1,$row->quote_id,$row->reference);
        $this->assertSame('success',$row->status);$this->assertSame($row->id,$again->id);
        $this->assertEquals(7750,$this->db->table('users')->value('wallet'));
        $profile=$this->db->table('esim_profiles')->first();
        $this->assertStringNotContainsString('private-code',$profile->payload);
        $this->assertStringContainsString('private-code',Crypt::decryptString($profile->payload));
    }
    public function test_confirmed_unavailable_plan_refunds_exactly_once(): void {
        $service=new EsimPurchases($this->provider('failed'));
        $row=$this->buy($service);$service->refresh($row);
        $this->assertSame('reversed',$row->status);
        $this->assertEquals(10000,$this->db->table('users')->value('wallet'));
        $this->assertEquals(0,$this->db->table('esim_profiles')->count());
    }
    public function test_unknown_result_is_pending_not_refunded(): void {
        $row=$this->buy(new EsimPurchases($this->provider('timeout')));
        $this->assertSame('pending',$row->status);
        $this->assertEquals(7750,$this->db->table('users')->value('wallet'));
    }
    public function test_existing_quote_keeps_exchange_rate_after_admin_changes(): void {
        $service=new EsimPurchases($this->provider());
        $quote=$service->quote(1,['package_code'=>'GB5']);
        $this->db->table('service_controls')->update(['usd_ngn'=>'2000.0000']);
        $next=$service->quote(1,['package_code'=>'GB5']);
        $this->assertEquals(225000,$quote->amount_kobo);$this->assertEquals(300000,$next->amount_kobo);
        $row=$service->purchase(1,$quote->id,'SLE'.str_repeat('a',28));
        $this->assertEquals(225000,$row->amount_kobo);
    }
    public function test_switching_off_prevents_purchase_even_with_valid_quote(): void {
        $service=new EsimPurchases($this->provider());$quote=$service->quote(1,['package_code'=>'GB5']);
        $this->db->table('service_controls')->update(['esim_enabled'=>0]);
        try{$service->purchase(1,$quote->id,'SLE'.str_repeat('a',28));$this->fail('Service switch ignored');}
        catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){$this->assertEquals(10000,$this->db->table('users')->value('wallet'));}
    }
    public function test_another_customer_cannot_read_installation_codes(): void {
        $service=new EsimPurchases($this->provider());$row=$this->buy($service);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $service->profile(2,$row->profile_id);
    }
    public function test_expired_quote_does_not_debit(): void {
        $service=new EsimPurchases($this->provider());$quote=$service->quote(1,['package_code'=>'GB5']);
        $this->db->table('esim_quotes')->update(['expires_at'=>now()->subMinute()]);
        try{$service->purchase(1,$quote->id,'SLE'.str_repeat('a',28));$this->fail('Expired quote accepted');}
        catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){$this->assertEquals(0,$this->db->table('esim_purchases')->count());}
    }
    public function test_topup_timeout_is_not_resubmitted_automatically(): void {
        $service=new EsimPurchases($this->provider());$base=$this->buy($service);
        $provider=$this->provider('timeout');$provider->expects($this->once())->method('request');
        $topupService=new EsimPurchases($provider);
        $quote=$topupService->quote(1,['package_code'=>'GB5','profile_id'=>$base->profile_id]);
        $row=$topupService->purchase(1,$quote->id,'SLE'.str_repeat('b',28));
        $topupService->refresh($row);
        $this->assertSame('pending',$row->status);$this->assertEquals(5500,$this->db->table('users')->value('wallet'));
    }

    public function test_separate_payments_can_proceed_while_another_is_pending(): void {
        $service=new EsimPurchases($this->provider('timeout'));
        $first=$this->buy($service);
        $quote=$service->quote(1,['package_code'=>'GB5','location'=>'GB']);
        $second=$service->purchase(1,$quote->id,'SLE'.str_repeat('b',28));
        $this->assertSame('pending',$first->status);
        $this->assertSame('pending',$second->status);
        $this->assertNotEquals($first->id,$second->id);
        $this->assertEquals(5500,$this->db->table('users')->value('wallet'));
        $service->purchase(1,$first->quote_id,$first->reference);
        $service->purchase(1,$second->quote_id,$second->reference);
        $this->assertEquals(2,$this->db->table('esim_purchases')->count());
        $this->assertEquals(5500,$this->db->table('users')->value('wallet'));
    }
    public function test_new_purchase_still_requires_sufficient_remaining_balance(): void {
        $service=new EsimPurchases($this->provider('timeout'));
        $this->buy($service);
        $this->db->table('users')->update(['wallet'=>100]);
        $quote=$service->quote(1,['package_code'=>'GB5']);
        try {
            $service->purchase(1,$quote->id,'SLE'.str_repeat('b',28));
            $this->fail('Insufficient balance accepted');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertEquals(1,$this->db->table('esim_purchases')->count());
            $this->assertEquals(100,$this->db->table('users')->value('wallet'));
        }
    }
}
