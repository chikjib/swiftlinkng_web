<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{


    protected $commands = [
        Commands\DeletePendingTransactionsCron::class,
        Commands\RefundFailedTransactionVTPassCron::class,
        Commands\AirtelCron::class,
        Commands\AwufCron::class,
        Commands\ReconcileReferralReward::class,
        Commands\RecoverPrelaunchWelcomeRewards::class,
       // Commands\RingoCron::class,
        //Commands\OgaDamCron::class,
    ];
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('deletependingtransaction:cron')->daily();

        $schedule->command('refundfailedtransactionvtpass:cron')->everyMinute();
        
        $schedule->command('airtel:cron')->everyMinute();
        
        $schedule->command('ringo:cron')->everyMinute();
        
        $schedule->command('awufswitcher:cron')->dailyAt('7:00');

        // Repairs rewards missed by asynchronous provider/manual status updates.
        $schedule->command('referrals:reconcile-reward')->dailyAt('00:15');

        // Concurrent device and integration tokens are allowed. Remove only
        // revoked and long-expired Passport records; active tokens are kept.
        $schedule->command('passport:purge --revoked --expired')
            ->dailyAt('02:30')
            ->withoutOverlapping();
        
        //$schedule->command('ogadam:cron')->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
