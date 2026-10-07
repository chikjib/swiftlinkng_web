<?php
namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use App\Services\EsimAccess;
use App\Services\EsimPurchases;
use App\Services\ServiceControls;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class EsimController extends Controller {
    public function __construct(private EsimAccess $provider, private EsimPurchases $purchases) {}
    public function terms() { return response()->json(['data'=>['content'=>ServiceControls::get()->esim_terms ?? '']]); }
    public function locations() {
        ServiceControls::enabled('esim');
        return response()->json(['data'=>Cache::remember('esim-locations',3600,fn () => $this->provider->data('location/list')['locationList'] ?? [])]);
    }
    public function packages(Request $request) {
        $settings=ServiceControls::enabled('esim');
        $input=$request->validate(['location'=>'nullable|string|max:30','profile_id'=>'nullable|integer']);
        $profile=isset($input['profile_id']) ? $this->purchases->profile($request->user()->id,$input['profile_id']) : null;
        $list=$this->provider->packages($input['location'] ?? '',$profile);
        $result=[];
        foreach ($list as $p) {
            if (($p['currencyCode'] ?? '') !== 'USD' || (int) ($p['price'] ?? 0) <= 0) continue;
            $result[]=[
                'package_code'=>$p['packageCode'],'name'=>$p['name'],'amount_kobo'=>ServiceControls::kobo((int) $p['price'],(string) $settings->usd_ngn),
                'volume'=>$p['volume'],'duration'=>$p['duration'],'duration_unit'=>$p['durationUnit'],
                'location'=>$p['location'],'description'=>$p['description'] ?? '',
                'daily'=>(int) ($p['dataType'] ?? 1) !== 1,'networks'=>$p['locationNetworkList'] ?? [],
                'speed'=>$p['speed'] ?? '', 'activation_type'=>$p['activeType'] ?? null,
                'topup_supported'=>in_array((int) ($p['supportTopUpType'] ?? 1),[2,3],true),
                'fup_policy'=>$p['fupPolicy'] ?? '',
            ];
        }
        return response()->json(['data'=>$result]);
    }
    public function quote(Request $request) {
        $input=$request->validate(['package_code'=>'required|string|max:100','location'=>'nullable|string|max:30','profile_id'=>'nullable|integer','period_num'=>'nullable|integer|min:1|max:365']);
        $q=$this->purchases->quote($request->user()->id,$input);
        return response()->json(['data'=>['id'=>$q->id,'amount_kobo'=>$q->amount_kobo,'package_name'=>$q->package_name,'expires_at'=>$q->expires_at,'kind'=>$q->kind]]);
    }
    public function purchase(Request $request) {
        $input=$request->validate(['quote_id'=>'required|uuid','reference'=>['required','regex:/^SLE[a-f0-9]{28}$/']]);
        $row=$this->purchases->purchase($request->user()->id,$input['quote_id'],$input['reference']);
        return response()->json(['data'=>$this->purchases->publicPurchase($row)]);
    }
    public function history(Request $request) {
        $rows=DB::table('esim_purchases')->where('user_id',$request->user()->id)->orderByDesc('id')->limit(50)->get();
        return response()->json(['data'=>$rows->map(fn ($row) => $this->purchases->publicPurchase($row))]);
    }
    public function status(Request $request,string $reference) {
        $row=DB::table('esim_purchases')->where('user_id',$request->user()->id)->where('reference',$reference)->first();
        abort_unless($row,404,'Purchase not found.');
        return response()->json(['data'=>$this->purchases->publicPurchase($this->purchases->refresh($row))]);
    }
    public function profiles(Request $request) {
        $rows=DB::table('esim_profiles')->where('user_id',$request->user()->id)->orderByDesc('id')->get();
        return response()->json(['data'=>$rows->map(fn ($row) => $this->purchases->publicProfile($row))]);
    }
    public function details(Request $request,int $id) {
        $row=$this->purchases->profile($request->user()->id,$id);
        $stale=false;
        try { $row=$this->purchases->syncProfile($row); } catch (\Throwable $e) { $stale=true; }
        return response()->json(['data'=>$this->purchases->publicProfile($row)+['stale'=>$stale]]);
    }
}
