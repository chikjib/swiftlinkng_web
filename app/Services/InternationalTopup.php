<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\{DB, Http, Cache, Crypt};
use Illuminate\Support\Str;
class InternationalTopup
{
    public function settings(bool $lock = false): object
    {
        $q = DB::table('international_settings')->where('id', 1);
        if ($lock) {
            $q->lockForUpdate();
        }
        $s = $q->first();
        abort_unless($s, 503, 'International top-up is being configured.');
        return $s;
    }
    public function enabled(): object
    {
        $s = $this->settings();
        abort_unless($s->enabled, 503, 'International top-up is currently unavailable.');
        return $s;
    }
    public function provider(?object $s = null): string
    {
        return ($s ?? $this->settings())->provider ?? 'vtugate';
    }
    private function selectionCost(array $in, object $s, bool $fresh = false): array
    {
        if ($this->provider($s) === 'sochitel') {
            return app(Sochitel::class)->selection($s, $in, $fresh);
        }
        return ['cost' => $this->data('fxrate', ['operator_id' => $in['operator_id'], 'amount' => $in['amount']])['charged_to_user'] ?? null];
    }
    public function request(string $path, array $body = []): array
    {
        $s = $this->settings();
        $key = $s->api_key ? Crypt::decryptString($s->api_key) : config('international.api_key');
        abort_unless($key, 503, 'Configure the VTUgate API key in admin.');
        abort_unless(\Illuminate\Support\Facades\RateLimiter::attempt('international-provider-requests', 60, fn() => true, 60), 429, 'International service is busy. Try again in a minute.');
        $r = Http::asForm()->acceptJson()->withToken($key)->connectTimeout(10)->timeout(50)->post(config('international.base_url') . '/' . $path, $body);
        if (!$r->successful() || !is_array($r->json())) {
            throw new \RuntimeException('International provider unavailable.');
        }
        return $r->json();
    }
    public function data(string $path, array $body = []): array
    {
        $r = $this->request($path, $body);
        abort_unless(($r['status'] ?? false) === true && is_array($r['data'] ?? null), 503, 'International service details are unavailable.');
        return $r['data'];
    }
    public static function price($cost, $multiplier): int
    {
        // Decimal input to integer arithmetic: round up to a kobo, never a naira.
        abort_unless(is_numeric($cost) && $cost > 0 && $cost <= 100000000 && is_numeric($multiplier) && $multiplier >= 1 && $multiplier <= 10, 503, 'International pricing unavailable.');
        $kobo = (int) round((float) $cost * 100);
        $rate = (int) round((float) $multiplier * 10000);
        return intdiv($kobo * $rate + 9999, 10000);
    }
    public function countries(): array
    {
        $this->enabled();
        $s = $this->settings();
        if ($this->provider($s) === 'sochitel') return app(Sochitel::class)->countries($s);
        $codes = json_decode($s->calling_codes ?? '{}', true);
        return array_map(function ($c) use ($codes) {
            $iso = $c['isoName'];
            return ['code' => $iso, 'name' => $c['name'], 'calling_code' => $codes[$iso] ?? $c['callingCodes'][0] ?? '', 'flag' => $c['flag'] ?? null];
        }, Cache::remember('international-countries', 3600, fn() => $this->data('countries')));
    }
    public function operators(string $country, string $type): array
    {
        $this->enabled();
        $raw = Cache::remember('international-operators-' . $country . '-' . $type, 300, fn() => $this->data('operators', ['country_code' => $country] + ($type === 'data' ? ['type' => 'data'] : [])));
        return array_values(array_filter($raw, fn($o) => $type === 'data' ? !empty($o['data']) || !empty($o['bundle']) : empty($o['data']) && empty($o['bundle'])));
    }
    private function amountKey($amount): string
    {
        $value = trim((string) $amount);
        if (!preg_match('/^\+?(\d+)(?:\.(\d+))?$/', $value, $match)) {
            return $value;
        }
        $whole = ltrim($match[1], '0') ?: '0';
        $fraction = rtrim($match[2] ?? '', '0');
        return $whole . ($fraction === '' ? '' : '.' . $fraction);
    }

