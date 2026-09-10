<?php

namespace App\Console\Commands;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DeletePendingTransactionsCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deletependingtransaction:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command delete all pending transactions';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Order::where('updated_at', '<', Carbon::now()->subDays(30))->where('status', 0)->delete();
        //return 0;
    }
}
