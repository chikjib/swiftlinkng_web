<?php
namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use App\Services\ServiceControls;
use App\Services\PayVesselBetting;
use App\Services\BettingFunding;
use App\Services\EsimPurchases;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ServiceAdminController extends Controller {
    private function settings(): array {
        $row=(array) ServiceControls::get();
        $row['betting_enabled']=(bool) $row['betting_enabled']; $row['esim_enabled']=(bool) $row['esim_enabled'];
        $row['betting_biller_ids']=json_decode($row['betting_biller_ids'] ?? '[]',true);
        $row['payvessel_configured']=(bool) (config('betting.api_key') && config('betting.api_secret'));
        $row['esim_configured']=(bool) config('esim.access_code');
        return $row;
    }
    public function show() { return response()->json(['data'=>$this->settings()]); }
    public function status() {
        $s=ServiceControls::get();
        return response()->json(['data'=>['betting_enabled'=>(bool) $s->betting_enabled,'esim_enabled'=>(bool) $s->esim_enabled]]);
    }
    public function update(Request $request) {
        $input=$request->validate([
            'betting_enabled'=>'required|boolean','esim_enabled'=>'required|boolean',
            'usd_ngn'=>['required','numeric','min:0','max:100000','regex:/^\d+(\.\d{1,4})?$/'],
            'esim_terms'=>'nullable|string|max:30000',
            'betting_biller_ids'=>'present|array|max:100', 'betting_biller_ids.*'=>['required','string','max:64','distinct','regex:/^[a-zA-Z0-9_-]+$/'],
        ]);
        abort_if($input['esim_enabled'] && ((float) $input['usd_ngn'] <= 0 || !config('esim.access_code') || trim($input['esim_terms'] ?? '') === ''),422,'Set a positive exchange rate, publish eSIM terms, and configure the eSIM Access code before enabling eSIM.');
        abort_if($input['betting_enabled'] && (!$input['betting_biller_ids'] || !config('betting.api_key') || !config('betting.api_secret')),422,'Configure PayVessel and select betting platforms before enabling betting.');
        DB::transaction(function () use ($request,$input) {
            $before=ServiceControls::get(true);
            $input['betting_biller_ids']=json_encode($input['betting_biller_ids']);
            DB::table('service_controls')->where('id',1)->update($input+['updated_at'=>now()]);
            DB::table('service_control_audits')->insert(['admin_id'=>$request->user()->id,'before_values'=>json_encode($before),'after_values'=>json_encode($input),'created_at'=>now(),'updated_at'=>now()]);
        });
        return response()->json(['data'=>$this->settings(),'message'=>'Service settings saved.']);
    }
    public function billers(PayVesselBetting $provider) { return response()->json(['data'=>$provider->request('GET','billers')]); }
    public function transactions(Request $request) {
        $input=$request->validate(['service'=>'required|in:betting,esim','status'=>'nullable|string|max:20','search'=>'nullable|string|max:100']);
        $query=DB::table($input['service'] === 'betting' ? 'betting_fundings' : 'esim_purchases');
        if ($request->filled('status')) $query->where('status',$input['status']);
        if ($request->filled('search')) $query->where(function ($q) use ($input) {
            $q->where('reference','like','%'.$input['search'].'%');
            if (ctype_digit($input['search'])) $q->orWhere('user_id',(int) $input['search']);
        });
        $fields=$input['service'] === 'betting' ? ['id','user_id','reference','biller_name','recharge_account','amount','status','created_at'] : ['id','user_id','reference','kind','package_name','amount_kobo','exchange_rate','provider_price','provider_order','status','error_code','created_at'];
        return response()->json(['data'=>$query->select($fields)->orderByDesc('id')->paginate(25)]);
    }
    public function recheck(Request $request,string $service,int $id,BettingFunding $betting,EsimPurchases $esim) {
        abort_unless(in_array($service,['betting','esim'],true),404);
        $row=DB::table($service === 'betting' ? 'betting_fundings' : 'esim_purchases')->where('id',$id)->first();
        abort_unless($row,404);
        $result=$service === 'betting' ? $betting->refresh($row) : $esim->refresh($row);
        return response()->json(['data'=>['status'=>$result->status],'message'=>'Provider status checked.']);
    }
    public function resolveTopup(Request $request,int $id,EsimPurchases $esim) {
        $input=$request->validate(['outcome'=>'required|in:success,reversed','evidence'=>'required|string|min:15|max:2000']);
        return Cache::lock('esim-purchase-'.$id,120)->block(5,function () use ($id,$input,$request,$esim) {
            return DB::transaction(function () use ($id,$input,$request,$esim) {
                $row=DB::table('esim_purchases')->where('id',$id)->first();
                abort_unless($row && $row->kind === 'topup' && in_array($row->status,['pending','processing'],true),422,'Only unresolved top-ups can be resolved manually.');
                $result=$esim->settle($row,$input['outcome'],'ADMIN_CONFIRMED');
                DB::table('service_control_audits')->insert(['admin_id'=>$request->user()->id,'before_values'=>json_encode(['purchase'=>$row->reference,'status'=>$row->status]),'after_values'=>json_encode($input),'created_at'=>now(),'updated_at'=>now()]);
                return response()->json(['data'=>['status'=>$result->status]]);
            });
        });
    }
    public function audits() { return response()->json(['data'=>DB::table('service_control_audits')->orderByDesc('id')->paginate(20)]); }
}
