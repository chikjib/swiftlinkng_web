<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class PayVesselBetting
{
    public function request(string $method, string $path, array $body = []): array
    {
        abort_unless(config('betting.api_key') && config('betting.api_secret'), 503, 'Betting is temporarily unavailable.');
        $response = Http::acceptJson()->withHeaders([
            'api-key' => config('betting.api_key'),
            'api-secret' => config('betting.api_secret'),
        ])->connectTimeout(10)->timeout(35)->send($method,
            rtrim(config('betting.base_url'), '/').'/vaas/api/v1/biller-reseller/'.$path,
            $method === 'GET' ? [] : ['json' => $body]);
        // Never retry order creation: a timeout may still have charged the provider wallet.
        if (!$response->successful() || $response->json('status') !== true) {
            throw new \RuntimeException('PayVessel could not confirm this request.');
        }
        $data = $response->json('data');
        if (!is_array($data)) throw new \RuntimeException('Invalid PayVessel response.');
        return $data;
    }

    public function billers(): array
    {
        $settings = ServiceControls::enabled('betting');
        $allowed = json_decode($settings->betting_biller_ids ?? '[]', true);
        abort_if(empty($allowed), 503, 'Betting platforms are being set up. Please try again later.');
        return array_values(array_filter($this->request('GET', 'billers'), fn ($b) =>
            in_array($b['biller_id'] ?? null, $allowed, true) && ($b['status'] ?? 1) == 1));
    }

    public function biller(string $id): array
    {
        foreach ($this->billers() as $biller) if ($biller['biller_id'] === $id) return $biller;
        throw ValidationException::withMessages(['biller_id' => 'Select an available betting platform.']);
    }

    public function items(string $id): array
    {
        $this->biller($id);
        return $this->request('GET', 'billers/'.rawurlencode($id).'/items');
    }

    public function verify(array $input): string
    {
        $data = $this->request('POST', 'validate-account', array_intersect_key($input, array_flip(['biller_id', 'item_id', 'recharge_account'])));
        $name = trim((string) ($data['biller'] ?? ''));
        if ($name === '') throw ValidationException::withMessages(['recharge_account' => 'Betting account could not be verified.']);
        return $name;
    }

    public function validatePurchase(array $input): array
    {
        $biller = $this->biller($input['biller_id']);
        $items = $this->request('GET', 'billers/'.rawurlencode($input['biller_id']).'/items');
        $item = collect($items)->firstWhere('item_id', $input['item_id']);
        if (!$item) throw ValidationException::withMessages(['item_id' => 'Select an available funding option.']);
        $amount = (int) $input['amount'];
        $min = max(1, (int) ($biller['min_amount'] ?? 1), (int) ($item['min_amount'] ?? 1));
        $limits = array_filter([$biller['max_amount'] ?? null, $item['max_amount'] ?? null], fn ($v) => $v !== null);
        if ($amount < $min || ($limits && $amount > min($limits)) ||
            (($item['is_fixed_amount'] ?? false) && $amount !== (int) $item['amount'])) {
            throw ValidationException::withMessages(['amount' => 'Amount is outside the platform funding limits.']);
        }
        return [$biller, $this->verify($input)];
    }
}
