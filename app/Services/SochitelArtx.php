<?php

namespace App\Services;

use Illuminate\Support\Facades\{Crypt, Http, RateLimiter};

/** ARTX 5.9.6 wire protocol. The returned arrays use our internal provider model. */
class SochitelArtx
{
    public function command(object $s, string $command, array $params = []): array
    {
        $url = config('international.sochitel_artx_url', '');
        $parts = parse_url($url);
        abort_unless(($parts['scheme'] ?? '') === 'https' && !empty($parts['host']) && !isset($parts['user']) && !isset($parts['pass']) && !isset($parts['fragment']), 503, 'Set the assigned HTTPS SOCHITEL_ARTX_URL on the server.');
        abort_unless(!empty($s->sochitel_username) && !empty($s->sochitel_password), 503, 'Configure the ARTX username and password in admin.');
        $salt = bin2hex(random_bytes(20));
        $password = sha1(Crypt::decryptString($s->sochitel_password));
        $signed = $password.$salt.$command;
        foreach (['operator','productId','amount','amountOperator','msisdn','id','userReference'] as $field) {
            if (array_key_exists($field, $params)) $signed .= $params[$field];
        }
        $body = ['auth' => ['username' => $s->sochitel_username, 'salt' => $salt, 'password' => hash('sha512', $salt.$password), 'signature' => hash('sha512', $signed)], 'version' => 5, 'command' => $command] + $params;
        if ($command === 'execTransaction') abort_unless(RateLimiter::attempt('international-sochitel-requests', 60, fn() => true, 60), 429, 'Sochitel is busy. Try again in a minute.');
        $options = ['allow_redirects' => false];
        if (config('international.sochitel_artx_certificate')) $options['cert'] = config('international.sochitel_artx_certificate');
        if (config('international.sochitel_artx_ssl_key')) $options['ssl_key'] = config('international.sochitel_artx_ssl_key');
        $response = Http::acceptJson()->withOptions($options)->connectTimeout(10)->timeout(50)->post($url, $body);
        $r = $response->json();
        if (!$response->successful() || !is_array($r) || ($r['command'] ?? null) !== $command || !is_numeric($r['status']['id'] ?? null)) {
            throw new \RuntimeException('Sochitel returned an unreadable ARTX response.');
        }
        return $r;
    }

    public function request(object $s, string $method, string $path, array $body): array
    {
        if ($path === '/balance') {
            $r = $this->command($s, 'getBalance');
            return ['errno' => $r['status']['id'], 'balance' => $r['result']['value'] ?? null, 'currency' => $r['result']['currency'] ?? null];
        }
        if ($path === '/operators') {
            $r = $this->command($s, 'getOperators', array_intersect_key($body, array_flip(['country','productCategory','productType'])));
            abort_unless((int)$r['status']['id'] === 0 && is_array($r['result'] ?? null), 503, 'Sochitel operators unavailable.');
            $operators = [];
            foreach ($r['result'] as $operator) {
                if (!is_array($operator) || empty($operator['id'])) continue;
                $catalogue = $this->command($s, 'getOperatorProducts', ['operator' => $operator['id']] + (isset($body['productCategory']) ? ['productCategory' => $body['productCategory']] : []));
                abort_unless((int)$catalogue['status']['id'] === 0 && is_array($catalogue['result']['products'] ?? null), 503, 'Sochitel products unavailable.');
                abort_unless(($catalogue['result']['currency']['user'] ?? '') === $s->sochitel_currency, 503, 'Sochitel catalogue account currency mismatch.');
                $products = [];
                foreach ($catalogue['result']['products'] as $p) {
                    $fixed = ($p['priceType'] ?? '') === 'fixed';
                    $products[] = $p + ['type' => $p['productType']['id'] ?? null, 'category' => $p['productCategory']['id'] ?? null, 'amount' => ['type' => $p['priceType'] ?? '', 'min' => $fixed ? ($p['price'] ?? []) : ($p['price']['min'] ?? []), 'max' => $fixed ? ($p['price'] ?? []) : ($p['price']['max'] ?? [])]];
                }
                $operators[] = array_merge($operator, ['country' => $operator['country']['id'] ?? '', 'currency' => $catalogue['result']['currency']['operator'] ?? '', 'products' => $products]);
            }
            return ['errno' => 0, 'operators' => $operators];
        }
        if ($method === 'POST' && $path === '/transaction') {
            $recipient = $body['recipient'];
            $bill = ($body['_recipient_kind'] ?? '') === 'accountId';
            if (!$bill) {
                // Internet has Nigerian local numbers; international quotes are already normalized.
                if (str_starts_with($recipient, '0')) $recipient = '234'.substr($recipient, 1);
                abort_unless(preg_match('/^[1-9][0-9]{5,19}$/', $recipient), 422, 'Use an international phone number.');
            }
            abort_unless(preg_match('/^[a-zA-Z0-9]{1,30}$/', $body['reference']), 422, 'Invalid Sochitel transaction reference.');
            $params = ['operator' => $body['operator'], 'productId' => $body['product'], 'amountOperator' => $body['amount'], $bill ? 'accountId' : 'msisdn' => $recipient, 'userReference' => $body['reference'], 'simulate' => 0];
            $r = $this->command($s, 'execTransaction', $params);
            return $this->transaction($r, false, $s->sochitel_currency);
        }
        if (str_starts_with($path, '/transaction/user/')) {
            $reference = rawurldecode(substr($path, strlen('/transaction/user/')));
            $r = $this->command($s, 'getTransaction', ['userReference' => $reference]);
            return $this->transaction($r, true, $s->sochitel_currency);
        }
        throw new \InvalidArgumentException('Unsupported ARTX operation.');
    }

    private function transaction(array $r, bool $lookup, string $currency): array
    {
        $t = $r['result'] ?? [];
        if (!is_array($t)) $t = [];
        if (($t['currency']['user'] ?? '') !== $currency) $t = [];
        $extra = $t['extraParameters'] ?? [];
        // A successful lookup command does not imply a successful transaction.
        $status = $lookup ? ($t['status'] ?? null) : $r['status'];
        $recipient = $t['accountId'] ?? $t['msisdn'] ?? $extra['accountId'] ?? $extra['msisdn'] ?? '';
        // getTransaction does not document productId. Identity is instead checked by
        // its transaction ID plus userReference, recipient, operator, amount and currency.
        return ['_protocol' => 'artx', '_lookup' => $lookup, 'errno' => $r['status']['id'], 'id' => $t['id'] ?? null, 'reference' => $t['userReference'] ?? '', 'recipient' => (string)$recipient, 'product' => $t['productId'] ?? null, 'operator' => array_merge($t['operator'] ?? [], ['currency' => $t['currency']['operator'] ?? null]), 'amount' => $t['amount'] ?? [], 'status' => $status, 'simulation' => $t['simulation'] ?? null, 'instructions' => $t['instructions'] ?? $extra['instructions'] ?? '', 'pin' => $t['pin'] ?? $extra['pin'] ?? []];
    }
}
