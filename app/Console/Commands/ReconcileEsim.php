<?php
namespace App\Console\Commands;
use App\Services\EsimPurchases;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class ReconcileEsim extends Command {
    protected $signature='esim:reconcile';
    protected $description='Reconcile pending eSIM allocation and purchases';
    public function handle(EsimPurchases $service) {
        DB::table('esim_purchases')->whereIn('status',['pending','processing'])->orderBy('id')->chunkById(50,function ($rows) use ($service) {
            foreach ($rows as $row) $service->refresh($row);
        });
        return 0;
    }
}
