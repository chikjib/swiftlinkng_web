<?php
namespace App\Jobs;
use App\Services\BettingFunding;
use App\Services\PayVesselBetting;
use Illuminate\Support\Facades\DB;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;
class PayVesselBettingWebhookJob extends ProcessWebhookJob {
    public $tries=10;
    public $timeout=60;
    public function backoff(): array { return [30,60,120,300,600,900,1800,3600]; }
    public function handle(PayVesselBetting $provider, BettingFunding $funding): void {
        $reference=$this->webhookCall->payload['reference'];
        $row=DB::table('betting_fundings')->where('reference',$reference)->first();
        if (!$row) throw new \RuntimeException('Betting payment not yet available');
        if (in_array($row->status,['success','failed','cancelled'],true)) return;
        // Signed notifications trigger a verified lookup; callback status never moves money directly.
        $data=$provider->request('GET','orders/verify/'.rawurlencode($reference));
        $result=$funding->settle($row,$data);
        if (in_array($result->status,['pending','processing'],true)) throw new \RuntimeException('Betting payment not yet confirmed');
        \Illuminate\Support\Facades\Log::info('Betting webhook processed',['reference'=>$reference,'status'=>$result->status]);
    }
}