    private function descriptionMap(array $descriptions): array
    {
        $names = [];
        foreach ($descriptions as $amount => $description) {
            if (!is_string($description)) continue;
            $name = trim(preg_replace('/\s+/u', ' ', $description) ?? '');
            if ($name !== '') $names[$this->amountKey($amount)] = $name;
        }
        return $names;
    }

    public function catalog(string $country, string $type): array
    {
        $s = $this->enabled();
        if ($this->provider($s) === 'sochitel') {
            return array_map(function ($o) { unset($o['_operator'], $o['_products']); return $o; }, app(Sochitel::class)->catalog($s, $country, $type));
        }
        return array_map(function ($o) use ($country, $type) {
            $plans = [];
            $localAmounts = array_values($o['localFixedAmounts'] ?? []);
            $senderAmounts = array_values($o['fixedAmounts'] ?? []);
            $localNames = $this->descriptionMap($o['localFixedAmountsDescriptions'] ?? []);
            $senderNames = $this->descriptionMap($o['fixedAmountsDescriptions'] ?? []);
            foreach ($localAmounts as $index => $amount) {
                // Provider descriptions may use "50.00" while the amount array contains 50.
                $name = $localNames[$this->amountKey($amount)] ?? null;
                // Sender and local denomination arrays describe the same products in order.
                // Use this correspondence only when both arrays have the same length.
                if ($name === null && count($senderAmounts) === count($localAmounts)) {
                    $name = $senderNames[$this->amountKey($senderAmounts[$index])] ?? null;
                }
                $plans[] = ['amount' => $amount, 'name' => $name ?? ($type === 'data' ? $o['name'] . ' bundle — ' . $amount . ' ' . ($o['destinationCurrencyCode'] ?? '') : $amount . ' ' . ($o['destinationCurrencyCode'] ?? ''))];
            }
            $logos = array_values(array_filter($o['logoUrls'] ?? [], fn($url) => is_string($url) && str_starts_with($url, 'https://')));
            $logo = collect($logos)->first(fn($url) => preg_match('/\.(png|jpe?g|webp)(\?|$)/i', $url)) ?? ($logos[0] ?? null);
            $fallbackLogo = null;
            if ($country === 'NG') {
                $name = strtolower($o['name']);
                foreach (['mtn'=>'mtn.jpeg','airtel'=>'airtel.jpeg','glo'=>'glodata.jpeg','9mobile'=>'9mobile.jpeg','t2 mobile'=>'9mobile.jpeg','etisalat'=>'9mobile.jpeg'] as $brand=>$file) {
                    if (str_contains($name, $brand)) { $fallbackLogo = '/template/images/services/'.$file; break; }
                }
            }
            return ['provider' => 'vtugate', 'id' => $o['operatorId'] ?? $o['id'], 'name' => $o['name'], 'logo' => $logo ?? $fallbackLogo, 'fallback_logo' => $fallbackLogo, 'currency' => $o['destinationCurrencyCode'] ?? '', 'min' => $o['localMinAmount'] ?? (count($plans) ? min(array_column($plans, 'amount')) : null), 'max' => $o['localMaxAmount'] ?? (count($plans) ? max(array_column($plans, 'amount')) : null), 'plans' => $plans];
        }, $this->operators($country, $type));
    }
    public function validateSelection(array $in): array
    {
        $country = collect($this->countries())->firstWhere('code', $in['country_code']);
        abort_unless($country && $country['calling_code'], 422, 'Country calling code unavailable.');
        $s = $this->settings();
        $provider = $this->provider($s);
        abort_unless(($in['provider'] ?? 'vtugate') === $provider, 422, 'International route changed. Select the country and operator again.');
        if ($provider === 'sochitel') {
            $o = app(Sochitel::class)->selection($s, $in)['operator'];
        } else {
            $o = collect($this->operators($in['country_code'], $in['type']))->first(fn($o) => (int) ($o['operatorId'] ?? $o['id']) === (int) $in['operator_id']);
            abort_unless($o, 422, 'Select an available operator.');
            $amount = (float) $in['amount'];
            $fixed = $o['localFixedAmounts'] ?? [];
            if (($o['denominationType'] ?? 'FIXED') === 'FIXED') {
                abort_unless(in_array($amount, $fixed), 422, 'Select an available plan or denomination.');
            } else {
                abort_unless(isset($o['localMinAmount'], $o['localMaxAmount']) && $amount >= $o['localMinAmount'] && $amount <= $o['localMaxAmount'], 422, 'Amount is outside this operator’s limits.');
            }
        }
        $digits = preg_replace('/\D/', '', $in['phone']);
        $prefix = ltrim($country['calling_code'], '+');
        if (!str_starts_with($digits, $prefix)) {
            $digits = $prefix . ltrim($digits, '0');
        }
        abort_unless(preg_match('/^[1-9][0-9]{6,14}$/', $digits), 422, 'Enter a valid phone number.');
        return [$o, $digits];
    }
    public function preview(array $in): array
    {
        [$o] = $this->validateSelection($in);
        $s = $this->settings();
        $r = $this->selectionCost($in, $s);
        return ['amount_kobo' => self::price($r['cost'], $s->multiplier)];
    }
    public function quote(int $user, array $in): object
    {
        return DB::transaction(function () use ($user, $in) {
            $this->settings(true);
            [$o, $phone] = $this->validateSelection($in);
            $s = $this->enabled();
            $r = $this->selectionCost($in, $s);
            $id = (string) Str::uuid();
            DB::table('international_quotes')->insert(['id' => $id, 'user_id' => $user, 'country_code' => $in['country_code'], 'type' => $in['type'], 'recipient_number' => $phone, 'operator_id' => $in['operator_id'], 'operator_name' => $o['name'], 'local_amount' => $in['amount'], 'provider' => $this->provider($s), 'provider_account' => $this->provider($s) === 'sochitel' ? app(Sochitel::class)->account($s) : null, 'product_id' => $in['product_id'] ?? null, 'provider_payload' => isset($r['payload']) ? json_encode($r['payload']) : null, 'provider_price' => $r['cost'], 'multiplier' => $s->multiplier, 'amount_kobo' => self::price($r['cost'], $s->multiplier), 'expires_at' => now()->addMinutes(2), 'created_at' => now(), 'updated_at' => now()]);
            return DB::table('international_quotes')->where('id', $id)->first();
        });
    }
    public function purchase(int $userId, string $quoteId): object
    {
        $row = DB::transaction(function () use ($userId, $quoteId) {
            $s = $this->settings(true);
            $user = User::whereKey($userId)->lockForUpdate()->firstOrFail();
            $q = DB::table('international_quotes')->where('id', $quoteId)->where('user_id', $userId)->lockForUpdate()->first();
            abort_unless($q, 404, 'Quote not found.');
            $old = DB::table('international_purchases')->where('quote_id', $quoteId)->first();
            if ($old) {
                return $old;
            }
            abort_unless($s->enabled, 503, 'International top-up is currently unavailable.');
            abort_if(now()->greaterThan($q->expires_at), 422, 'Quote expired. Please preview again.');
            // Refresh provider cost immediately before reserving funds. A change requires a fresh customer confirmation.
            abort_unless(($q->provider ?? 'vtugate') === $this->provider($s), 422, 'International route changed. Please select and confirm again.');
            if ($q->provider === 'sochitel') abort_unless($q->provider_account === app(Sochitel::class)->account($s), 422, 'Provider account changed. Please confirm again.');
            $r = $this->selectionCost(['country_code' => $q->country_code, 'type' => $q->type, 'operator_id' => $q->operator_id, 'product_id' => $q->product_id, 'amount' => $q->local_amount], $s, true);
            if ($q->provider === 'sochitel') abort_unless(json_decode($q->provider_payload, true) === $r['payload'], 422, 'Product changed. Please select and confirm again.');
            abort_unless(self::price($r['cost'] ?? null, $s->multiplier) === (int) $q->amount_kobo, 422, 'Price changed. Please preview and confirm the new price.');
            abort_unless((int) round($user->wallet * 100) >= $q->amount_kobo, 422, 'Insufficient wallet balance.');
            $amount = number_format($q->amount_kobo / 100, 2, '.', '');
            $before = $user->wallet;
            $user->decrement('wallet', $amount);
            $ref = 'SLI' . strtoupper(Str::random(27));
            $order = DB::table('orders')->insertGetId(['ref' => $ref, 'user_id' => $userId, 'subcategory_id' => $s->subcategory_id, 'plan' => $q->operator_name, 'amount' => $amount, 'quantity' => 1, 'subtotal' => $amount, 'total' => $amount, 'phone' => $q->recipient_number, 'channel' => 'App', 'prev_bal' => $before, 'bal' => $before - $amount, 'description' => 'International ' . $q->type, 'status' => 0, 'created_at' => now(), 'updated_at' => now()]);
            $id = DB::table('international_purchases')->insertGetId(['provider' => $q->provider, 'user_id' => $userId, 'quote_id' => $quoteId, 'order_id' => $order, 'reference' => $ref, 'amount_kobo' => $q->amount_kobo, 'created_at' => now(), 'updated_at' => now()]);
            return DB::table('international_purchases')->where('id', $id)->first();
        });
        // Only the first caller may dispatch. Never resend after a timeout or process crash.
        $claimed = DB::table('international_purchases')->where('id', $row->id)->where('status', 'pending')->update(['status' => 'processing', 'updated_at' => now()]);
        if (!$claimed) {
            return DB::table('international_purchases')->where('id', $row->id)->first();
        }
        try {
            $q = DB::table('international_quotes')->where('id', $quoteId)->first();
            if ($q->provider === 'sochitel') {
                $s = $this->settings();
                abort_unless($q->provider_account === app(Sochitel::class)->account($s), 503, 'Provider account changed.');
                $payload = json_decode($q->provider_payload, true, 512, JSON_THROW_ON_ERROR);
                $r = app(Sochitel::class)->request($s, 'POST', '/transaction', $payload + ['recipient' => $q->recipient_number, 'amount' => (string) $q->local_amount, 'reference' => $row->reference]);
                // These documented request rejections create no transaction. Duplicate references
                // and ambiguous server errors must instead be recovered by reference lookup.
                if (app(Sochitel::class)->protocol($s) === 'rest' && empty($r['id']) && in_array((int) ($r['errno'] ?? -1), [17,101,102,105,106,107,108,109,110,111], true)) {
                    DB::table('international_purchases')->where('id', $row->id)->update(['details' => json_encode(['rejection_code' => (int) $r['errno']])]);
                    return $this->settle($row, 'reversed');
                }
                return $this->recordSochitel($row, $q, $r);
            }
            $r = $this->request('topup', ['operator_id' => $q->operator_id, 'amount' => $q->local_amount, 'country_code' => $q->country_code, 'recipient_number' => $q->recipient_number]);
            $data = $r['data'] ?? [];
            if (!empty($data['transaction_id'])) {
                DB::table('international_purchases')->where('id', $row->id)->update(['transaction_id' => $data['transaction_id'], 'details' => json_encode($data)]);
            }
            if (($r['status'] ?? null) === true && ($data['provider_status'] ?? null) === true) {
                $this->settle($row, 'success');
            } elseif (!empty($data['transaction_id'])) {
                $this->refresh(DB::table('international_purchases')->where('id', $row->id)->first());
            }
        } catch (\Throwable $e) {
        }
        return DB::table('international_purchases')->where('id', $row->id)->first();
    }
    public function refresh(object $row): object
    {
        if (!in_array($row->status, ['pending', 'processing'])) {
            return $row;
        }
        try {
            if (($row->provider ?? 'vtugate') === 'sochitel') {
                $q = DB::table('international_quotes')->where('id', $row->quote_id)->first();
                $s = $this->settings();
                abort_unless($q->provider_account === app(Sochitel::class)->account($s), 503, 'Restore the original Sochitel account to check this purchase.');
                $r = app(Sochitel::class)->request($s, 'GET', '/transaction/user/' . rawurlencode($row->reference));
                return $this->recordSochitel($row, $q, $r);
            }
            if (!$row->transaction_id) return $row;
            $r = $this->data('topupstatus', ['transaction_id' => $row->transaction_id]);
            if (($r['status'] ?? '') === 'SUCCESSFUL') {
                return $this->settle($row, 'success');
            }
            if (in_array($r['status'] ?? '', ['REFUNDED', 'FAILED'], true)) {
                return $this->settle($row, 'reversed');
            }
        } catch (\Throwable $e) {
        }
        return $row;
    }
    private function recordSochitel(object $row, object $q, array $r): object
    {
        // Match the provider response against the exact frozen product and payment.
        $payload = json_decode($q->provider_payload ?? '{}', true);
        if ((!empty($row->transaction_id) && (string)$row->transaction_id !== (string)($r['id'] ?? '')) || empty($r['id']) || !ctype_digit((string) $r['id']) ||
            strcasecmp((string) ($r['reference'] ?? ''), $row->reference) !== 0 ||
            (string) ($r['recipient'] ?? '') !== $q->recipient_number ||
            !app(Sochitel::class)->productMatches($r, $payload) ||
            (string) ($r['operator']['id'] ?? '') !== ($payload['operator'] ?? '') ||
            ($r['operator']['currency'] ?? '') !== ($payload['currency'] ?? '') ||
            !is_numeric($r['amount']['operator'] ?? null) ||
            abs((float) $r['amount']['operator'] - (float) $q->local_amount) > 0.001) {
            return DB::table('international_purchases')->where('id', $row->id)->first();
        }
        $pin = is_array($r['pin'] ?? null) ? $r['pin'] : [];
        $details = ['provider_status' => $r['status'] ?? null, 'pin_detail' => trim(implode(' ', array_filter([$pin['number'] ?? '', $pin['serial'] ?? '', $r['instructions'] ?? '']))) ?: null];
        DB::table('international_purchases')->where('id', $row->id)->update(['transaction_id' => $r['id'], 'details' => json_encode($details), 'updated_at' => now()]);
        $outcome = app(Sochitel::class)->outcome($r);
        if ($outcome === 'success' && (int) ($r['errno'] ?? -1) !== 0) $outcome = null;
        return $outcome ? $this->settle($row, $outcome) : DB::table('international_purchases')->where('id', $row->id)->first();
    }
    public function settle(object $row, string $status): object
    {
        return DB::transaction(function () use ($row, $status) {
            $u = User::whereKey($row->user_id)->lockForUpdate()->firstOrFail();
            $r = DB::table('international_purchases')->where('id', $row->id)->lockForUpdate()->first();
            if (in_array($r->status, ['success', 'reversed'])) {
                return $r;
            }
            if ($status === 'reversed') {
                $u->increment('wallet', number_format($r->amount_kobo / 100, 2, '.', ''));
            }
            DB::table('international_purchases')->where('id', $r->id)->update(['status' => $status, 'updated_at' => now()]);
            DB::table('orders')->where('id', $r->order_id)->update(['status' => $status === 'success' ? 1 : 2, 'description' => 'International top-up: ' . $status, 'updated_at' => now()]);
            return DB::table('international_purchases')->where('id', $r->id)->first();
        });
    }
    public function publicRow(object $r): array
    {
        $q = DB::table('international_quotes')->where('id', $r->quote_id)->first();
        return ['reference' => $r->reference, 'quote_id' => $r->quote_id, 'status' => $r->status, 'amount_kobo' => $r->amount_kobo, 'operator_name' => $q->operator_name, 'phone' => $q->recipient_number, 'type' => $q->type, 'created_at' => $r->created_at, 'pin_detail' => json_decode($r->details ?? '{}', true)['pin_detail'] ?? null];
    }
}
