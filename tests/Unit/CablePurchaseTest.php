<?php

namespace Tests\Unit;

use App\Http\Controllers\API\OrderController;
use App\Models\Order;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;

class CablePurchaseTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('cableResponses')]
    public function test_vtpass_cable_response_with_optional_voucher(string $plan, string $status, ?string $voucher): void
    {
        $container = new Container();
        Container::setInstance($container);
        $container->instance('config', new Repository(['app' => ['GATE_TOKEN' => '']]));
        $container->instance('log', new \Illuminate\Log\Logger(new \Psr\Log\NullLogger()));
        $factory = $this->createStub(ResponseFactory::class);
        $factory->method('json')->willReturnCallback(fn ($data, $status = 200) => new JsonResponse($data, $status));
        $container->instance(ResponseFactory::class, $factory);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($container);
        if (!class_exists('Log')) {
            class_alias(\Illuminate\Support\Facades\Log::class, 'Log');
        }

        $db = new Capsule($container);
        $db->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $db->setAsGlobal();
        $db->bootEloquent();
        $db->schema()->create('subcategory', function (Blueprint $table) {
            $table->id();
            foreach (['title', 'description', 'products', 'pins', 'telegram'] as $field) {
                $table->text($field)->nullable();
            }
            $table->integer('status');
        });
        $db->schema()->create('orders', function (Blueprint $table) {
            $table->id();
            foreach (['ref', 'custom_reference', 'user_id', 'subcategory_id', 'plan', 'amount', 'quantity', 'phone', 'subtotal', 'total', 'iuc', 'bal', 'prev_bal', 'channel', 'api_route', 'description', 'status'] as $field) {
                $table->text($field)->nullable();
            }
            $table->timestamps();
        });
        $db->table('subcategory')->insert([
            'title' => $plan, 'description' => 'VTPASS', 'status' => 1,
            'products' => '{"amount1":0}',
        ]);

        $controller = new class extends OrderController {
            public array $providerResponse;
            public string $notification = '';
            public function __construct() {}
            public function fetchBouquet(Request $request) {
                return new JsonResponse(['data' => [(object) ['variation_code' => 'test-plan', 'variation_amount' => 5000]]]);
            }
            public function vtPassreferenceCode() { return 'test-cable-ref'; }
            public function isDebited($amount, $userid = null) { return true; }
            public function VtPassPurchase($billerCode, $serviceID, $variationCode, $amount, $phone, $ref, $subtype = 'renew') {
                return $this->providerResponse;
            }
            protected function processReferralRewards(Order $order) {}
            public function sendTelegramMessage($amount, $phone, $pin, $channelCode, $ref, $channelID, $channelText = '') {
                $this->notification = $channelText;
            }
        };
        $controller->user = (object) ['id' => 1, 'wallet' => 10000, 'userlevel' => 0];
        $controller->providerResponse = [
            'code' => '000', 'response_description' => 'TRANSACTION SUCCESSFUL',
            'content' => ['transactions' => ['status' => $status, 'transactionId' => 'vtpass-ref']],
        ];
        if ($voucher !== null) {
            $controller->providerResponse['purchased_code'] = $voucher;
        }

        // Laravel converts missing array-key warnings to exceptions. Reproduce that
        // behavior while exercising the real controller and persisted order update.
        set_error_handler(function ($severity, $message, $file, $line) {
            if ($severity === E_WARNING) {
                throw new \ErrorException($message, 0, $severity, $file, $line);
            }
            return false;
        });
        try {
            $response = $controller->purchaseCable(new Request([
                'plan' => $plan, 'amount' => 1, 'variation_code' => 'test-plan',
                'phonenumber' => '08012345678', 'cardno' => '1234567890',
            ]));
            $data = $response->getData(true);
            $this->assertSame(200, $response->getStatusCode());
            $this->assertTrue($data['success']);
            $this->assertSame($voucher, $data['token']);
            $this->assertEquals(5000, $data['amount']);
            $order = $db->table('orders')->first();
            $this->assertEquals($status === 'delivered' ? 1 : 0, $order->status);
            if ($voucher) {
                $this->assertStringContainsString($voucher, $order->description);
                $this->assertStringContainsString($voucher, $controller->notification);
                $this->assertStringContainsString('Voucher', $data['message']);
            } else {
                $this->assertStringNotContainsString('Voucher', $data['message']);
                $this->assertStringNotContainsString('Voucher', $controller->notification);
            }
        } finally {
            restore_error_handler();
            $db->getDatabaseManager()->disconnect();
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication(null);
            Container::setInstance(null);
        }
    }

    public static function cableResponses(): array
    {
        return [
            'DStv without voucher' => ['DSTV', 'delivered', null],
            'GOtv without voucher' => ['GOTV', 'delivered', null],
            'Showmax with voucher' => ['SHOWMAX', 'delivered', 'voucher-123'],
            'processing without voucher' => ['DSTV', 'pending', null],
        ];
    }
}
