<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Validation\ValidationException;

class PayVesselBetting
{
    public function request(string $method, string $path, array $body = []): array
    {
        abort_unless(config('betting.api_key') && config('betting.api_secret'), 503, 'Betting is temporarily unavailable.');
        try {
        $response = Http::acceptJson()->withHeaders([
            'api-key' => config('betting.api_key'),
            'api-secret' => config('betting.api_secret'),
        ])->connectTimeout(10)->timeout(35)->send($method,
            rtrim(config('betting.base_url'), '/').'/vaas/api/v1/biller-reseller/'.$path,
            $method === 'GET' ? [] : ['json' => $body]);
        } catch (ConnectionException $e) {
            Log::warning('PayVessel request failed', ['operation' => $this->operation($path), 'reason' => 'connection_failure']);
            throw new HttpException(503, 'Unable to reach the betting provider. Please try again shortly.');
        }
        // Never retry order creation: a timeout may still have charged the provider wallet.
        if (!$response->successful() || $response->json('status') !== true) {
            $httpStatus = $response->status();
            $payload = $response->json();
            $status = is_array($payload) ? ($payload['status'] ?? null) : null;
            // Only log response metadata. Never log credentials, bodies or account IDs.
            Log::warning('PayVessel request rejected', [
                'operation' => $this->operation($path),
                'http_status' => $httpStatus,
                'response_format' => is_array($payload) ? 'json' : 'non_json',
                'provider_status' => is_bool($status) ? $status : gettype($status),
            ]);
            if (in_array($httpStatus, [401, 403], true)) {
                throw new HttpException(503, 'The betting provider could not authorize this request. Please contact Swiftlink support.');
            }
            if ($httpStatus === 429) {
                throw new HttpException(503, 'The betting provider is busy. Please wait a moment and try again.');
            }
            if ($path === 'validate-account' && in_array($httpStatus, [200, 400, 422], true) && is_array($payload) && $status === false) {
                throw ValidationException::withMessages(['recharge_account' => 'The provider could not verify these details. Check the selected betting platform and account ID, or contact support if they are correct.']);
            }
            throw new HttpException(502, 'The betting provider could not confirm this request. Please try again shortly or contact Swiftlink support.');
        }
        $data = $response->json('data');
        if (!is_array($data)) {
            Log::warning('PayVessel invalid response', ['operation' => $this->operation($path), 'http_status' => $response->status()]);
            throw new HttpException(502, 'The betting provider returned an unexpected response. Please contact Swiftlink support.');
        }
        return $data;
    }

    private function operation(string $path): string
    {
        if ($path === 'validate-account') return 'validate-account';
        if ($path === 'orders') return 'create-order';
        if (str_starts_with($path, 'orders/verify/')) return 'verify-order';
        return $path === 'billers' ? 'billers' : 'biller-items';
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
