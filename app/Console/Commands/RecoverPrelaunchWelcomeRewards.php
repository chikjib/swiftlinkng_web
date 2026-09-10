<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\ReferralReward;
use App\Models\Subcategory;
use App\Models\User;
use App\Services\ReferralProgramConfig;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RecoverPrelaunchWelcomeRewards extends Command
{
    protected $signature = 'referrals:recover-prelaunch-welcome
        {--apply : Recover invalid credits from available Bonus balances}
        {--details : List every affected reward entry}
        {--force : Skip the confirmation prompt when applying}';

    protected $description = 'Find and safely recover welcome bonuses awarded for referrals registered before programme launch';

    public function handle()
    {
        if (!Schema::hasTable('referral_rewards')) {
            $this->error('The referral_rewards table does not exist on this database.');
            return 1;
        }

        $programStartsAt = Carbon::parse(ReferralProgramConfig::get('program_starts_at'))
            ->setTimezone(config('app.timezone', 'UTC'));
        $rewards = $this->affectedRewards($programStartsAt)->get();

        $this->line('Programme launch: '.$programStartsAt->toIso8601String());
        $this->line('Affected reward entries: '.$rewards->count());
        $this->line('Originally credited: N'.number_format((float) $rewards->sum('amount'), 2));

        if ($this->option('details') || !$this->option('apply')) {
            $this->displayAffectedRewards($rewards);
        }

        if (!$this->option('apply')) {
            $this->warn('Preview only. No Bonus balance was changed.');
            $this->line('After reviewing, run with --apply to recover available invalid balances.');
            return 0;
        }

        if (!$this->option('force') && !$this->confirm('Recover these invalid credits from available Bonus balances?')) {
            $this->info('Recovery cancelled.');
            return 0;
        }

        $recovered = 0.0;
        $unrecovered = 0.0;
        $affectedUsers = [];

        foreach ($rewards as $reward) {
            $result = $this->recoverReward((int) $reward->id);
            $recovered += $result['recovered'];
            $unrecovered += $result['outstanding'];
            if ($result['recovered'] > 0) {
                $affectedUsers[$result['beneficiary_id']] = true;
            }
        }

        $this->info('Recovered: N'.number_format($recovered, 2));
        $this->line('Users debited: '.count($affectedUsers));
        if ($unrecovered > 0) {
            $this->warn('Still unrecovered because Bonus balances were insufficient: N'.number_format($unrecovered, 2));
            $this->warn('You can rerun this command later; it will not recover the same amount twice.');
        }

        return 0;
    }

    private function affectedRewards(Carbon $programStartsAt)
    {
        return ReferralReward::query()
            ->join('users as referred', 'referred.id', '=', 'referral_rewards.referred_user_id')
            ->join('users as beneficiary', 'beneficiary.id', '=', 'referral_rewards.beneficiary_id')
            ->whereIn('referral_rewards.type', ['welcome_referrer', 'welcome_friend'])
            ->where('referred.created_at', '<', $programStartsAt)
            ->select([
                'referral_rewards.id', 'referral_rewards.type', 'referral_rewards.amount',
                'referral_rewards.created_at as credited_at',
                'referred.id as referred_user_id', 'referred.email as referred_email',
                'referred.created_at as referred_registered_at',
                'beneficiary.id as beneficiary_id', 'beneficiary.email as beneficiary_email',
            ])->orderBy('referral_rewards.id');
    }

    private function displayAffectedRewards($rewards)
    {
        if ($rewards->isEmpty()) {
            $this->info('No pre-launch welcome credits were found.');
            return;
        }

        $this->table(
            ['Reward', 'Type', 'Amount', 'Beneficiary', 'Referred user', 'Registered', 'Credited'],
            $rewards->map(function ($reward) {
                return [
                    $reward->id, $reward->type, 'N'.number_format((float) $reward->amount, 2),
                    $reward->beneficiary_id.' / '.$reward->beneficiary_email,
                    $reward->referred_user_id.' / '.$reward->referred_email,
                    $reward->referred_registered_at, $reward->credited_at,
                ];
            })->all()
        );
    }

    private function recoverReward($rewardId)
    {
        return DB::transaction(function () use ($rewardId) {
            $reward = ReferralReward::lockForUpdate()->find($rewardId);
            if (!$reward || !in_array($reward->type, ['welcome_referrer', 'welcome_friend'], true)) {
                return ['recovered' => 0.0, 'outstanding' => 0.0, 'beneficiary_id' => 0];
            }

            $keyPrefix = 'prelaunch-welcome-reversal:'.$reward->id.':';
            $alreadyRecovered = abs((float) ReferralReward::where('reward_key', 'like', $keyPrefix.'%')->sum('amount'));
            $outstanding = round(max(0, (float) $reward->amount - $alreadyRecovered), 2);
            if ($outstanding <= 0) {
                return ['recovered' => 0.0, 'outstanding' => 0.0, 'beneficiary_id' => (int) $reward->beneficiary_id];
            }

            $user = User::lockForUpdate()->find($reward->beneficiary_id);
            if (!$user) {
                return ['recovered' => 0.0, 'outstanding' => $outstanding, 'beneficiary_id' => (int) $reward->beneficiary_id];
            }

            $balanceBefore = round(max(0, (float) $user->commission), 2);
            $recoverable = round(min($outstanding, $balanceBefore), 2);
            if ($recoverable <= 0) {
                return ['recovered' => 0.0, 'outstanding' => $outstanding, 'beneficiary_id' => (int) $user->id];
            }

            $balanceAfter = round($balanceBefore - $recoverable, 2);
            $newRecoveredTotal = round($alreadyRecovered + $recoverable, 2);
            $reversalKey = $keyPrefix.(int) round($newRecoveredTotal * 100);

            $user->commission = $balanceAfter;
            $user->save();
            ReferralReward::create([
                'beneficiary_id' => $user->id,
                'referred_user_id' => $reward->referred_user_id,
                'order_id' => $reward->order_id,
                'type' => 'welcome_reversal',
                'wallet_type' => 'commission',
                'reward_key' => $reversalKey,
                'amount' => -$recoverable,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ]);
            $this->storeReversalOrder($user, $recoverable, $balanceBefore, $balanceAfter, $reversalKey);

            Log::warning('Pre-launch welcome bonus recovered', [
                'original_reward_id' => $reward->id, 'beneficiary_id' => $user->id,
                'referred_user_id' => $reward->referred_user_id, 'amount' => $recoverable,
                'balance_before' => $balanceBefore, 'balance_after' => $balanceAfter,
                'remaining' => round($outstanding - $recoverable, 2),
            ]);

            return [
                'recovered' => $recoverable,
                'outstanding' => round($outstanding - $recoverable, 2),
                'beneficiary_id' => (int) $user->id,
            ];
        }, 3);
    }

    private function storeReversalOrder(User $user, $amount, $balanceBefore, $balanceAfter, $reversalKey)
    {
        $subcategory = Subcategory::where('title', 'Bonus')->first()
            ?: Subcategory::where('title', 'Manual')->first()
            ?: Subcategory::find(ReferralProgramConfig::get('order_history.subcategory_id', 23));
        if (!$subcategory) {
            throw new \RuntimeException('A Bonus, Manual, or reward-history subcategory is required.');
        }

        $reference = 'RVR-'.strtoupper(substr(hash('sha256', $reversalKey), 0, 24));
        if (Order::where('ref', $reference)->exists()) {
            return;
        }

        $order = new Order();
        $order->ref = $reference;
        $order->user_id = $user->id;
        $order->subcategory_id = $subcategory->id;
        $order->plan = 'Pre-launch Welcome Bonus Reversal';
        $order->amount = $amount;
        $order->quantity = 1;
        $order->subtotal = $amount;
        $order->total = $amount;
        $order->bal = $balanceAfter;
        $order->prev_bal = $balanceBefore;
        $order->channel = 'System';
        $order->description = 'Recovery of an ineligible N50 welcome bonus credited for a pre-launch referral.';
        $order->response = sprintf('N%s invalid welcome bonus recovered. Bonus balance: N%s.', number_format((float) $amount, 2), number_format((float) $balanceAfter, 2));
        $order->status = 1;
        Order::withoutEvents(function () use ($order) { $order->save(); });
    }
}
