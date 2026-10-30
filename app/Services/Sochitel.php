<?php

namespace App\Services;

use Illuminate\Support\Facades\{Cache, Crypt, Http, Log, RateLimiter};

class Sochitel
{
    public function protocol(object $s): string
    {
        return $s->sochitel_protocol ?? 'rest';
    }

    public function account(object $s): string
    {
        if ($this->protocol($s) === 'artx') return hash('sha256', 'artx|'.config('international.sochitel_artx_url').'|'.($s->sochitel_username ?? ''));
        return hash('sha256', rtrim(config('international.sochitel_base_url', ''), '/') . '|' . ($s->sochitel_user_id ?? ''));
    }

    public function verificationHash(object $s): string
    {
        return hash('sha256', $this->account($s) . '|' . ($this->protocol($s) === 'artx' ? ($s->sochitel_password ?? '') : ($s->sochitel_private_key ?? '')) . '|' . ($s->sochitel_currency ?? ''));
    }

    public function request(object $s, string $method, string $path, array $body = []): array
    {
        if ($this->protocol($s) === 'artx') return app(SochitelArtx::class)->request($s, $method, $path, $body);
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
        // The client portal wraps successful payloads in response, using sysErr
        // for request errors. Other REST deployments use the documented errno.
        if (is_array($data) && !array_key_exists('errno', $data) && is_numeric($data['sysErr'] ?? null)) {
            $code = (int) $data['sysErr'];
            $result = $data['response'] ?? [];
            if ($code === 0 && !is_array($result)) throw $this->unrecognizedResponse($r, $data, $path);
            if (!is_array($result)) $result = [];
            if ($path === '/operators' && $code === 0 && array_is_list($result)) $result = ['operators' => $result];
            $data = ['errno' => $code] + $result;
        }
        if (!is_array($data) || !is_numeric($data['errno'] ?? null)) throw $this->unrecognizedResponse($r, $data, $path);
        // Transaction failures can contain a valid transaction ID even on HTTP 4xx.
        if ($path !== '/transaction' && (!$r->successful() || (int) $data['errno'] !== 0)) throw SochitelConnectionException::response($r->status(), (int) $data['errno']);
        return $data;
    }

    private function unrecognizedResponse(\Illuminate\Http\Client\Response $response, mixed $data, string $path): SochitelConnectionException
    {
        // Log structure only: no response values, credentials, balances or signatures.
        Log::warning('sochitel.rest_response_unrecognized', [
            'parser' => 'rest-envelope-v2', 'path' => $path, 'http_status' => $response->status(),
            'content_type' => $response->header('Content-Type'),
            'json_type' => get_debug_type($data),
            'json_keys' => is_array($data) ? array_slice(array_keys($data), 0, 20) : [],
            'response_type' => is_array($data) ? get_debug_type($data['response'] ?? null) : null,
        ]);
        return new SochitelConnectionException(SochitelConnectionException::response($response->status())->getMessage().' REST parser v2 expected errno or sysErr/response; see the sochitel.rest_response_unrecognized server log for response structure.');
    }

    public function operators(object $s, bool $fresh = false, array $filters = []): array
    {
        $load = function () use ($s, $filters) {
            $r = $this->request($s, 'GET', '/operators', $filters);
            abort_unless((int) ($r['errno'] ?? -1) === 0 && is_array($r['operators'] ?? null), 503, 'Sochitel catalogue unavailable.');
            return $r['operators'];
        };
        return $fresh ? $load() : Cache::remember('sochitel-operators-' . $this->verificationHash($s).'-'.md5(json_encode($filters)), 300, $load);
    }

