<?php
namespace App\Http\Controllers\API;
use App\Models\Subcategory;
use App\Services\InternetService;
use Illuminate\Http\Request;
class InternetController extends BaseController {
    public function catalogue(InternetService $service) {
        $data=$service->catalogue($this->user);
        foreach ($data as &$provider) foreach ($provider['products'] as &$p) $p=array_intersect_key($p,array_flip(['plan','name','amount']));
        return response()->json(['data'=>$data])->header('Cache-Control','no-store');
    }
    public function purchase(Request $r, InternetService $service) {
        $in=$r->validate(['subcategory_id'=>'required|integer','plan_id'=>'required|string|max:255','phonenumber'=>'required|string|regex:/^[0-9]{6,20}$/','amount'=>'required|numeric|min:1','request_key'=>'required|string|max:100|regex:/^[a-zA-Z0-9-]+$/','channel'=>'nullable|in:App,Web']);
        $s=Subcategory::where('status',1)->findOrFail($in['subcategory_id']);
        if ($s->description==='AUTOSYNCAWUF') {
            $plan=collect(json_decode($s->products,true)?:[])->firstWhere('plan',$in['plan_id']);
            abort_unless($plan && InternetService::isFibre($s->title.' '.$plan['plan']),422,'Select a Fibre X package.');
            $expected=app(\App\Services\InternetAdmin::class)->fibrePrice($s,$plan,(int)$this->user->userlevel);
            abort_unless($expected !== null,422,'This Fibre X package is unavailable.');
            abort_unless((float)$expected===(float)$in['amount'],422,'Package price changed. Select the package again.');
            return \Illuminate\Support\Facades\Cache::lock('internet-fibre-'.$this->user->id,60)->block(5,function() use($r,$in) {
                $order=\App\Models\Order::where('user_id',$this->user->id)->where('custom_reference',$in['request_key'])->first();
                if (!$order) {
                    $r->merge(['ported'=>true,'custom_reference'=>$in['request_key']]);
                    $controller=app(OrderController::class); $controller->user=$this->user;
                    $response=$controller->purchaseData($r);
                    $order=\App\Models\Order::where('user_id',$this->user->id)->where('custom_reference',$in['request_key'])->first();
                    if (!$order) return $response;
                }
                $status=(int)$order->status===1?'success':(in_array((int)$order->status,[2,4],true)?'reversed':'pending');
                return response()->json(['success'=>$status!=='reversed','status'=>$status,'amount'=>$order->amount,'transaction_reference'=>$order->ref,'message'=>['success'=>'Internet purchase successful.','pending'=>'Internet purchase is pending. Check Transactions before trying again.','reversed'=>'Internet purchase failed. Check Transactions for the refund.'][$status]],$status==='reversed'?422:200);
            });
        }
        $row=$service->purchase($this->user,$in);
        $message=['success'=>'Internet purchase successful.','reversed'=>'Internet purchase failed. Your wallet has been refunded.','pending'=>'Internet purchase is pending. Check Transactions before trying again.'][$row->status];
        return response()->json(['success'=>$row->status!=='reversed','status'=>$row->status,'transaction_reference'=>$row->reference,'message'=>$message,'amount'=>(float)\App\Models\Order::findOrFail($row->order_id)->amount],$row->status==='reversed'?422:200);
    }
}
