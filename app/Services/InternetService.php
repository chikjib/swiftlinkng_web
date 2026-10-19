<?php
namespace App\Services;

use App\Models\{Order, Subcategory, User, RefundLog};
use App\Traits\{IntegrationsTrait, ReferenceTrait};
use Illuminate\Support\Facades\{Cache, DB, Http, Log};

class InternetService
{
    use IntegrationsTrait, ReferenceTrait;

    public static function isFibre(string $name): bool
    {
        return (bool) preg_match('/fib(?:re|er)\s*[-_]?\s*x\b/i', $name);
    }

    public function catalogue(User $user, bool $fresh = false): array
    {
        $result = [];
        foreach (Subcategory::where('status', 1)->get() as $s) {
            if (str_starts_with((string) $s->description, 'INTERNET_')) {
                $key = strtolower(substr($s->description, 9));
                try {
                    $plans = app(InternetAdmin::class)->apply($key, $this->providerPlans($key, $fresh));
                    $result[] = ['id'=>$s->id,'title'=>$s->title,'network'=>$key,'status'=>1,'products'=>$plans];
                } catch (\Throwable $e) {
                    Log::warning('internet.catalogue_unavailable', ['provider'=>$key,'error'=>$e->getMessage()]);
                    $result[] = ['id'=>$s->id,'title'=>$s->title,'network'=>$key,'status'=>1,'products'=>[], 'message'=>'Packages are temporarily unavailable. Please try again.'];
                }
            } elseif ($s->description === 'AUTOSYNCAWUF') {
                $plans = json_decode($s->products ?: '[]', true) ?: [];
                $plans = array_values(array_filter($plans, fn($p)=>is_array($p) && self::isFibre($s->title.' '.($p['plan']??''))));
                if ($plans) $result[] = ['id'=>$s->id,'title'=>'Fibre X','network'=>'fibrex','status'=>1,'products'=>app(InternetAdmin::class)->apply('fibrex', array_map(fn($p)=>app(InternetAdmin::class)->fibrePlan($s, $p, (int)$user->userlevel),$plans))];
            }
        }
        return $result;
    }

    public function providerPlans(string $key, bool $fresh = false): array
    {
        $s = $key === 'smile' ? null : app(InternationalTopup::class)->settings();
        $cacheKey = 'internet-plans-'.$key.'-'.($s ? app(Sochitel::class)->verificationHash($s).'-'.$s->multiplier.'-'.$s->sochitel_ngn_rate : hash('sha256', config('app.vtPassBaseUrl').'|'.config('app.vtpassApiKey')));
        $load = function() use ($key, $s) {
            if ($key === 'smile') {
                $r = Http::withHeaders(['api-key'=>config('app.vtpassApiKey'),'public-key'=>config('app.vtpassPublicKey')])->connectTimeout(10)->timeout(30)->get(rtrim(config('app.vtPassBaseUrl'),'/').'/service-variations', ['serviceID'=>'smile-direct']);
                abort_unless($r->successful(),503,'Smile catalogue is unavailable.');
                $rows = $r->json('content.variations') ?? $r->json('content.varations');
                abort_unless(is_array($rows),503,'Smile catalogue is unavailable.');
                return array_values(array_map(fn($p)=>['plan'=>(string)$p['variation_code'],'name'=>$p['name'],'amount'=>(int)ceil((float)$p['variation_amount']),'code'=>(string)$p['variation_code'],'cost'=>(float)$p['variation_amount']], array_filter($rows,fn($p)=>isset($p['name'],$p['variation_code']) && is_numeric($p['variation_amount']??null) && $p['variation_amount']>0 && ($p['fixedPrice']??'Yes')==='Yes')));
            }
            abort_unless(in_array($key,['spectranet','nitel'],true),422,'Select an Internet provider.');
            $plans = [];
            foreach (app(Sochitel::class)->catalog($s,'NG','data',true) as $o) {
                $name = strtolower($o['name']);
                if ($key === 'spectranet' ? !str_contains($name,'spectranet') : !preg_match('/\b(nitel|ntel)\b/',$name)) continue;
                foreach ($o['_products'] as $p) {
                    // Use one catalogue snapshot for both the product and its price.
                    // Fixed bundles must have identical endpoints in both currencies.
                    if ((float)$p['amount']['min']['operator'] !== (float)$p['amount']['max']['operator'] || (float)$p['amount']['min']['user'] !== (float)$p['amount']['max']['user']) continue;
                    $rate=$s->sochitel_currency==='NGN'?1:(float)$s->sochitel_ngn_rate;
                    abort_unless($rate>0,503,'Configure the Sochitel conversion to NGN.');
                    $cost=round((float)$p['amount']['min']['user']*$rate,2);
                    $amount = (int)ceil(InternationalTopup::price($cost,$s->multiplier)/100);
                    $plans[] = ['plan'=>$o['id'].':'.$p['id'],'name'=>$p['name'],'amount'=>$amount,'cost'=>$cost,'payload'=>['operator'=>(string)$o['_operator'],'product'=>(string)$p['id'],'currency'=>$o['currency'],'amount'=>(string)$p['amount']['min']['operator']]];
                }
            }
            return $plans;
        };
        if ($fresh) { $plans=$load(); Cache::put($cacheKey,$plans,60); return $plans; }
        return Cache::remember($cacheKey,60,$load);
    }

