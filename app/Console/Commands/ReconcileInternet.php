<?php
namespace App\Console\Commands;
use App\Services\InternetService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Log};
class ReconcileInternet extends Command {
    protected $signature='internet:reconcile';
    protected $description='Query pending Internet purchases without sending another purchase';
    public function handle(InternetService $service) {
        foreach (DB::table('internet_purchases')->where('status','pending')->where('created_at','<',now()->subMinute())->orderBy('updated_at')->limit(20)->get() as $row) {
            try { $service->reconcile($row); } catch (\Throwable $e) { Log::warning('internet.recheck_failed',['reference'=>$row->reference,'error'=>$e->getMessage()]); DB::table('internet_purchases')->where('id',$row->id)->update(['updated_at'=>now()]); }
        }
        return 0;
    }
}
