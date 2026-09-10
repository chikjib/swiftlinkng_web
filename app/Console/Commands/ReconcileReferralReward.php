<?php

namespace App\Console\Commands;

use App\Models\ReferralProgress;
use App\Models\User;
use App\Services\RewardService;
use App\Services\ReferralProgramConfig;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ReconcileReferralReward extends Command
{
    protected $signature = 'referrals:reconcile-reward {user? : The referred user ID; omit to reconcile all eligible users}';

    protected $description = 'Recalculate and credit missing referral rewards for one or all eligible referred users';

    public function handle()
    {
        if (!$this->argument('user')) {
            return $this->reconcileAll();
        }

        $user = User::find($this->argument('user'));
        if (!$user) {
            $this->error('The referred user was not found.');

            return 1;
        }

        if (!$user->referrer()) {
            $this->error('This user does not have a referrer.');

            return 1;
        }

        if (is_null($user->email_verified_at)) {
            $this->error('This user must verify their email before fixed referral rewards can be credited.');

            return 1;
        }

        if ($user->created_at->lt($this->programStartsAt())) {
            $this->error('This user registered before the current referral programme started.');

            return 1;
        }

        if (!RewardService::reconcileUser($user)) {
            $this->error('No successful qualifying purchase was found for this user.');

            return 1;
        }

        $progress = ReferralProgress::where('referred_user_id', $user->id)->first();
        if (!$progress) {
            $this->error('Referral progress could not be created. Check the application log.');

            return 1;
        }

        $this->info('Qualifying spend: N'.number_format((float) $progress->qualifying_spend, 2));
        $this->info($progress->active_rewarded_at
            ? 'The N400 active-referral reward is credited.'
            : 'The active-referral reward is not yet eligible.');

        return 0;
    }

    private function reconcileAll()
    {
        $programStartsAt = $this->programStartsAt();
        $processed = 0;

        User::where('referral_id', '>', 0)
            ->whereNotNull('email_verified_at')
            ->where('created_at', '>=', $programStartsAt)
            ->orderBy('id')
            ->chunkById(200, function ($users) use (&$processed) {
                foreach ($users as $user) {
                    if (RewardService::reconcileUser($user)) {
                        $processed++;
                    }
                }
            });

        $this->info("Reconciled {$processed} referred users with qualifying purchases.");

        return 0;
    }

    private function programStartsAt()
    {
        return Carbon::parse(
            ReferralProgramConfig::get('program_starts_at')
        )->setTimezone(config('app.timezone', 'UTC'));
    }
}