    public function purchase(User $user, array $in): object
    {
        return Cache::lock('internet-buy-'.$user->id,60)->block(5,function() use($user,$in) {
            $existing=DB::table('internet_purchases')->where('user_id',$user->id)->where('request_key',$in['request_key'])->first();
            if ($existing) return $existing;
            $s=Subcategory::where('status',1)->findOrFail($in['subcategory_id']);
            abort_unless(str_starts_with((string)$s->description,'INTERNET_'),422,'Select an Internet provider.');
            $key=strtolower(substr($s->description,9));
            $rawPlans=$this->providerPlans($key,true);
            $plan=collect(app(InternetAdmin::class)->apply($key,$rawPlans))->firstWhere('plan',$in['plan_id']);
            abort_unless($plan && (float)$plan['amount']===(float)$in['amount'],422,'Package or price changed. Select the package again.');
            $provider=$key==='smile'?'vtpass':'sochitel';
            $ref=$provider==='vtpass'?$this->vtPassreferenceCode():'INT'.bin2hex(random_bytes(12));
            $payload=$provider==='vtpass'?['billersCode'=>$in['phonenumber'],'serviceID'=>'smile-direct','request_id'=>$ref,'variation_code'=>$plan['code'],'amount'=>$plan['cost'],'phone'=>$user->phone]:$plan['payload']+['recipient'=>$in['phonenumber'],'reference'=>$ref];
            if ($provider==='sochitel') $payload['_account']=app(Sochitel::class)->account(app(InternationalTopup::class)->settings());
            $row=DB::transaction(function() use($user,$in,$s,$plan,$provider,$ref,$payload,$key,$rawPlans) {
                $current=collect(app(InternetAdmin::class)->apply($key,$rawPlans,true))->firstWhere('plan',$in['plan_id']);
                abort_unless($current && (float)$current['amount']===(float)$in['amount'],422,'Package settings or price changed. Select the package again.');
                $u=User::lockForUpdate()->findOrFail($user->id);
                abort_unless($plan['amount']>0 && $u->wallet >= $plan['amount'],422,'Insufficient balance for this purchase.');
                $prev=$u->wallet; $u->wallet-=$plan['amount']; $u->save();
                $order=new Order();
                foreach (['ref'=>$ref,'custom_reference'=>$in['request_key'],'user_id'=>$u->id,'subcategory_id'=>$s->id,'plan'=>$s->title.' '.($plan['name']??$plan['plan']),'amount'=>$plan['amount'],'quantity'=>1,'subtotal'=>$plan['amount'],'total'=>$plan['amount'],'phone'=>$in['phonenumber'],'bal'=>$u->wallet,'prev_bal'=>$prev,'channel'=>$in['channel']??'Web','description'=>$s->title.' '.($plan['name']??$plan['plan']),'api_route'=>$s->description,'cost_price'=>$plan['cost'],'status'=>0] as $k=>$v) $order->$k=$v;
                $order->save();
                $id=DB::table('internet_purchases')->insertGetId(['user_id'=>$u->id,'order_id'=>$order->id,'request_key'=>$in['request_key'],'provider'=>$provider,'reference'=>$ref,'payload'=>json_encode($payload),'status'=>'pending','created_at'=>now(),'updated_at'=>now()]);
                return DB::table('internet_purchases')->find($id);
            });
            try { return $this->record($row,$this->send($row)); }
            catch (\Throwable $e) { Log::warning('internet.purchase_pending',['reference'=>$ref,'error'=>$e->getMessage()]); return $row; }
        });
    }

