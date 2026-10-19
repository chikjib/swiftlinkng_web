<?php
namespace Tests\Feature;

use App\Models\{User, Subcategory};
use App\Services\{InternetService, OpayDigitalWalletService};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Http, Schema, Crypt};
use Tests\Support\SochitelFixtures as F;

class InternetServiceTest extends \Tests\Support\InternationalApiTestCase
{
    private int $posts = 0;
    protected function setUp(): void
    {
        parent::setUp();
        Schema::table('subcategory', function(Blueprint $t) { $t->text('products')->nullable(); });
        Schema::table('users', function(Blueprint $t) { $t->string('phone')->nullable(); });
        (require database_path('migrations/2026_10_18_000001_add_internet_service.php'))->up();
        (require database_path('migrations/2026_10_18_000002_add_internet_admin_controls.php'))->up();
        Schema::create('orders', function(Blueprint $t) {
            $t->id();
            foreach (['ref','custom_reference','plan','phone','channel','description','api_route','response'] as $f) $t->text($f)->nullable();
            foreach (['user_id','subcategory_id','quantity','status'] as $f) $t->integer($f);
            foreach (['amount','subtotal','total','prev_bal','bal','cost_price'] as $f) $t->decimal($f,16,2);
            $t->timestamps();
        });
        Schema::create('refund_logs', function(Blueprint $t) { $t->id(); $t->string('ref'); $t->integer('order_id'); $t->integer('user_id'); $t->decimal('credited_amount',16,2); $t->timestamps(); });
        DB::table('users')->insert(['id'=>1,'wallet'=>10000,'phone'=>'08012345678']);
        config(['app.vtPassBaseUrl'=>'https://vtpass.test/api']);
    }
    protected function input(): array
    {
        return ['subcategory_id'=>Subcategory::where('title','Smile')->value('id'),'plan_id'=>'123','amount'=>1000,'phonenumber'=>'08011111111','request_key'=>'internet-test-1','channel'=>'App'];
    }
    protected function smileFake(string $status = 'delivered', bool $timeout = false): void
    {
        Http::fake(function($r) use($status,$timeout) {
            if (str_contains($r->url(),'service-variations')) return Http::response(['content'=>['variations'=>[['name'=>'Smile 10GB','variation_code'=>'123','variation_amount'=>'1000.00','fixedPrice'=>'Yes']]]]);
            $this->posts++;
            if ($timeout) throw new \Illuminate\Http\Client\ConnectionException('timeout');
            return Http::response(['code'=>'000','content'=>['transactions'=>['status'=>$status]]]);
        });
    }
    public function test_smile_timeout_retry_never_resends_or_debits_twice(): void
    {
        $this->smileFake('delivered',true);
        $service=app(InternetService::class); $user=User::find(1);
        $first=$service->purchase($user,$this->input());
        $again=$service->purchase($user,$this->input());
        $this->assertSame('pending',$first->status); $this->assertSame($first->id,$again->id);
        $this->assertEquals(9000,User::find(1)->wallet);
        $this->assertSame(1,$this->posts);
    }
    public function test_confirmed_smile_purchase_records_success(): void
    {
        $this->smileFake();
        $row=app(InternetService::class)->purchase(User::find(1),$this->input());
        $this->assertSame('success',$row->status);
        $this->assertEquals(9000,User::find(1)->wallet);
        Http::assertSent(fn($r)=>str_ends_with($r->url(),'/pay') && $r['serviceID']==='smile-direct' && $r['billersCode']==='08011111111');
    }
    public function test_changed_price_is_rejected_before_debit(): void
    {
        $this->smileFake();
        try { app(InternetService::class)->purchase(User::find(1),array_replace($this->input(),['amount'=>1])); $this->fail('Expected price rejection'); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { $this->assertSame(422,$e->getStatusCode()); }
        $this->assertEquals(10000,User::find(1)->wallet); $this->assertSame(0,DB::table('orders')->count());
        Http::assertSentCount(1);
    }
    public function test_failure_refunds_once_even_when_reconciled_again(): void
    {
        $this->smileFake('failed');
        $service=app(InternetService::class); $row=$service->purchase(User::find(1),$this->input());
        $this->assertSame('reversed',$row->status);
        $service->record($row,['code'=>'000','content'=>['transactions'=>['status'=>'failed']]]);
        $this->assertEquals(10000,User::find(1)->wallet); $this->assertSame(1,DB::table('refund_logs')->count());
    }
    public function test_sochitel_domestic_catalogue_keeps_spectranet_and_nitel_separate(): void
    {
        config(['international.sochitel_base_url'=>'https://sochitel.test/api']);
        DB::table('international_settings')->update(['sochitel_user_id'=>'123','sochitel_private_key'=>Crypt::encryptString(F::keys()['private']),'sochitel_currency'=>'NGN','multiplier'=>1.03]);
        $o=F::operators()[1]; $o['name']='Spectranet'; $o['products'][0]['amount']['min']['user']='450'; $o['products'][0]['amount']['max']['user']='450';
        $ntel=$o; $ntel['id']='9'; $ntel['name']='ntel';
        Http::fake(['*'=>Http::response(['errno'=>0,'operators'=>[$o,$ntel]])]);
        $service=app(InternetService::class);
        $this->assertSame('8',$service->providerPlans('spectranet')[0]['payload']['operator']);
        $this->assertSame('9',$service->providerPlans('nitel')[0]['payload']['operator']);
        $this->assertSame(464,$service->providerPlans('nitel')[0]['amount']);
    }
    public function test_sochitel_response_must_match_the_original_purchase_before_settling(): void
    {
        $this->smileFake('pending');
        $service=app(InternetService::class); $row=$service->purchase(User::find(1),$this->input());
        $payload=['recipient'=>'08011111111','product'=>'31','operator'=>'8','currency'=>'NGN','amount'=>'500'];
        DB::table('internet_purchases')->where('id',$row->id)->update(['provider'=>'sochitel','payload'=>json_encode($payload)]);
        $row=DB::table('internet_purchases')->find($row->id);
        $response=['id'=>'500','errno'=>0,'reference'=>$row->reference,'recipient'=>'08011111111','product'=>'31','operator'=>['id'=>'8','currency'=>'NGN'],'amount'=>['operator'=>'500'],'status'=>0];
        $bad=$response; $bad['reference']='different-reference';
        $this->assertSame('pending',$service->record($row,$bad)->status);
        $this->assertSame('success',$service->record($row,$response)->status);
        $this->assertEquals(9000,User::find(1)->wallet);
    }
    public function test_internet_purchase_requires_the_transaction_pin(): void
    {
        $this->login();
        $this->postJson('/api/purchase/internet',$this->input())->assertStatus(422);
        $this->assertSame(0,DB::table('orders')->count());
    }
    public function test_catalogue_only_includes_fibre_x_from_autosync(): void
    {
        $this->smileFake();
        Subcategory::create(['category_id'=>1,'title'=>'MTN Data','description'=>'AUTOSYNCAWUF','status'=>1,'products'=>json_encode([['plan'=>'MTN Fibre X 50GB','amount1'=>5000,'amount2'=>5000],['plan'=>'MTN SME 1GB','amount1'=>500,'amount2'=>500]])]);
        $user=User::find(1); $user->userlevel=1;
        $catalogue=app(InternetService::class)->catalogue($user);
        $fibre=collect($catalogue)->firstWhere('network','fibrex');
        $this->assertCount(1,$fibre['products']); $this->assertSame('MTN Fibre X 50GB',$fibre['products'][0]['plan']);
    }
}
