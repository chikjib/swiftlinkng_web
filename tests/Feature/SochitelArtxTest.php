<?php
namespace Tests\Feature;

use App\Models\{Subcategory, User};
use App\Services\{InternationalTopup, InternetService, Sochitel, SochitelArtx};
use Illuminate\Support\Facades\{Crypt, DB, Http};

class SochitelArtxTest extends InternetServiceTest
{
    private array $sent = [];
    private int $nativePosts = 0;
    private int $status = 9;
    private bool $timeout = false;
    private bool $simulation = false;
    private bool $wrongRecipient = false;
    private bool $missing = false;
    private array $salts = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['international.sochitel_artx_url'=>'https://artx.test/assigned']);
        DB::table('international_settings')->update(['sochitel_protocol'=>'artx','sochitel_username'=>'merchant','sochitel_password'=>Crypt::encryptString('testtest'),'sochitel_currency'=>'NGN','multiplier'=>1.03]);
    }

    private function fakeArtx(): void
    {
        Http::fake(function($r) {
            $this->assertSame('POST',$r->method());
            $this->assertSame('https://artx.test/assigned',$r->url());
            $a=$r['auth']; $salt=$a['salt'];
            $this->assertSame(40,strlen($salt)); $this->assertNotContains($salt,$this->salts); $this->salts[]=$salt;
            $this->assertSame(hash('sha512',$salt.sha1('testtest')),$a['password']);
            $signed=sha1('testtest').$salt.$r['command'];
            foreach (['operator','productId','amount','amountOperator','msisdn','id','userReference'] as $f) if(isset($r[$f])) $signed.=$r[$f];
            $this->assertSame(hash('sha512',$signed),$a['signature']);
            $this->assertSame(5,$r['version']);
            $result=[]; $outer=0;
            switch($r['command']) {
                case 'getBalance': $result=['value'=>10000,'currency'=>'NGN']; break;
                case 'getOperators':
                    $result=['7'=>['id'=>7,'name'=>'Spectranet','country'=>['id'=>'NG','name'=>'Nigeria'],'currency'=>'NGN']]; break;
                case 'getOperatorProducts':
                    $base=['id'=>11,'name'=>'Spectranet 10GB','productType'=>['id'=>3,'name'=>'Bill Payment'],'productCategory'=>['id'=>'6.0','name'=>'Internet services'],'priceType'=>'fixed','price'=>['operator'=>500,'user'=>450]];
                    $mobile=array_replace($base,['id'=>12,'name'=>'Mobile Internet','productType'=>['id'=>4]]);
                    $extra=array_replace($base,['id'=>13,'extraParameters'=>['required'=>['mandatory'=>true]]]);
                    $wrongCategory=array_replace($base,['id'=>14,'productCategory'=>['id'=>'7.0']]);
                    $result=['currency'=>['user'=>'NGN','operator'=>'NGN'],'products'=>['11'=>$base,'12'=>$mobile,'13'=>$extra,'14'=>$wrongCategory]]; break;
                case 'execTransaction':
                    $this->nativePosts++;
                    $this->sent=$r->data();
                    $this->assertArrayNotHasKey('amount',$this->sent);
                    $this->assertSame('500',$r['amountOperator']);
                    $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]{1,30}$/',$r['userReference']);
                    if($this->timeout) throw new \Illuminate\Http\Client\ConnectionException('Timeout after send');
                    $result=$this->transaction(false); $outer=$this->status; break;
                case 'getTransaction':
                    $this->assertSame($this->sent['userReference'],$r['userReference']);
                    if($this->missing) { $outer=22; break; }
                    $result=$this->transaction(true); break;
                default: throw new \RuntimeException('Unexpected command');
            }
            return Http::response(['status'=>['id'=>$outer,'type'=>$outer===0?0:2], 'command'=>$r['command'], 'reference'=>987654, 'result'=>$result]);
        });
    }
    private function transaction(bool $lookup): array
    {
        $kind=isset($this->sent['accountId'])?'accountId':'msisdn';
        $recipient=$this->wrongRecipient?'999999999':$this->sent[$kind];
        $t=['id'=>12345,'userReference'=>strtolower($this->sent['userReference']),'operator'=>['id'=>7],'currency'=>['operator'=>'NGN','user'=>'NGN'],'amount'=>['operator'=>500,'user'=>450]];
        if($lookup) return $t+['status'=>['id'=>$this->status,'type'=>$this->status===0?0:2],'productType'=>['id'=>$kind==='accountId'?3:4],'extraParameters'=>[$kind=>$recipient,'instructions'=>'Activate your bundle in the provider portal.']];
        return $t+[$kind=>$recipient,'productId'=>$this->sent['productId'],'simulation'=>$this->simulation,'instructions'=>'Activate your bundle in the provider portal.'];
    }
    private function artxInput(int $product = 11): array
    {
        return ['subcategory_id'=>Subcategory::where('title','Spectranet')->value('id'),'plan_id'=>'7:'.$product,'amount'=>464,'phonenumber'=>'08011111111','request_key'=>'artx-test','channel'=>'App'];
    }
    public function test_production_endpoint_uses_the_documented_artx_balance_request(): void
    {
        config(['international.sochitel_artx_url' => 'https://artxh1.sochitel.com/apiv8']);
        Http::fake([
            'https://artxh1.sochitel.com/apiv8' => Http::response([
                'command' => 'getBalance', 'status' => ['id' => 0],
                'result' => ['value' => 10000, 'currency' => 'NGN'],
            ]),
        ]);

        $result = app(Sochitel::class)->request(app(InternationalTopup::class)->settings(), 'GET', '/balance');

        $this->assertSame(['errno' => 0, 'balance' => 10000, 'currency' => 'NGN'], $result);
        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            $auth = $request['auth'];
            return $request->url() === 'https://artxh1.sochitel.com/apiv8'
                && $request->method() === 'POST'
                && $request['version'] === 5
                && $request['command'] === 'getBalance'
                && $auth['username'] === 'merchant'
                && strlen($auth['salt']) === 40
                && $auth['password'] === hash('sha512', $auth['salt'].sha1('testtest'))
                && $auth['signature'] === hash('sha512', sha1('testtest').$auth['salt'].'getBalance');
        });
    }
    public function test_catalogue_uses_separate_commands_and_internet_category_and_verifies_auth(): void
    {
        $this->fakeArtx();
        $plans=app(InternetService::class)->providerPlans('spectranet',true);
        $this->assertSame(['7:11','7:12'],array_column($plans,'plan'));
        $this->assertSame('accountId',$plans[0]['payload']['_recipient_kind']);
        $this->assertSame('msisdn',$plans[1]['payload']['_recipient_kind']);
        $this->assertSame(2,app(Sochitel::class)->verify(app(InternationalTopup::class)->settings())['products']);
        Http::assertNotSent(fn($r)=>$r['command']==='execTransaction');
    }
    public function test_bill_account_is_not_normalized_and_lookup_uses_nested_status(): void
    {
        $this->fakeArtx(); $service=app(InternetService::class);
        $row=$service->purchase(User::find(1),$this->artxInput());
        $this->assertSame('pending',$row->status);
        $this->assertSame('08011111111',$this->sent['accountId']);
        $this->assertArrayNotHasKey('msisdn',$this->sent);
        $this->status=59; $row=$service->reconcile($row); $this->assertSame('pending',$row->status);
        $this->status=0; $row=$service->reconcile($row); $this->assertSame('success',$row->status);
        $this->assertStringContainsString('Activate your bundle',DB::table('orders')->value('description'));
        $this->assertEquals(9536,User::find(1)->wallet);
    }
    public function test_mobile_numbers_use_international_msisdn_and_local_amount(): void
    {
        $this->fakeArtx(); $this->status=0;
        $row=app(InternetService::class)->purchase(User::find(1),$this->artxInput(12));
        $this->assertSame('success',$row->status);
        $this->assertSame('2348011111111',$this->sent['msisdn']);
        $this->assertArrayNotHasKey('accountId',$this->sent);
    }
    public function test_timeout_missing_lookup_and_unknown_status_never_resend_or_refund(): void
    {
        $this->fakeArtx(); $this->timeout=true; $service=app(InternetService::class);
        $row=$service->purchase(User::find(1),$this->artxInput());
        $service->purchase(User::find(1),$this->artxInput());
        $this->missing=true; $this->assertSame('pending',$service->reconcile($row)->status);
        $this->missing=false; $this->status=999; $this->assertSame('pending',$service->reconcile($row)->status);
        $this->assertEquals(9536,User::find(1)->wallet);
        $this->status=0; $this->assertSame('success',$service->reconcile($row)->status);
        $this->assertSame(1,$this->nativePosts);
    }
    public function test_confirmed_failure_refunds_once_and_mismatch_and_simulation_do_not_settle(): void
    {
        $this->fakeArtx(); $this->status=0; $this->simulation=true; $service=app(InternetService::class);
        $row=$service->purchase(User::find(1),$this->artxInput());
        $this->assertSame('pending',$row->status);
        $this->wrongRecipient=true; $this->assertSame('pending',$service->reconcile($row)->status);
        $this->wrongRecipient=false; $this->status=24;
        $this->assertSame('reversed',$service->reconcile($row)->status);
        $service->reconcile($row);
        $this->assertEquals(10000,User::find(1)->wallet);
        $this->assertSame(1,DB::table('refund_logs')->count());
    }
    public function test_international_quote_purchase_and_lookup_use_artx_on_the_same_account(): void
    {
        $this->fakeArtx();
        DB::table('international_settings')->update(['enabled'=>true,'provider'=>'sochitel']);
        $service=app(InternationalTopup::class);
        $quote=$service->quote(1,['provider'=>'sochitel','country_code'=>'NG','type'=>'data','operator_id'=>7,'product_id'=>12,'amount'=>'500.00','phone'=>'08011111111']);
        $row=$service->purchase(1,$quote->id);
        $this->assertContains($row->status,['pending','processing']);
        $this->assertSame('2348011111111',$this->sent['msisdn']);
        $this->status=0;
        $row=$service->refresh($row);
        $this->assertSame('success',$row->status);
        $this->assertStringContainsString('Activate your bundle',$service->publicRow($row)['pin_detail']);
        $this->assertSame(1,$this->nativePosts);
    }

    public function test_admin_encrypts_password_and_pending_internet_blocks_protocol_switch(): void
    {
        $this->login(1);
        $body=['enabled'=>false,'provider'=>'vtugate','multiplier'=>'1.03','calling_codes'=>[], 'sochitel_protocol'=>'artx','sochitel_username'=>'merchant','sochitel_password'=>'new-password'];
        $this->putJson('/api/admin/international/settings',$body)->assertOk()->assertJsonMissing(['sochitel_password'=>'new-password']);
        $saved=DB::table('international_settings')->first();
        $this->assertSame('new-password',Crypt::decryptString($saved->sochitel_password));
        $this->assertStringNotContainsString('new-password',DB::table('service_control_audits')->value('after_values'));
        DB::table('international_settings')->update(['sochitel_password'=>Crypt::encryptString('testtest')]);
        $this->fakeArtx(); app(InternetService::class)->purchase(User::find(1),$this->artxInput());
        $body['sochitel_protocol']='rest'; unset($body['sochitel_password']);
        $this->putJson('/api/admin/international/settings',$body)->assertStatus(422);
    }
}