    private function supported(array $p, string $type): bool
    {
        if ($type === 'internet') {
            if (!in_array((string)($p['type'] ?? ''), ['3','4'], true)) return false;
            if ((int)($p['category'] ?? 0) !== 6 && !((string)($p['type'] ?? '') === '4' && (int)($p['category'] ?? 0) === 4)) return false;
        } elseif ((string) ($p['type'] ?? '') !== ($type === 'data' ? '4' : '1')) return false;
        // These screens collect only the recipient. Exclude products requiring additional inputs.
        foreach ((array) ($p['extraParameters'] ?? []) as $extra) {
            if (is_array($extra) && !empty($extra['mandatory'])) return false;
        }
        if (!in_array($p['amount']['type'] ?? '', ['fixed', 'range'], true)) return false;
        if (in_array($type, ['data','internet'], true) && $p['amount']['type'] !== 'fixed') return false;
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
        // Internet providers also publish mobile bundles under category 4.x.
        // Fetch both categories, then apply the supported-product checks locally.
        foreach ($this->operators($s, $fresh, $this->protocol($s) === 'artx' ? ['country' => $country] + ($type === 'internet' ? [] : ['productType' => $type === 'data' ? 4 : 1]) : []) as $o) {
            if (($o['country'] ?? '') !== $country || !ctype_digit((string) ($o['id'] ?? ''))) continue;
            $products = array_values(array_filter($o['products'] ?? [], fn($p) => $this->supported($p, $type)));
            $images = $this->operatorLogos($o, $logos[$o['id']] ?? null);
            $base = ['name' => $o['name'], 'logo' => $images['logo'], 'fallback_logo' => $images['fallback_logo'], 'currency' => $o['currency'], 'provider' => 'sochitel'];
            if (in_array($type, ['data','internet'], true) && $products) {
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

    public function operatorLogos(array $operator, ?string $override = null): array
    {
        // The published REST schema does not promise logos. Use any optional
        // metadata returned by an account, then our existing Nigerian assets.
        $candidates = array_merge([$override, $operator['logo'] ?? null, $operator['logoUrl'] ?? null], (array) ($operator['logoUrls'] ?? []));
        $remote = null;
        foreach ($candidates as $url) {
            if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL)) continue;
            $parts = parse_url($url);
            if (($parts['scheme'] ?? '') === 'https' && !isset($parts['user']) && !isset($parts['pass'])) { $remote = $url; break; }
        }
        $fallback = null;
        if (($operator['country'] ?? '') === 'NG') {
            $name = strtolower($operator['name'] ?? '');
            foreach (['spectranet' => 'internet_spectranet-transparent.png', 'ntel' => 'internet_nitel-transparent.png', 'nitel' => 'internet_nitel-transparent.png', 'smile' => 'internet_smile-transparent.png', 'mtn' => 'mtn.jpeg', 'airtel' => 'airtel.jpeg', 'glo' => 'glodata.jpeg', '9mobile' => '9mobile.jpeg', 't2 mobile' => '9mobile.jpeg', 'etisalat' => '9mobile.jpeg'] as $brand => $file) {
                if (preg_match('/\b'.preg_quote($brand, '/').'\b/', $name)) { $fallback = '/template/images/services/'.$file; break; }
            }
        }
        return ['logo' => $remote ?? $fallback, 'fallback_logo' => $fallback];
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
        // The provider explicitly prices both endpoints, even when their rates
        // differ. Use those exact prices before considering interpolation.
        if ($amount === $min) {
            $cost = $userMin;
        } elseif ($amount === $max) {
            $cost = $userMax;
        } else {
            $proportional = abs($userMin - round($min * $userMax / $max, 2)) <= 0.01;
            if (!$proportional) Log::warning('sochitel.range_quote_unavailable', [
                'operator' => $o['_operator'], 'product' => $p['id'],
                'country' => $in['country_code'], 'amount' => $amount,
                'operator_min' => $min, 'operator_max' => $max,
                'user_min' => $userMin, 'user_max' => $userMax,
            ]);
            abort_unless($proportional, 503, 'An exact price is unavailable for this amount. Try '. $min .' or '. $max .' '. $o['currency'] .', or select another product.');
            $cost = round($amount * $userMax / $max, 2);
        }
        $rate = $s->sochitel_currency === 'NGN' ? 1 : (float) $s->sochitel_ngn_rate;
        abort_unless($rate > 0, 503, 'Configure the Sochitel conversion to NGN.');
        return ['operator' => $o, 'product' => $p, 'cost' => round($cost * $rate, 2), 'currency' => $o['currency'], 'payload' => ['operator' => (string) $o['_operator'], 'product' => (string) $p['id'], 'currency' => $o['currency']]];
    }

    public function verify(object $s): array
    {
        $r = $this->request($s, 'GET', '/balance');
        $currency = $r['currency'] ?? null;
        if ((int) ($r['errno'] ?? -1) !== 0 || !is_string($currency) || !preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new SochitelConnectionException('Sochitel balance response did not include a valid account currency. Check the selected API and account configuration.');
        }
        if ($currency !== $s->sochitel_currency) {
            $configured = preg_match('/^[A-Z]{3}$/', (string) $s->sochitel_currency) ? $s->sochitel_currency : 'unset';
            throw new SochitelConnectionException("Sochitel account currency is {$currency}, but the configured currency is {$configured}. Set Sochitel account currency to {$currency} in International Settings and configure its NGN conversion rate before testing again.");
        }
        $operators = $this->operators($s, true);
        $count = 0;
        foreach ($operators as $o) foreach ($o['products'] ?? [] as $p) if ($this->supported($p, 'airtime') || $this->supported($p, 'data') || $this->supported($p, 'internet')) $count++;
        abort_unless($count, 422, 'No compatible airtime, data or Internet products are enabled on this Sochitel account.');
        return ['currency' => $r['currency'], 'balance' => $r['balance'] ?? null, 'products' => $count];
    }

    public function recipientMatches(array $response, array $payload): bool
    {
        $expected = (string)($payload['recipient'] ?? '');
        if (($response['_protocol'] ?? '') === 'artx' && ($payload['_recipient_kind'] ?? '') !== 'accountId' && str_starts_with($expected, '0')) $expected = '234'.substr($expected, 1);
        return (string)($response['recipient'] ?? '') === $expected;
    }

    public function productMatches(array $response, array $payload): bool
    {
        // ARTX lookup omits productId; its unique userReference and the remaining
        // frozen purchase fields must still match before any settlement.
        if (($response['_protocol'] ?? '') === 'artx' && !empty($response['_lookup']) && !isset($response['product'])) return true;
        return (string)($response['product'] ?? '') === (string)($payload['product'] ?? '');
    }

    public function outcome(array $r): ?string
    {
        $status = $r['status'] ?? null;
        $code = is_array($status) ? ($status['id'] ?? null) : $status;
        if ($code === null || !is_numeric($code)) return null;
        $code = (int) $code;
        // 59 is described as type 2 in the appendix but explicitly remains pending in the lifecycle.
        if (in_array($code, [9, 46, 59], true)) return null;
        if (($r['_protocol'] ?? '') === 'artx') {
            if (empty($r['_lookup']) && ($r['simulation'] ?? null) !== false) return null;
            if (($r['simulation'] ?? false) || ($r['errno'] ?? -1) != 0 && !empty($r['_lookup'])) return null;
            if ($code === 0) return 'success';
            // Unknown errors and missing/duplicate references require reconciliation.
            if (in_array($code, [1,14,22,48,50,69,70,71,78], true)) return null;
            return in_array($code, [2,3,4,6,7,8,11,12,13,15,19,20,24,25,27,29,32,34,35,36,37,39,41,42,43,47,52,53,56,57,58,60,61,63,65,66,67,68,72,73,74,75,76,77], true) && (int)($status['type'] ?? -1) === 2 ? 'reversed' : null;
        }
        if ($code === 0) return 'success';
        $failures = [1,2,3,4,6,7,8,13,14,15,20,24,27,29,32,35,36,37,39,41,42,43,48,50,53,56,57,58,60,61,63,65,66,67,68,70,71,72,73,74,75,76,77];
        return in_array($code, $failures, true) ? 'reversed' : null;
    }
}
