<?php
namespace App\Jobs;
use App\Services\EsimWebhook;
use Illuminate\Support\Facades\Cache;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;
class EsimAccessWebhookJob extends ProcessWebhookJob {
    public $tries=10;
    public $timeout=60;
    public function backoff(): array { return [30,60,120,300,600,900,1800,3600]; }
    public function handle(EsimWebhook $service): void {
        $event=$this->webhookCall->payload;
        $key='esim-event-'.hash('sha256',$event['notifyId']);
        $lock=Cache::lock($key,120);
        if (!$lock->get()) throw new \RuntimeException('eSIM event is being processed');
        try {
            if (Cache::get($key.'-done')) return;
            $service->synchronize($event['content']);
            Cache::put($key.'-done',true,now()->addDays(30));
            \Illuminate\Support\Facades\Log::info('eSIM webhook processed',['webhook_call_id'=>$this->webhookCall->id,'notify_type'=>$event['notifyType']]);
        } finally { $lock->release(); }
    }
}
