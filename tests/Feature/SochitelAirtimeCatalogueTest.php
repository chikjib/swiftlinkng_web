<?php

namespace Tests\Feature;

use App\Services\{InternationalTopup, Sochitel};
use Illuminate\Support\Facades\{Crypt, DB, Http};
use Tests\Support\{InternationalApiTestCase, SochitelFixtures};

class SochitelAirtimeCatalogueTest extends InternationalApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['international.sochitel_base_url' => 'https://sochitel.test/api']);
        DB::table('international_settings')->update([
            'provider' => 'sochitel', 'sochitel_protocol' => 'rest', 'sochitel_user_id' => '123456',
            'sochitel_private_key' => Crypt::encryptString(SochitelFixtures::keys()['private']),
            'sochitel_currency' => 'USD', 'sochitel_ngn_rate' => 1500,
        ]);
    }

    private function product(int $id, string $name, float $min, float $max, string $type = '1'): array
    {
        return ['id' => (string) $id, 'name' => $name, 'type' => $type, 'category' => $type.'.0',
            'amount' => ['type' => $min === $max ? 'fixed' : 'range',
                'min' => ['operator' => $min, 'user' => $min / 100],
                'max' => ['operator' => $max, 'user' => $max / 100]]];
    }

    private function fakeOperators(array $operators): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        Http::fake(['https://sochitel.test/api/operators' => Http::response(['sysErr' => 0, 'response' => $operators])]);
    }

    private function operator(int $id, string $name, array $products, string $country = 'NG'): array
    {
        return ['id' => (string) $id, 'name' => $name, 'country' => $country,
            'currency' => $country === 'NG' ? 'NGN' : 'GHS', 'products' => $products];
    }

    private function catalog(string $country = 'NG', string $type = 'airtime'): array
    {
        return app(Sochitel::class)->catalog(app(InternationalTopup::class)->settings(), $country, $type, true);
    }

    public function test_nigeria_keeps_only_the_screenshot_selections_and_original_prices(): void
    {
        $airtel = [$this->product(10, '50-150000 NGN Top Up', 50, 150000), $this->product(11, '100 NGN Talkmore', 100, 100), $this->product(12, '200 NGN Talkmore', 200, 200)];
        $glo = [$this->product(20, '10-150000 NGN Top Up', 10, 150000), $this->product(21, '1100 NGN Top up', 1100, 1100)];
        $mtn = [$this->product(30, '1-99 NGN GISTPLUS (3 Days)', 1, 99),
            $this->product(31, '5-150,000NGN Top Up', 5, 150000),
            $this->product(32, '5-100000 NGN Top Up', 5, 100000),
            $this->product(33, 'GISTPLUS 100-999NGN (7 Days)', 100, 999),
            $this->product(34, 'GISTPLUS 1000-5000NGN (14 Days)', 1000, 5000),
            $this->product(35, '1100 NGN Top up', 1100, 1100),
            $this->product(36, '2200 NGN Top up', 2200, 2200)];
        $t2 = [$this->product(40, 'T2 Mobile Top Up', 50, 50000)];
        $this->fakeOperators([$this->operator(20, 'Nigeria Airtel', $airtel), $this->operator(19, 'Nigeria GLO', $glo), $this->operator(1, 'Nigeria MTN', $mtn), $this->operator(18, 'Nigeria T2 Mobile', $t2)]);
        $rows = $this->catalog();
        $this->assertSame([10, 20, 30, 31, 33, 34, 40], array_column($rows, 'id'));
        $this->assertSame('Nigeria Airtel', $rows[0]['name']);
        $this->assertSame('Nigeria GLO', $rows[1]['name']);
        $this->assertSame('Nigeria T2 Mobile', $rows[6]['name']);
        $this->assertEquals($mtn[1], $rows[3]['_products'][0]);
        $this->assertEquals(150000, $rows[3]['max']);
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }

    public function test_other_countries_remove_contained_ranges_and_fixed_denominations_per_operator(): void
    {
        $products = [$this->product(12, 'Duplicate range', 1, 100), $this->product(10, 'Main range', 1, 100),
            $this->product(11, 'Narrower range', 10, 80), $this->product(13, 'Fixed inside', 50, 50),
            $this->product(14, 'Outside denomination', 150, 150), $this->product(15, 'Duplicate outside denomination', 150, 150)];
        $this->fakeOperators([$this->operator(7, 'Ghana Network', $products, 'GH'),
            $this->operator(8, 'Another Ghana Network', [$this->product(21, 'Same bounds on another network', 1, 100)], 'GH')]);
        $this->assertSame([10, 14, 21], array_column($this->catalog('GH'), 'id'));
        $this->fakeOperators([$this->operator(7, 'Ghana Network', array_reverse($products), 'GH')]);
        $ids = array_column($this->catalog('GH'), 'id');
        sort($ids);
        $this->assertSame([10, 14], $ids);
    }

    public function test_partial_overlap_and_disjoint_ranges_preserve_unique_coverage(): void
    {
        $products = [$this->product(10, 'First range', 1, 100), $this->product(11, 'Extends to 150', 80, 150), $this->product(12, 'Separate range', 200, 300)];
        $this->fakeOperators([$this->operator(7, 'Ghana Network', $products, 'GH')]);
        $rows = $this->catalog('GH');
        $this->assertSame([10, 11, 12], array_column($rows, 'id'));
        $this->assertEquals($products[1], $rows[1]['_products'][0]);
    }

    public function test_data_products_sharing_a_denomination_remain_distinct(): void
    {
        $this->fakeOperators([$this->operator(7, 'Ghana Network', [$this->product(10, 'Daily data', 50, 50, '4'), $this->product(11, 'Weekly data', 50, 50, '4')], 'GH')]);
        $this->assertSame([10, 11], array_column($this->catalog('GH', 'data')[0]['plans'], 'product_id'));
    }

    public function test_hidden_airtime_product_cannot_be_selected_from_a_stale_client(): void
    {
        $this->fakeOperators([$this->operator(20, 'Nigeria Airtel', [$this->product(10, 'Top Up', 50, 150000), $this->product(11, 'Talkmore', 100, 100)])]);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Route or operator changed');
        app(Sochitel::class)->selection(app(InternationalTopup::class)->settings(), ['country_code' => 'NG', 'type' => 'airtime', 'operator_id' => 11, 'amount' => 100], true);
    }
}
