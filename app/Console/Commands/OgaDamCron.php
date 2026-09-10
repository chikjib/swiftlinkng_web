<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\Subcategory;
use App\Models\User;

use App\Traits\IntegrationsTrait;
use App\Traits\ReferenceTrait;
use App\Traits\WalletTrait;
use DB;

class OgaDamCron extends Command
{
     use IntegrationsTrait;
    use WalletTrait;
    use ReferenceTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ogadam:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ogadam Refunder';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $date = "2023-08-30 10:00:00";
        // $pendingOrders = Order::where('status', 0)->where('created_at','>=',$date)->take(10)->get();
        
        // \Log::info($orders);
        // \Log::info(is_null($pendingOrders));
        // if(!is_null($pendingOrders)){
        //   foreach ($pendingOrders as $orders) {
            
            $refund = $this->ogaDamReQuery("630843");
            \Log::info($refund);
            
            
        // }
        \Log::info("Ogadam Done");
        // }
        return Command::SUCCESS;
    }
}
