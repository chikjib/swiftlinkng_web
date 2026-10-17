<?php
namespace App\Jobs;
use App\Services\InternationalTopup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
class RecheckInternationalTopupJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $tries=3;
    public $timeout=60;
    public function __construct(public int $purchaseId) {}
    public function backoff(): array { return [60,180,600]; }
    public function handle(InternationalTopup $service): void {
        $lock=Cache::lock('international-recheck-'.$this->purchaseId,120);
        if (!$lock->get()) return;
        try {
            $row=DB::table('international_purchases')->where('id',$this->purchaseId)->first();
            if (!$row || !in_array($row->status,['pending','processing'],true) || (!$row->transaction_id && $row->provider !== 'sochitel')) return;
            $result=$service->refresh($row);
            \Illuminate\Support\Facades\Log::info('International status checked',['reference'=>$row->reference,'provider'=>$row->provider,'status'=>$result->status]);
        } finally { $lock->release(); }
    }
}
