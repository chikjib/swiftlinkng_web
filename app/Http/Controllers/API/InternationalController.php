<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\InternationalTopup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Crypt};
class InternationalController extends Controller
{
    public function __construct(private InternationalTopup $service)
    {
    }
    private function input(Request $r): array
    {
        return $r->validate(['country_code' => 'required|string|size:2|regex:/^[A-Z]{2}$/', 'type' => 'required|in:airtime,data', 'operator_id' => 'required|integer|min:1', 'amount' => 'required|numeric|min:0.01|max:100000000|regex:/^\d+(\.\d{1,2})?$/', 'phone' => 'required|string|max:20|regex:/^\+?[0-9 ]+$/']);
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
        return response()->json(['data' => ['enabled' => (bool) $s->enabled, 'multiplier' => $s->multiplier, 'calling_codes' => json_decode($s->calling_codes ?? '{}', true), 'api_key_configured' => (bool) ($s->api_key || config('international.api_key'))]]);
    }
    public function updateSettings(Request $r)
    {
        $v = $r->validate(['enabled' => 'required|boolean', 'multiplier' => 'required|numeric|min:1|max:10|regex:/^\d+(\.\d{1,4})?$/', 'api_key' => 'nullable|string|max:1000', 'calling_codes' => 'present|array|max:300', 'calling_codes.*' => 'required|string|regex:/^\+[1-9][0-9]{0,3}$/']);
        foreach ($v['calling_codes'] as $iso => $code) {
            abort_unless(preg_match('/^[A-Z]{2}$/', $iso), 422, 'Use two-letter uppercase country codes.');
        }
        DB::transaction(function () use ($r, $v) {
            $s = $this->service->settings(true);
            abort_if($v['enabled'] && !($v['api_key'] ?? $s->api_key ?? config('international.api_key')), 422, 'Configure the VTUgate API key first.');
            $values = ['enabled' => $v['enabled'], 'multiplier' => $v['multiplier'], 'calling_codes' => json_encode($v['calling_codes']), 'updated_at' => now()];
            if (!empty($v['api_key'])) {
                $values['api_key'] = Crypt::encryptString($v['api_key']);
            }
            DB::table('international_settings')->where('id', 1)->update($values);
            unset($values['api_key'], $s->api_key);
            DB::table('service_control_audits')->insert(['admin_id' => $r->user()->id, 'before_values' => json_encode($s), 'after_values' => json_encode($values + ['service' => 'international', 'api_key_changed' => !empty($v['api_key'])]), 'created_at' => now(), 'updated_at' => now()]);
        });
        return $this->settings();
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
