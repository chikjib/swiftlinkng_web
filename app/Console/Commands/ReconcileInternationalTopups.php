<?php
namespace App\Console\Commands;
use App\Jobs\RecheckInternationalTopupJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
class ReconcileInternationalTopups extends Command {
    protected $signature='international:reconcile';
    protected $description='Recheck pending international top-ups with their original provider';
    public function handle() {
        // Rotate through pending purchases and leave API capacity for customer requests.
        $cursor=(int) Cache::get('international-recheck-cursor',0);
        $query=DB::table('international_purchases')->whereIn('status',['pending','processing'])->where(fn($q) => $q->whereNotNull('transaction_id')->orWhere('provider', 'sochitel'));
        $rows=(clone $query)->where('id','>',$cursor)->orderBy('id')->limit(20)->get();
        if ($rows->isEmpty()) $rows=$query->orderBy('id')->limit(20)->get();
        foreach ($rows as $row) {
            RecheckInternationalTopupJob::dispatch($row->id);
            Cache::put('international-recheck-cursor',$row->id,86400);
        }
        return 0;
    }
}
