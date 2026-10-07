<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\BettingFunding;
use App\Services\PayVesselBetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BettingController extends Controller
{
    public function __construct(private PayVesselBetting $provider, private BettingFunding $funding) {}

    private function input(Request $request): array
    {
        return $request->validate([
            'biller_id' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'item_id' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'recharge_account' => ['required', 'string', 'max:15'],
        ]);
    }

    public function billers() { return response()->json(['data' => $this->provider->billers()]); }
    public function items(string $biller) { return response()->json(['data' => $this->provider->items($biller)]); }
    public function verify(Request $request) {
        $input = $this->input($request);
        $this->provider->biller($input['biller_id']);
        return response()->json(['data' => ['account_name' => $this->provider->verify($input)]]);
    }
    public function accounts(Request $request) {
        return response()->json(['data' => DB::table('betting_accounts')->where('user_id', $request->user()->id)->orderByDesc('updated_at')->get()]);
    }
    public function saveAccount(Request $request) {
        $input = $this->input($request);
        $biller = $this->provider->biller($input['biller_id']);
        $name = $this->provider->verify($input);
        DB::table('betting_accounts')->upsert([[
            'user_id' => $request->user()->id, 'biller_id' => $input['biller_id'],
            'recharge_account' => $input['recharge_account'], 'biller_name' => $biller['biller_name'],
            'account_name' => $name, 'created_at' => now(), 'updated_at' => now(),
        ]], ['user_id', 'biller_id', 'recharge_account'], ['biller_name', 'account_name', 'updated_at']);
        return $this->accounts($request);
    }
    public function deleteAccount(Request $request, int $account) {
        DB::table('betting_accounts')->where('user_id', $request->user()->id)->where('id', $account)->delete();
        return response()->json(['data' => []]);
    }
    public function purchase(Request $request) {
        $input = $this->input($request) + $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'reference' => ['required', 'string', 'max:32', 'regex:/^SLB[a-f0-9]{28}$/'],
        ]);
        return response()->json(['data' => $this->funding->purchase($request->user()->id, $input)]);
    }
    public function status(Request $request, string $reference) {
        $row = DB::table('betting_fundings')->where('user_id', $request->user()->id)->where('reference', $reference)->first();
        abort_unless($row, 404, 'Payment not found.');
        return response()->json(['data' => $this->funding->refresh($row)]);
    }
    public function history(Request $request) {
        return response()->json(['data' => DB::table('betting_fundings')->where('user_id', $request->user()->id)->orderByDesc('id')->limit(20)->get()]);
    }
}
