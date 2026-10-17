<?php

namespace Tests\Unit;

use Illuminate\Container\Container;
use Illuminate\Config\Repository;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
abstract class InternationalPurchaseTestCase extends TestCase
{
    protected Capsule $db;
    protected function setUp(): void
    {
        $container = new class extends Container
        {
            public function abort($code, $message = '', $headers = [])
            {
                throw new \Symfony\Component\HttpKernel\Exception\HttpException($code, $message);
            }
        };
        Container::setInstance($container);
        $container->instance('validator', new \Illuminate\Validation\Factory(new \Illuminate\Translation\Translator(new \Illuminate\Translation\ArrayLoader(), 'en'), $container));
        $container->instance('config', new Repository(['betting' => ['subcategory_id' => 1], 'esim_terms' => ['draft' => 'Draft eSIM terms']]));
        $container->instance('cache', new \Illuminate\Cache\Repository(new \Illuminate\Cache\ArrayStore()));
        $container->instance('encrypter', new \Illuminate\Encryption\Encrypter(str_repeat('a', 32), 'AES-256-CBC'));
        $responseFactory = $this->createMock(\Illuminate\Contracts\Routing\ResponseFactory::class);
        $responseFactory->method('json')->willReturnCallback(fn($data, $status = 200) => new \Illuminate\Http\JsonResponse($data, $status));
        $container->instance(\Illuminate\Contracts\Routing\ResponseFactory::class, $responseFactory);
        $this->db = new Capsule($container);
        $this->db->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $this->db->setAsGlobal();
        $this->db->bootEloquent();
        $container->instance('db', $this->db->getDatabaseManager());
        $container->bind('db.schema', fn() => $this->db->schema());
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($container);
        $this->db->schema()->create('users', function (Blueprint $t) {
            $t->id();
            $t->decimal('wallet', 16, 2);
            $t->string('phone')->nullable();
            $t->timestamps();
        });
        $this->db->schema()->create('category', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->boolean('status');
            $t->timestamps();
        });
        $this->db->schema()->create('subcategory', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('category_id');
            $t->string('title');
            $t->text('description')->nullable();
            $t->boolean('status');
            $t->timestamps();
        });
        $this->db->schema()->create('orders', function (Blueprint $t) {
            $t->id();
            foreach (['ref', 'plan', 'phone', 'channel', 'description'] as $f) {
                $t->string($f);
            }
            foreach (['user_id', 'subcategory_id', 'quantity', 'status'] as $f) {
                $t->integer($f);
            }
            foreach (['amount', 'subtotal', 'total', 'prev_bal', 'bal'] as $f) {
                $t->decimal($f, 16, 2);
            }
            $t->timestamps();
        });
        (require __DIR__ . '/../../database/migrations/2026_10_02_000001_create_betting_tables.php')->up();
        $this->db->table('users')->insert(['id' => 1, 'wallet' => 10000]);
        (require __DIR__ . '/../../database/migrations/2026_10_02_000002_create_esim_and_service_controls.php')->up();
        $this->db->table('service_controls')->update(['betting_enabled' => 1, 'esim_enabled' => 1, 'usd_ngn' => '1500.0000', 'betting_biller_ids' => '["sportybet"]']);
    }
}
class FakeInternational extends \App\Services\InternationalTopup
{
    public ?array $operatorOverrides = null;
    public int $sent = 0;
    public float $cost = 6000;
    public bool $timeout = false;
    public string $outcome = 'SUCCESSFUL';
    public function request(string $path, array $body = []): array
    {
        if ($path === 'countries') {
            return ['status' => true, 'data' => [['isoName' => 'GH', 'name' => 'Ghana', 'callingCodes' => ['+233']]]];
        }
        if ($path === 'operators') {
            if ($this->operatorOverrides !== null) return ['status' => true, 'data' => $this->operatorOverrides];
            return ['status' => true, 'data' => [['operatorId' => 7, 'logoUrls' => ['https://logos.test/'. $body['country_code'].'/data.svg', 'https://logos.test/'. $body['country_code'].'/data.png'], 'name' => 'Ghana Data', 'data' => true, 'bundle' => false, 'denominationType' => 'FIXED', 'localFixedAmounts' => [50], 'localFixedAmountsDescriptions' => ['50' => '5GB 30 days'], 'destinationCurrencyCode' => 'GHS'], ['operatorId' => 8, 'logoUrls' => ['https://logos.test/'. $body['country_code'].'/airtime.svg'], 'name' => 'Ghana Airtime', 'data' => false, 'bundle' => false, 'denominationType' => 'RANGE', 'localMinAmount' => 1, 'localMaxAmount' => 100, 'destinationCurrencyCode' => 'GHS']]];
        }
        if ($path === 'fxrate') {
            return ['status' => true, 'data' => ['charged_to_user' => $this->cost]];
        }
        if ($path === 'topup') {
            $this->sent++;
            if ($this->timeout) {
                throw new \RuntimeException('timeout');
            }
            return ['status' => true, 'data' => ['provider_status' => true, 'transaction_id' => 86]];
        }
        if ($path === 'topupstatus') {
            return ['status' => true, 'data' => ['status' => $this->outcome]];
        }
        throw new \RuntimeException('Unexpected call');
    }
}
class InternationalTopupTest extends InternationalPurchaseTestCase
{
    private FakeInternational $service;
    protected function setUp(): void
    {
        parent::setUp();
        (require __DIR__ . '/../../database/migrations/2026_10_11_000001_create_international_topups.php')->up();
        (require __DIR__ . '/../../database/migrations/2026_10_17_000001_add_sochitel_international_provider.php')->up();
        $this->db->table('international_settings')->update(['enabled' => true]);
        $this->service = new FakeInternational();
    }
    private function input(): array
    {
        return ['country_code' => 'GH', 'type' => 'data', 'operator_id' => 7, 'amount' => 50, 'phone' => '0241234567'];
    }
    private function quote(): object
    {
        return $this->service->quote(1, $this->input());
    }
    public function test_markup_and_decimal_rounding(): void
    {
        $this->assertSame(618000, \App\Services\InternationalTopup::price(6000, '1.03'));
        $this->assertSame(105, \App\Services\InternationalTopup::price('1.01', '1.03'));
    }
    public function test_quote_normalizes_phone_and_applies_admin_multiplier(): void
    {
        $q = $this->quote();
        $this->assertSame('233241234567', $q->recipient_number);
        $this->assertEquals(618000, $q->amount_kobo);
        $this->db->table('international_settings')->update(['multiplier' => '1.05', 'calling_codes' => '{"GH":"+234"}']);
        $q = $this->quote();
        $this->assertEquals(630000, $q->amount_kobo);
        $this->assertSame('234241234567', $q->recipient_number);
    }
    public function test_purchase_and_retry_debit_and_dispatch_once(): void
    {
        $q = $this->quote();
        $p = $this->service->purchase(1, $q->id);
        $again = $this->service->purchase(1, $q->id);
        $this->assertSame('success', $p->status);
        $this->assertSame($p->id, $again->id);
        $this->assertSame(1, $this->service->sent);
        $this->assertEquals(3820, $this->db->table('users')->value('wallet'));
        $this->assertEquals(1, $this->db->table('orders')->value('status'));
    }
    public function test_timeout_never_resends_or_refunds_without_confirmation(): void
    {
        $q = $this->quote();
        $this->service->timeout = true;
        $p = $this->service->purchase(1, $q->id);
        $again = $this->service->purchase(1, $q->id);
        $this->assertSame('processing', $again->status);
        $this->assertSame(1, $this->service->sent);
        $this->assertEquals(3820, $this->db->table('users')->value('wallet'));
        $this->service->settle($p, 'reversed');
        $this->service->settle($p, 'reversed');
        $this->assertEquals(10000, $this->db->table('users')->value('wallet'));
    }
    public function test_price_change_requires_new_confirmation_before_debit(): void
    {
        $q = $this->quote();
        $this->service->cost = 7000;
        try {
            $this->service->purchase(1, $q->id);
            $this->fail('Expected rejection');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
        $this->assertSame(0, $this->service->sent);
        $this->assertEquals(10000, $this->db->table('users')->value('wallet'));
    }
    public function test_expired_quote_cannot_debit(): void
    {
        $q = $this->quote();
        $this->db->table('international_quotes')->where('id', $q->id)->update(['expires_at' => now()->subMinute()]);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->service->purchase(1, $q->id);
    }
    public function test_quote_is_private_to_owner(): void
    {
        $q = $this->quote();
        $this->db->table('users')->insert(['id' => 2, 'wallet' => 10000]);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->service->purchase(2, $q->id);
    }
    public function test_wrong_type_or_invalid_plan_cannot_be_quoted(): void
    {
        $in = $this->input();
        $in['operator_id'] = 8;
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->service->quote(1, $in);
    }
    public function test_insufficient_balance_never_dispatches(): void
    {
        $q = $this->quote();
        $this->db->table('users')->update(['wallet' => 100]);
        try {
            $this->service->purchase(1, $q->id);
            $this->fail('Expected rejection');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
        $this->assertSame(0, $this->service->sent);
        $this->assertEquals(100, $this->db->table('users')->value('wallet'));
    }
    public function test_airtime_operator_and_amount_range(): void
    {
        $in = $this->input();
        $in['type'] = 'airtime';
        $in['operator_id'] = 8;
        $in['amount'] = 10;
        $this->assertEquals(618000, $this->service->quote(1, $in)->amount_kobo);
        $in['amount'] = 101;
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->service->quote(1, $in);
    }
    public function test_disabled_service_blocks_quotes(): void
    {
        $this->db->table('international_settings')->update(['enabled' => false]);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->quote();
    }
    public function test_catalog_contains_local_plan_names(): void
    {
        $catalog = $this->service->catalog('GH', 'data');
        $this->assertCount(1, $catalog);
        $this->assertSame('5GB 30 days', $catalog[0]['plans'][0]['name']);
        $this->assertSame(50, $catalog[0]['plans'][0]['amount']);
    }
    public function test_logos_follow_country_and_service_selection(): void
    {
        $this->assertSame('https://logos.test/GH/data.png', $this->service->catalog('GH', 'data')[0]['logo']);
        $this->assertSame('https://logos.test/GB/data.png', $this->service->catalog('GB', 'data')[0]['logo']);
        $this->assertSame('https://logos.test/GH/airtime.svg', $this->service->catalog('GH', 'airtime')[0]['logo']);
        $this->assertNull($this->service->catalog('GH', 'data')[0]['fallback_logo']);
    }

    private function namedCatalog(array $fields): array
    {
        $this->service->operatorOverrides = [array_merge(['operatorId'=>7, 'name'=>'Ghana Data', 'data'=>true, 'destinationCurrencyCode'=>'GHS'], $fields)];
        return $this->service->catalog('GH', 'data')[0]['plans'];
    }

    public function test_decimal_description_keys_match_integer_and_fractional_local_amounts(): void
    {
        $plans = $this->namedCatalog([
            'localFixedAmounts'=>[3, 5.13, 50],
            'localFixedAmountsDescriptions'=>['3.00'=>'1GB Plan Mobile Data', '5.1300'=>'625MB Mobile Data', '50.00'=>"5GB 30 days\t"],
        ]);
        $this->assertSame(['1GB Plan Mobile Data', '625MB Mobile Data', '5GB 30 days'], array_column($plans, 'name'));
        $this->assertSame([3, 5.13, 50], array_column($plans, 'amount'));
    }

    public function test_sender_descriptions_supply_names_without_changing_local_purchase_amounts(): void
    {
        $plans = $this->namedCatalog([
            'localFixedAmounts'=>[1, 50], 'localFixedAmountsDescriptions'=>[],
            'fixedAmounts'=>[83.2, 4160.2],
            'fixedAmountsDescriptions'=>['83.20'=>'300MB — 2 days', '4160.20'=>'5GB — 30 days'],
        ]);
        $this->assertSame(['300MB — 2 days', '5GB — 30 days'], array_column($plans, 'name'));
        $this->assertSame([1, 50], array_column($plans, 'amount'));
    }

    public function test_local_description_takes_precedence_and_ignores_empty_strings(): void
    {
        $plans = $this->namedCatalog([
            'localFixedAmounts'=>[1, 50], 'localFixedAmountsDescriptions'=>['1.00'=>'   ', '50.00'=>'Correct 5GB bundle'],
            'fixedAmounts'=>[83.2, 4160.2], 'fixedAmountsDescriptions'=>['83.20'=>'300MB daily', '4160.20'=>'Other description'],
        ]);
        $this->assertSame(['300MB daily', 'Correct 5GB bundle'], array_column($plans, 'name'));
    }

    public function test_mismatched_denomination_arrays_do_not_assign_the_wrong_bundle_name(): void
    {
        $plans = $this->namedCatalog([
            'localFixedAmounts'=>[1, 50], 'fixedAmounts'=>[83.2], 'fixedAmountsDescriptions'=>['83.20'=>'Wrong bundle'],
        ]);
        $this->assertStringNotContainsString('Wrong bundle', $plans[0]['name']);
        $this->assertStringNotContainsString('Wrong bundle', $plans[1]['name']);
    }

}
