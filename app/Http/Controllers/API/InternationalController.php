<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\InternationalTopup;
use App\Services\Sochitel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Crypt};
class InternationalController extends Controller
{
    public function __construct(private InternationalTopup $service)
    {
    }
    private function input(Request $r): array
    {
        return $r->validate(['provider' => 'sometimes|in:vtugate,sochitel', 'product_id' => 'nullable|integer|min:1', 'country_code' => 'required|string|size:2|regex:/^[A-Z]{2}$/', 'type' => 'required|in:airtime,data', 'operator_id' => 'required|integer|min:1', 'amount' => 'required|numeric|min:0.01|max:100000000|regex:/^\d+(\.\d{1,2})?$/', 'phone' => 'required|string|max:20|regex:/^\+?[0-9 ]+$/']);
    }
    public function countries()
    {
        return response()->json(['data' => $this->service->countries()]);
    }
    public function operators(Request $r)
    {
        $v = $r->validate(['country_code' => 'required|string|size:2|regex:/^[A-Z]{2}$/', 'type' => 'required|in:airtime,data']);
        return response()->json(['data' => $this->service->catalog($v['country_code'], $v['type'])]);
    }
    public function preview(Request $r)
    {
        return response()->json(['data' => $this->service->preview($this->input($r))]);
    }
    public function quote(Request $r)
    {
        $q = $this->service->quote($r->user()->id, $this->input($r));
        return response()->json(['data' => ['id' => $q->id, 'amount_kobo' => $q->amount_kobo, 'phone' => $q->recipient_number, 'operator_name' => $q->operator_name, 'expires_at' => $q->expires_at]]);
    }
    public function purchase(Request $r)
    {
        $v = $r->validate(['quote_id' => 'required|uuid']);
        return response()->json(['data' => $this->service->publicRow($this->service->purchase($r->user()->id, $v['quote_id']))]);
    }
    public function history(Request $r)
    {
        return response()->json(['data' => DB::table('international_purchases')->where('user_id', $r->user()->id)->orderByDesc('id')->limit(30)->get()->map(fn($row) => $this->service->publicRow($row))]);
    }
    public function status(Request $r, string $reference)
    {
        $row = DB::table('international_purchases')->where('user_id', $r->user()->id)->where('reference', $reference)->first();
        abort_unless($row, 404);
        return response()->json(['data' => $this->service->publicRow($this->service->refresh($row))]);
    }
    public function settings()
    {
        $s = $this->service->settings();
        $sochitel = app(Sochitel::class);
        return response()->json(['data' => [
            'enabled' => (bool) $s->enabled, 'provider' => $this->service->provider($s),
            'multiplier' => $s->multiplier, 'calling_codes' => json_decode($s->calling_codes ?? '{}', true),
            'api_key_configured' => (bool) ($s->api_key || config('international.api_key')),
            'sochitel_protocol' => $sochitel->protocol($s),
            'sochitel_username' => $s->sochitel_username ?? null,
            'sochitel_password_configured' => !empty($s->sochitel_password),
            'sochitel_artx_url_configured' => (bool) config('international.sochitel_artx_url'),
            'sochitel_base_url_configured' => (bool) config('international.sochitel_base_url'),
            'sochitel_user_id' => $s->sochitel_user_id, 'sochitel_currency' => $s->sochitel_currency,
            'sochitel_ngn_rate' => $s->sochitel_ngn_rate, 'sochitel_key_configured' => (bool) $s->sochitel_private_key,
            'sochitel_verified' => $s->sochitel_verification_hash && hash_equals($s->sochitel_verification_hash, $sochitel->verificationHash($s)),
            'sochitel_verified_at' => $s->sochitel_verified_at, 'sochitel_logos' => json_decode($s->sochitel_logos ?? '{}', true),
        ]]);
    }
    private function auditSettings(object $s): array
    {
        $values = (array) $s;
        unset($values['sochitel_password'], $values['api_key'], $values['sochitel_private_key'], $values['sochitel_verification_hash']);
        return $values;
    }
    public function updateSettings(Request $r)
    {
        $v = $r->validate([
            'enabled' => 'required|boolean', 'provider' => 'sometimes|in:vtugate,sochitel',
            'multiplier' => 'required|numeric|min:1|max:10|regex:/^\d+(\.\d{1,4})?$/',
            'api_key' => 'nullable|string|max:1000', 'calling_codes' => 'present|array|max:300',
            'calling_codes.*' => 'required|string|regex:/^\+[1-9][0-9]{0,3}$/',
            'sochitel_protocol' => 'sometimes|in:artx,rest',
            'sochitel_username' => 'nullable|string|max:255',
            'sochitel_password' => 'nullable|string|max:1000',
            'sochitel_user_id' => 'nullable|string|max:100|regex:/^[0-9]+$/',
            'sochitel_private_key' => 'nullable|string|max:10000',
            'sochitel_currency' => 'sometimes|required|string|size:3|regex:/^[A-Z]{3}$/',
            'sochitel_ngn_rate' => 'sometimes|required|numeric|min:0.000001|max:1000000|regex:/^\d+(\.\d{1,6})?$/',
            'sochitel_logos' => 'sometimes|array|max:1000', 'sochitel_logos.*' => 'required|url|max:2048|starts_with:https://',
        ]);
        foreach ($v['calling_codes'] as $iso => $code) abort_unless(preg_match('/^[A-Z]{2}$/', $iso), 422, 'Use two-letter uppercase country codes.');
        foreach ($v['sochitel_logos'] ?? [] as $id => $logo) abort_unless(ctype_digit((string) $id), 422, 'Use numeric Sochitel operator IDs for logos.');
        if (!empty($v['sochitel_private_key'])) {
            $key = openssl_pkey_get_private($v['sochitel_private_key']);
            $details = $key ? openssl_pkey_get_details($key) : false;
            abort_unless($details && $details['type'] === OPENSSL_KEYTYPE_RSA && $details['bits'] >= 4096, 422, 'Enter a valid unencrypted RSA private key of at least 4096 bits.');
        }
        DB::transaction(function () use ($r, $v) {
            $s = $this->service->settings(true);
            $values = ['enabled' => $v['enabled'], 'provider' => $v['provider'] ?? $this->service->provider($s), 'multiplier' => $v['multiplier'], 'calling_codes' => json_encode($v['calling_codes']), 'updated_at' => now()];
            foreach (['sochitel_protocol', 'sochitel_username', 'sochitel_user_id', 'sochitel_currency', 'sochitel_ngn_rate'] as $field) if (isset($v[$field])) $values[$field] = $v[$field];
            if (isset($v['sochitel_logos'])) $values['sochitel_logos'] = json_encode($v['sochitel_logos']);
            if (!empty($v['api_key'])) $values['api_key'] = Crypt::encryptString($v['api_key']);
            if (!empty($v['sochitel_private_key'])) $values['sochitel_private_key'] = Crypt::encryptString($v['sochitel_private_key']);
            if (!empty($v['sochitel_password'])) $values['sochitel_password'] = Crypt::encryptString($v['sochitel_password']);
            $next = (object) array_merge((array) $s, $values);
            if ($next->sochitel_currency === 'NGN') $values['sochitel_ngn_rate'] = $next->sochitel_ngn_rate = 1;
            if (app(Sochitel::class)->account($next) !== app(Sochitel::class)->account($s) || $next->sochitel_currency !== $s->sochitel_currency) {
                abort_if(\Illuminate\Support\Facades\Schema::hasTable('internet_purchases') && DB::table('internet_purchases')->where('provider', 'sochitel')->where('status', 'pending')->exists(), 422, 'Resolve pending Sochitel Internet purchases before changing accounts or protocol.');
                abort_if(DB::table('international_purchases')->where('provider', 'sochitel')->whereIn('status', ['pending', 'processing'])->exists(), 422, 'Resolve pending Sochitel purchases before changing its account or protocol.');
            }
            if ($next->enabled) {
                if ($next->provider === 'vtugate') abort_unless($next->api_key || config('international.api_key'), 422, 'Configure the VTUgate API key first.');
                else abort_unless($s->sochitel_verification_hash && hash_equals($s->sochitel_verification_hash, app(Sochitel::class)->verificationHash($next)), 422, 'Save Sochitel credentials and test its connection before enabling this route.');
            }
            DB::table('international_settings')->where('id', 1)->update($values);
            DB::table('service_control_audits')->insert(['admin_id' => $r->user()->id, 'before_values' => json_encode($this->auditSettings($s)), 'after_values' => json_encode($this->auditSettings((object) $values) + ['service' => 'international', 'api_key_changed' => !empty($v['api_key']), 'sochitel_key_changed' => !empty($v['sochitel_private_key'])]), 'created_at' => now(), 'updated_at' => now()]);
        });
        return $this->settings();
    }
    public function testSochitel(Request $r)
    {
        try {
            $result = DB::transaction(function () use ($r) {
                $s = $this->service->settings(true);
                $provider = app(Sochitel::class);
                $result = $provider->verify($s);
                DB::table('international_settings')->where('id', 1)->update(['sochitel_verification_hash' => $provider->verificationHash($s), 'sochitel_verified_at' => now()]);
                DB::table('service_control_audits')->insert(['admin_id' => $r->user()->id, 'before_values' => '{}', 'after_values' => json_encode(['service' => 'international', 'sochitel_connection_verified' => true, 'currency' => $result['currency'], 'products' => $result['products']]), 'created_at' => now(), 'updated_at' => now()]);
                return $result;
            });
            return response()->json(['data' => $result]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            throw $e;
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Sochitel connection failed. Check the assigned API URL and saved credentials for the selected protocol; ask Sochitel whether a client TLS certificate is required.'], 422);
        }
    }
    public function transactions()
    {
        return response()->json(['data' => DB::table('international_purchases')->orderByDesc('id')->paginate(30)]);
    }
    public function resolve(Request $r, int $id)
    {
        $v = $r->validate(['outcome' => 'required|in:success,reversed', 'evidence' => 'required|string|min:15|max:2000']);
        $row = DB::table('international_purchases')->where('id', $id)->first();
        abort_unless($row, 404);
        abort_if(now()->diffInMinutes($row->created_at) < 5, 422, 'Wait five minutes for the provider response before resolving.');
        $result = DB::transaction(function () use ($r, $row, $v) {
            $result = $this->service->settle($row, $v['outcome']);
            DB::table('service_control_audits')->insert(['admin_id' => $r->user()->id, 'before_values' => json_encode(['international_purchase' => $row->id, 'status' => $row->status]), 'after_values' => json_encode($v), 'created_at' => now(), 'updated_at' => now()]);
            return $result;
        });
        return response()->json(['data' => $this->service->publicRow($result)]);
    }
}
