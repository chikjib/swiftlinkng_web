<?php
namespace App\Services;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EsimWebhook {
    public function __construct(private EsimAccess $provider, private EsimPurchases $purchases) {}

    public function synchronize(array $content): void {
        // A notification is a hint only: query the authenticated provider before changing state.
        $query=DB::table('esim_purchases')->where('kind','purchase');
        if (!empty($content['transactionId'])) $query->where('reference',$content['transactionId']);
        elseif (!empty($content['orderNo'])) $query->where('provider_order',$content['orderNo']);
        elseif (!empty($content['iccid'])) {
            $profile=DB::table('esim_profiles')->where('iccid',$content['iccid'])->first();
            if (!$profile) throw new \RuntimeException('Profile not yet known');
            $query->where('id',$profile->purchase_id);
        } else throw new \RuntimeException('Missing order identity');
        $row=$query->first();
        if (!$row) throw new \RuntimeException('Order not yet known');
        if ($row->status==='reversed') return;
        if (!empty($content['orderNo']) && $row->provider_order && $content['orderNo']!==$row->provider_order) throw new \RuntimeException('Order mismatch');
        $lock=Cache::lock('esim-purchase-'.$row->id,120);
        if (!$lock->get()) throw new \RuntimeException('Purchase busy');
        try {
            $row=DB::table('esim_purchases')->where('id',$row->id)->first();
            if ($row->status==='reversed') return;
            $order=$row->provider_order ?: ($content['orderNo'] ?? null);
            if (!$order) throw new \RuntimeException('Order not available');
            $data=$this->provider->data('esim/query',['orderNo'=>$order,'pager'=>['pageNum'=>1,'pageSize'=>5]]);
            $profiles=$data['esimList'] ?? [];
            if (count($profiles)!==1) throw new \RuntimeException('Profile not allocated');
            $fresh=$profiles[0];
            if (($fresh['orderNo'] ?? null)!==$order || ($fresh['transactionId'] ?? null)!==$row->reference) throw new \RuntimeException('Provider identity mismatch');
            foreach (['iccid','esimTranNo'] as $field) {
                if (empty($fresh[$field]) || (!empty($content[$field]) && $content[$field]!==$fresh[$field])) throw new \RuntimeException('Profile identity mismatch');
            }
            if ($row->status==='success') {
                $profile=DB::table('esim_profiles')->where('id',$row->profile_id)->where('purchase_id',$row->id)->first();
                if (!$profile || $profile->iccid!==$fresh['iccid'] || $profile->esim_tran_no!==$fresh['esimTranNo']) throw new \RuntimeException('Stored profile mismatch');
                DB::table('esim_profiles')->where('id',$profile->id)->update(['payload'=>Crypt::encryptString(json_encode($fresh)),'synced_at'=>now(),'updated_at'=>now()]);
            } else {
                if (empty($fresh['ac']) || !in_array($fresh['esimStatus'] ?? '',['GOT_RESOURCE','IN_USE','USED_UP','USED_EXPIRED','UNUSED_EXPIRED'],true)) throw new \RuntimeException('Profile not ready');
                DB::table('esim_purchases')->where('id',$row->id)->update(['provider_order'=>$order,'updated_at'=>now()]);
                $this->purchases->settle($row,'success',null,$fresh);
            }
        } finally { $lock->release(); }
    }
}
