<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Subcategory;
use Illuminate\Console\Command;

class AwufCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'awufswitcher:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //$subcategory = Subcategory::find(48);
        //$switched_off_date = date("Y-m-d",strtotime($subcategory->updated_at));
        //\Log::info("SWITCHED OFF");
        //\Log::info($switched_off_date);
        //$today_date = date("Y-m-d");
        //\Log::info("TODAY DATE");
        //\Log::info($today_date);
        
        //if($switched_off_date != $today_date){
        //    $subcategory->status = 1;
        //    $subcategory->save();
        //}

        return Command::SUCCESS;
    }
}