    private function send(object $row): array
    {
        $payload=json_decode($row->payload,true);
        if ($row->provider==='sochitel') {
            $s=app(InternationalTopup::class)->settings();
            abort_unless(($payload['_account']??'')===app(Sochitel::class)->account($s),503,'Provider account changed.');
            unset($payload['_account']);
            return app(Sochitel::class)->request($s,'POST','/transaction',$payload);
        }
        return Http::withHeaders(['api-key'=>config('app.vtpassApiKey'),'secret-key'=>config('app.vtpassSecretKey')])->connectTimeout(10)->timeout(50)->post(rtrim(config('app.vtPassBaseUrl'),'/').'/pay',$payload)->json() ?? [];
    }

    public function reconcile(object $row): object
    {
        if ($row->provider==='sochitel') {
            $s=app(InternationalTopup::class)->settings(); $payload=json_decode($row->payload,true);
            abort_unless(($payload['_account']??'')===app(Sochitel::class)->account($s),503,'Restore the original Sochitel account.');
            $r=app(Sochitel::class)->request($s,'GET','/transaction/user/'.rawurlencode($row->reference));
        } else $r=$this->VtPassRequery($row->reference);
        return $this->record($row,$r??[]);
    }

    public function record(object $row, array $response): object
    {
        $outcome=self::vtpassOutcome($response);
        if ($row->provider==='sochitel') {
            $p=json_decode($row->payload,true);
            $matches=!empty($response['id'])
                && (string)($response['reference']??'')===$row->reference
                && (string)($response['recipient']??'')===($p['recipient']??'')
                && (string)($response['product']??'')===($p['product']??'')
                && (string)($response['operator']['id']??'')===($p['operator']??'')
                && ($response['operator']['currency']??'')===($p['currency']??'')
                && is_numeric($response['amount']['operator']??null)
                && abs((float)$response['amount']['operator']-(float)($p['amount']??0))<0.001;
            $outcome=$matches?app(Sochitel::class)->outcome($response):null;
            if ($outcome==='success' && (int)($response['errno']??-1)!==0) $outcome=null;
        }
        return DB::transaction(function() use($row,$response,$outcome) {
            $r=DB::table('internet_purchases')->where('id',$row->id)->lockForUpdate()->first();
            if ($r->status!=='pending') return $r;
            $order=Order::lockForUpdate()->findOrFail($r->order_id);
            if ($outcome) {
                if ($outcome==='reversed') {
                    $u=User::lockForUpdate()->findOrFail($r->user_id);
                    if (!RefundLog::where('ref',$r->reference)->exists()) {
                        $u->wallet+=$order->amount; $u->save();
                        RefundLog::create(['ref'=>$r->reference,'order_id'=>$order->id,'user_id'=>$u->id,'credited_amount'=>$order->amount]);
                    }
                }
                $order->status=$outcome==='success'?1:2;
                $order->response=json_encode($response); $order->save();
                if ($outcome==='success') RewardService::processSuccessfulOrder($order);
            }
            DB::table('internet_purchases')->where('id',$r->id)->update(['status'=>$outcome??'pending','response'=>json_encode($response),'updated_at'=>now()]);
            return DB::table('internet_purchases')->find($r->id);
        });
    }

    public static function vtpassOutcome(array $r): ?string
    {
        $status=strtolower($r['content']['transactions']['status']??'');
        if (($r['code']??'')==='000' && $status==='delivered') return 'success';
        if (in_array($status,['failed','reversed'],true)) return 'reversed';
        // Timeouts, malformed responses and pending codes must be queried, never resent.
        return null;
    }
}
