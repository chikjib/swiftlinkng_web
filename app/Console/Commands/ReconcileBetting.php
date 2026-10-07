<?php
namespace App\Console\Commands;
use App\Services\BettingFunding;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class ReconcileBetting extends Command {
    protected $signature = 'betting:reconcile';
    protected $description = 'Reconcile pending PayVessel betting payments without submitting new orders';
    public function handle(BettingFunding $funding) {
        DB::table('betting_fundings')->whereIn('status', ['pending', 'processing'])
            ->orderBy('id')->chunkById(100, function ($rows) use ($funding) {
                foreach ($rows as $row) $funding->refresh($row);
            });
        return 0;
    }
}
