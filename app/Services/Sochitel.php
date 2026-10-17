<?php

namespace App\Services;

use Illuminate\Support\Facades\{Cache, Crypt, Http, RateLimiter};

class Sochitel
{
    public function account(object $s): string
    {
        return hash('sha256', rtrim(config('international.sochitel_base_url', ''), '/') . '|' . ($s->sochitel_user_id ?? ''));
    }

    public function verificationHash(object $s): string
    {
        return hash('sha256', $this->account($s) . '|' . ($s->sochitel_private_key ?? '') . '|' . ($s->sochitel_currency ?? ''));
    }

    public function request(object $s, string $method, string $path, array $body = []): array
    {
        $base = rtrim(config('international.sochitel_base_url', ''), '/');
        $url = $base . $path;
        $parts = parse_url($url);
        abort_unless(($parts['scheme'] ?? '') === 'https' && !empty($parts['host']) && !isset($parts['user']) && !isset($parts['pass']) && !isset($parts['query']) && !isset($parts['fragment']), 503, 'Set a valid HTTPS SOCHITEL_BASE_URL on the server.');
        abort_unless(!empty($s->sochitel_user_id) && !empty($s->sochitel_private_key), 503, 'Configure the Sochitel user ID and RSA private key in admin.');
        $payload = $method === 'GET' ? '' : json_encode($body, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $date = gmdate('r');
        $nonce = gmdate('N');
        for ($i = 0; $i < 17; $i++) $nonce .= random_int(0, 9);
        $digest = 'SHA-256=' . base64_encode(hash('sha256', $payload, true));
        $host = $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
        $target = $parts['path'] ?? '/';
        // Sign the exact bytes sent, including any account-specific base path.
        $signing = '(request-target): ' . strtolower($method) . ' ' . $target . "\nhost: $host\ndate: $date\nnonce: $nonce\ndigest: $digest";
        $key = Crypt::decryptString($s->sochitel_private_key);
        abort_unless(openssl_sign($signing, $signature, $key, OPENSSL_ALGO_SHA256), 503, 'Unable to sign the Sochitel request.');
        abort_unless(RateLimiter::attempt('international-sochitel-requests', 60, fn() => true, 60), 429, 'International service is busy. Try again in a minute.');
        $authorization = 'Signature keyId="' . $s->sochitel_user_id . '", algorithm="rsa-sha256", headers="(request-target) host date nonce digest", signature="' . base64_encode($signature) . '"';
        $r = Http::acceptJson()->withHeaders(['Host' => $host, 'Date' => $date, 'Nonce' => $nonce, 'Digest' => $digest, 'Authorization' => $authorization])
            ->withOptions(['allow_redirects' => false])->connectTimeout(10)->timeout(50)
            ->withBody($payload, 'application/json')->send($method, $url);
        $data = $r->json();
        if (!is_array($data) || !isset($data['errno'])) throw new \RuntimeException('Sochitel returned an unreadable response.');
        // Transaction failures can contain a valid transaction ID even on HTTP 4xx.
        if (!$r->successful() && $path !== '/transaction') throw new \RuntimeException('Sochitel request failed. Check the account configuration or try again.');
        return $data;
    }

    public function operators(object $s, bool $fresh = false): array
    {
        $load = function () use ($s) {
            $r = $this->request($s, 'GET', '/operators');
            abort_unless((int) ($r['errno'] ?? -1) === 0 && is_array($r['operators'] ?? null), 503, 'Sochitel catalogue unavailable.');
            return $r['operators'];
        };
        return $fresh ? $load() : Cache::remember('sochitel-operators-' . $this->verificationHash($s), 300, $load);
    }

    private function supported(array $p, string $type): bool
    {
        if ((string) ($p['type'] ?? '') !== ($type === 'data' ? '4' : '1')) return false;
        // These screens collect a phone number only. Never sell products requiring other inputs.
        foreach ((array) ($p['extraParameters'] ?? []) as $extra) {
            if (is_array($extra) && !empty($extra['mandatory'])) return false;
        }
        if (!in_array($p['amount']['type'] ?? '', ['fixed', 'range'], true)) return false;
        if ($type === 'data' && $p['amount']['type'] !== 'fixed') return false;
        foreach (['min', 'max'] as $bound) {
            foreach (['operator', 'user'] as $currency) {
                $value = $p['amount'][$bound][$currency] ?? null;
                if (!is_numeric($value) || $value <= 0 || $value > 100000000) return false;
            }
        }
        return ctype_digit((string) ($p['id'] ?? '')) && (int) $p['id'] > 0;
    }

    public function countries(object $s): array
    {
        $metadata = json_decode(file_get_contents(resource_path('data/international-countries.json')), true, 512, JSON_THROW_ON_ERROR);
        $overrides = json_decode($s->calling_codes ?? '{}', true) ?: [];
        $countries = [];
        foreach ($this->operators($s) as $o) {
            $iso = strtoupper($o['country'] ?? '');
            if (empty($metadata[$iso])) continue;
            if (!collect($o['products'] ?? [])->contains(fn($p) => $this->supported($p, 'airtime') || $this->supported($p, 'data'))) continue;
            $countries[$iso] = ['code' => $iso, 'name' => $metadata[$iso]['name'], 'calling_code' => $overrides[$iso] ?? $metadata[$iso]['calling_code'], 'flag' => null];
        }
        usort($countries, fn($a, $b) => strcmp($a['name'], $b['name']));
        return $countries;
    }

    public function catalog(object $s, string $country, string $type, bool $fresh = false): array
    {
        $result = [];
        $logos = json_decode($s->sochitel_logos ?? '{}', true) ?: [];
        foreach ($this->operators($s, $fresh) as $o) {
            if (($o['country'] ?? '') !== $country || !ctype_digit((string) ($o['id'] ?? ''))) continue;
            $products = array_values(array_filter($o['products'] ?? [], fn($p) => $this->supported($p, $type)));
            $logo = $logos[$o['id']] ?? null;
            $base = ['name' => $o['name'], 'logo' => $logo, 'fallback_logo' => null, 'currency' => $o['currency'], 'provider' => 'sochitel'];
            if ($type === 'data' && $products) {
                $result[] = $base + ['id' => (int) $o['id'], 'min' => null, 'max' => null, 'plans' => array_map(fn($p) => ['amount' => $p['amount']['min']['operator'], 'name' => $p['name'], 'product_id' => (int) $p['id']], $products), '_operator' => $o['id'], '_products' => $products];
            } elseif ($type === 'airtime') {
                foreach ($products as $p) {
                    // A network may offer several airtime products with different pricing/ranges.
                    $entry = $base;
                    if (count($products) > 1) $entry['name'] .= ' — ' . $p['name'];
                    $result[] = $entry + ['id' => (int) $p['id'], 'min' => $p['amount']['min']['operator'], 'max' => $p['amount']['max']['operator'], 'plans' => [], '_operator' => $o['id'], '_products' => [$p]];
                }
            }
        }
        return $result;
    }

    public function selection(object $s, array $in, bool $fresh = false): array
    {
        $o = collect($this->catalog($s, $in['country_code'], $in['type'], $fresh))->firstWhere('id', (int) $in['operator_id']);
        abort_unless($o, 422, 'Route or operator changed. Select the country and operator again.');
        $products = $o['_products'];
        $p = $in['type'] === 'data' ? collect($products)->firstWhere('id', (string) ($in['product_id'] ?? '')) : $products[0];
        abort_unless($p, 422, 'Select an available data plan.');
        $amount = (float) $in['amount'];
        $min = (float) $p['amount']['min']['operator'];
        $max = (float) $p['amount']['max']['operator'];
        abort_unless($amount >= $min && $amount <= $max, 422, 'Amount is outside this product’s limits.');
        if ($p['amount']['type'] === 'fixed') abort_unless($amount === $min && $min === $max, 422, 'Select an available plan or denomination.');
        $userMin = (float) $p['amount']['min']['user'];
        $userMax = (float) $p['amount']['max']['user'];
        // Range endpoints must express the same conversion. Never invent an undocumented fee curve.
        abort_unless(abs($userMin - round($min * $userMax / $max, 2)) <= 0.01, 503, 'This product has variable pricing and cannot be quoted safely.');
        $cost = round($amount * $userMax / $max, 2);
        $rate = $s->sochitel_currency === 'NGN' ? 1 : (float) $s->sochitel_ngn_rate;
        abort_unless($rate > 0, 503, 'Configure the Sochitel conversion to NGN.');
        return ['operator' => $o, 'product' => $p, 'cost' => round($cost * $rate, 2), 'currency' => $o['currency'], 'payload' => ['operator' => (string) $o['_operator'], 'product' => (string) $p['id'], 'currency' => $o['currency']]];
    }

    public function verify(object $s): array
    {
        $r = $this->request($s, 'GET', '/balance');
        abort_unless((int) ($r['errno'] ?? -1) === 0 && ($r['currency'] ?? '') === $s->sochitel_currency, 422, 'Sochitel account currency does not match the configured currency.');
        $operators = $this->operators($s, true);
        $count = 0;
        foreach ($operators as $o) foreach ($o['products'] ?? [] as $p) if ($this->supported($p, 'airtime') || $this->supported($p, 'data')) $count++;
        abort_unless($count, 422, 'No compatible airtime or fixed data products are enabled on this Sochitel account.');
        return ['currency' => $r['currency'], 'balance' => $r['balance'] ?? null, 'products' => $count];
    }

    public function outcome(array $r): ?string
    {
        $status = $r['status'] ?? null;
        $code = is_array($status) ? ($status['id'] ?? null) : $status;
        if ($code === null || !is_numeric($code)) return null;
        $code = (int) $code;
        // 59 is described as type 2 in the appendix but explicitly remains pending in the lifecycle.
        if (in_array($code, [9, 46, 59], true)) return null;
        if ($code === 0) return 'success';
        $failures = [1,2,3,4,6,7,8,13,14,15,20,24,27,29,32,35,36,37,39,41,42,43,48,50,53,56,57,58,60,61,63,65,66,67,68,70,71,72,73,74,75,76,77];
        return in_array($code, $failures, true) ? 'reversed' : null;
    }
}
