<?php
namespace App\Services;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EsimPurchases {
    public function __construct(private EsimAccess $provider) {}

    public function profile(int $userId, int $id): object {
        $row = DB::table('esim_profiles')->where('user_id',$userId)->where('id',$id)->first();
        abort_unless($row,404,'eSIM not found.'); return $row;
    }
    public function quote(int $userId, array $input): object {
        $settings = ServiceControls::enabled('esim');
        $profile = isset($input['profile_id']) ? $this->profile($userId, $input['profile_id']) : null;
        if ($profile) {
            $payload = json_decode(Crypt::decryptString($profile->payload),true);
            abort_unless(in_array((int) ($payload['supportTopUpType'] ?? 1),[2,3],true),422,'This eSIM does not support top-ups.');
            abort_if(in_array($payload['esimStatus'] ?? '', ['USED_EXPIRED','UNUSED_EXPIRED','CANCEL','REVOKE'],true),422,'This eSIM can no longer be topped up.');
        }
        $packages = $this->provider->packages($input['location'] ?? '',$profile);
        $package = collect($packages)->firstWhere('packageCode',$input['package_code']);
        abort_unless($package && ($package['currencyCode'] ?? '') === 'USD',422,'This plan is unavailable. Please choose another plan.');
        $daily = (int) ($package['dataType'] ?? 1) !== 1;
        $period = $daily ? (int) ($input['period_num'] ?? 0) : null;
        abort_if($daily && ($period < 1 || $period > 365),422,'Select between 1 and 365 days.');
        $units = (int) $package['price'] * ($period ?? 1);
        $id = (string) Str::uuid();
        DB::table('esim_quotes')->insert([
            'id'=>$id,'user_id'=>$userId,'package_code'=>$package['packageCode'],'package_name'=>$package['name'],
            'provider_price'=>$units,'exchange_rate'=>$settings->usd_ngn,
            'amount_kobo'=>ServiceControls::kobo($units,(string) $settings->usd_ngn),
            'kind'=>$profile ? 'topup' : 'purchase','profile_id'=>$profile->id ?? null,'period_num'=>$period,
            'package_snapshot'=>json_encode($package),'expires_at'=>now()->addMinutes(10),'created_at'=>now(),'updated_at'=>now(),
        ]);
        return DB::table('esim_quotes')->where('id',$id)->first();
    }
    public function purchase(int $userId, string $quoteId, string $reference): object {
        $existing = DB::table('esim_purchases')->where('reference',$reference)->first();
        if ($existing) {
            abort_unless((int) $existing->user_id === $userId && $existing->quote_id === $quoteId,409,'Reference belongs to another payment.');
            return $this->refresh($existing);
        }
        $row = DB::transaction(function () use ($userId,$quoteId,$reference) {
            $settings = ServiceControls::enabled('esim',true);
            $user = User::whereKey($userId)->lockForUpdate()->firstOrFail();
            $quote = DB::table('esim_quotes')->where('user_id',$userId)->where('id',$quoteId)->lockForUpdate()->first();
            abort_unless($quote,404,'Quote not found.');
            $existing = DB::table('esim_purchases')->where('quote_id',$quoteId)->first();
            if ($existing) return $existing;
            abort_if(now()->greaterThan($quote->expires_at),422,'This quote expired. Please choose the plan again.');
            // Separate purchases may proceed; the locked wallet and unique quote/reference
            // keep each individual payment idempotent and prevent overspending.
            if ((int) round((float) $user->wallet * 100) < $quote->amount_kobo) throw ValidationException::withMessages(['amount'=>'Insufficient wallet balance.']);
            abort_unless($settings->esim_subcategory_id,503,'Travel eSIM is being configured.');
            $amount = number_format($quote->amount_kobo / 100,2,'.',''); $before=$user->wallet;
            $user->decrement('wallet',$amount);
            $orderId = DB::table('orders')->insertGetId([
                'ref'=>$reference,'user_id'=>$userId,'subcategory_id'=>$settings->esim_subcategory_id,
                'plan'=>$quote->package_name,'amount'=>$amount,'quantity'=>1,'subtotal'=>$amount,'total'=>$amount,
                'phone'=>$user->phone ?? '', 'channel'=>'App', 'prev_bal'=>$before,'bal'=>$before - $amount,
                'description'=>'Travel eSIM '.$quote->kind.': '.$quote->package_name,'status'=>0,'created_at'=>now(),'updated_at'=>now(),
            ]);
            $id = DB::table('esim_purchases')->insertGetId([
                'user_id'=>$userId,'order_id'=>$orderId,'reference'=>$reference,'quote_id'=>$quoteId,
                'kind'=>$quote->kind,'profile_id'=>$quote->profile_id,'package_code'=>$quote->package_code,
                'package_name'=>$quote->package_name,'provider_price'=>$quote->provider_price,'exchange_rate'=>$quote->exchange_rate,
                'amount_kobo'=>$quote->amount_kobo,'period_num'=>$quote->period_num,'package_snapshot'=>$quote->package_snapshot,
                'status'=>'pending','created_at'=>now(),'updated_at'=>now(),
            ]);
            return DB::table('esim_purchases')->where('id',$id)->first();
        });
        return $this->refresh($row);
    }
    public function refresh(object $purchase): object {
        if (in_array($purchase->status,['success','reversed'],true)) return $purchase;
        $lock=Cache::lock('esim-purchase-'.$purchase->id,120);
        if (!$lock->get()) return $purchase;
        try {
            $row=DB::table('esim_purchases')->where('id',$purchase->id)->first();
            if (in_array($row->status,['success','reversed'],true)) return $row;
            if ($row->kind === 'topup' && $row->last_attempt_at) return $row; // Unknown top-up results require provider confirmation, not a second top-up.
            if (!$row->provider_order) {
                $package=json_decode($row->package_snapshot,true);
                $body=['transactionId'=>$row->reference,'amount'=>(int) $row->provider_price];
                if ($row->kind === 'topup') {
                    $profile=$this->profile($row->user_id,$row->profile_id);
                    $body += ['esimTranNo'=>$profile->esim_tran_no,'packageCode'=>$row->package_code];
                    if ($row->period_num) $body['periodNum']=(int) $row->period_num;
                } else {
                    $plan=['packageCode'=>$row->package_code,'count'=>1,'price'=>(int) $package['price']];
                    if ($row->period_num) $plan['periodNum']=(int) $row->period_num;
                    $body['packageInfoList']=[$plan];
                }
                DB::table('esim_purchases')->where('id',$row->id)->update(['last_attempt_at'=>now()]);
                $response=$this->provider->request($row->kind === 'topup' ? 'esim/topup' : 'esim/order',$body);
                if (($response['success'] ?? null) !== true) {
                    $code=(string) ($response['errorCode'] ?? '');
                    // These documented errors reject the order before delivery/charging.
                    if (in_array($code,['200005','200006','200007','200008','200011','310241','310243','000104','000105','000106','000107'],true)) {
                        return $this->settle($row,'reversed',$code);
                    }
                    DB::table('esim_purchases')->where('id',$row->id)->update(['error_code'=>$code]);
                    return $row;
                }
                $data=$response['obj'] ?? [];
                if ($row->kind === 'topup') {
                    if (($data['transactionId'] ?? null) !== $row->reference || ($data['iccid'] ?? null) !== $profile->iccid) return $row;
                    $settled=$this->settle($row,'success');
                    try { $this->syncProfile($profile); } catch (\Throwable $e) {}
                    return $settled;
                }
                if (empty($data['orderNo'])) return $row;
                if (isset($data['transactionId']) && $data['transactionId'] !== $row->reference) return $row;
                DB::table('esim_purchases')->where('id',$row->id)->update(['provider_order'=>$data['orderNo'],'status'=>'processing','updated_at'=>now()]);
                $row->provider_order=$data['orderNo'];
            }
            $data=$this->provider->data('esim/query',['orderNo'=>$row->provider_order,'pager'=>['pageNum'=>1,'pageSize'=>5]]);
            $profiles=$data['esimList'] ?? [];
            if (count($profiles) !== 1) return DB::table('esim_purchases')->where('id',$row->id)->first();
            $profile=$profiles[0];
            if (($profile['orderNo'] ?? null) !== $row->provider_order || ($profile['transactionId'] ?? null) !== $row->reference) return $row;
            if (!in_array($profile['esimStatus'] ?? '',['GOT_RESOURCE','IN_USE','USED_UP','USED_EXPIRED','UNUSED_EXPIRED'],true) || empty($profile['esimTranNo']) || empty($profile['iccid']) || empty($profile['ac'])) return $row;
            return $this->settle($row,'success',null,$profile);
        } catch (\Throwable $e) {
            return DB::table('esim_purchases')->where('id',$purchase->id)->first();
        } finally { $lock->release(); }
    }
    public function settle(object $row, string $status, ?string $code=null, ?array $profile=null): object {
        return DB::transaction(function () use ($row,$status,$code,$profile) {
            $user=User::whereKey($row->user_id)->lockForUpdate()->firstOrFail();
            $current=DB::table('esim_purchases')->where('id',$row->id)->lockForUpdate()->first();
            if (in_array($current->status,['success','reversed'],true)) return $current;
            if ($status === 'reversed') $user->increment('wallet',number_format($current->amount_kobo/100,2,'.',''));
            $profileId=$current->profile_id;
            if ($profile) {
                $profileId=DB::table('esim_profiles')->insertGetId([
                    'user_id'=>$current->user_id,'purchase_id'=>$current->id,'esim_tran_no'=>$profile['esimTranNo'],
                    'iccid'=>$profile['iccid'],'payload'=>Crypt::encryptString(json_encode($profile)),
                    'synced_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
                ]);
            }
            DB::table('esim_purchases')->where('id',$current->id)->update(['status'=>$status,'profile_id'=>$profileId,'error_code'=>$code,'updated_at'=>now()]);
            DB::table('orders')->where('id',$current->order_id)->update(['status'=>$status === 'success' ? 1 : 2,'description'=>'Travel eSIM '.$current->kind.': '.$status,'updated_at'=>now()]);
            return DB::table('esim_purchases')->where('id',$current->id)->first();
        });
    }
    public function syncProfile(object $profile): object {
        $data=$this->provider->data('esim/query',['esimTranNo'=>$profile->esim_tran_no,'pager'=>['pageNum'=>1,'pageSize'=>5]]);
        $fresh=collect($data['esimList'] ?? [])->firstWhere('esimTranNo',$profile->esim_tran_no);
        if ($fresh && ($fresh['iccid'] ?? null) === $profile->iccid) {
            DB::table('esim_profiles')->where('id',$profile->id)->update(['payload'=>Crypt::encryptString(json_encode($fresh)),'synced_at'=>now(),'updated_at'=>now()]);
        }
        return DB::table('esim_profiles')->where('id',$profile->id)->first();
    }
    public function publicProfile(object $row): array {
        $snapshot = DB::table('esim_purchases')->where('id',$row->purchase_id)->value('package_snapshot');
        $networks = json_decode($snapshot ?? '{}',true)['locationNetworkList'] ?? [];
        return ['networks'=>$networks,'id'=>$row->id,'purchase_id'=>$row->purchase_id,'synced_at'=>$row->synced_at,'details'=>json_decode(Crypt::decryptString($row->payload),true)];
    }
    public function publicPurchase(object $row): array {
        return array_intersect_key((array) $row,array_flip(['id','reference','kind','profile_id','package_name','amount_kobo','status','created_at','updated_at']));
    }
}
