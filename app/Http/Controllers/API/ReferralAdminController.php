<?php

namespace App\Http\Controllers\API;

use App\Models\ReferralProgramSetting;
use App\Models\ReferralProgress;
use App\Models\ReferralReward;
use App\Models\User;
use App\Services\ReferralProgramConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReferralAdminController extends BaseController
{
    public function earningsSummary()
    {
        /*
         * users.commission is the source of truth for money that is still
         * available in users' referral bonus balances. The reward table is
         * an immutable audit trail and can be higher after users transfer
         * bonus into their main wallets.
         */
        $totalBonusBalances = round((float) User::sum('commission'), 2);

        if (!Schema::hasTable('referral_rewards')) {
            return $this->sendResponse([
                'all_time_earnings' => 0,
                'total_bonus_balances' => $totalBonusBalances,
                // Kept for older frontend/app versions.
                'current_bonus_balances' => $totalBonusBalances,
                'rewarded_users' => 0,
                'reward_count' => 0,
                'breakdown' => [],
            ], 'Referral earnings audit retrieved successfully.');
        }

        $breakdown = ReferralReward::query()
            ->select('type', DB::raw('COUNT(*) as reward_count'), DB::raw('SUM(amount) as total_amount'))
            ->groupBy('type')
            ->orderBy('type')
            ->get()
            ->map(function ($row) {
                return [
                    'type' => $row->type,
                    'reward_count' => (int) $row->reward_count,
                    'total_amount' => round((float) $row->total_amount, 2),
                ];
            })
            ->values();

        return $this->sendResponse([
            // Immutable reward entries are the audit source for lifetime earnings.
            'all_time_earnings' => round((float) ReferralReward::sum('amount'), 2),
            // Spendable bonus balances always come directly from users.commission.
            'total_bonus_balances' => $totalBonusBalances,
            // Kept for older frontend/app versions.
            'current_bonus_balances' => $totalBonusBalances,
            'rewarded_users' => ReferralReward::distinct('beneficiary_id')->count('beneficiary_id'),
            'reward_count' => ReferralReward::count(),
            'breakdown' => $breakdown,
        ], 'Referral earnings audit retrieved successfully.');
    }

    public function index(Request $request)
    {
        $query = User::query()
            ->from('users as referred')
            ->join('users as referrer', 'referrer.id', '=', 'referred.referral_id')
            ->leftJoin(
                'referral_progress as progress',
                'progress.referred_user_id',
                '=',
                'referred.id'
            )
            ->select([
                'referred.id',
                'referred.firstname',
                'referred.lastname',
                'referred.email',
                'referred.phone',
                'referred.created_at',
                'referrer.id as referrer_id',
                'referrer.firstname as referrer_firstname',
                'referrer.lastname as referrer_lastname',
                'referrer.email as referrer_email',
                DB::raw('COALESCE(progress.qualifying_spend, 0) as qualifying_spend'),
                'progress.welcome_rewarded_at',
                'progress.became_active_at',
                'progress.active_rewarded_at',
            ]);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('referred.firstname', 'like', "%{$search}%")
                    ->orWhere('referred.lastname', 'like', "%{$search}%")
                    ->orWhere('referred.email', 'like', "%{$search}%")
                    ->orWhere('referred.phone', 'like', "%{$search}%")
                    ->orWhere('referrer.email', 'like', "%{$search}%");
            });
        }

        if ($request->input('status') === 'active') {
            $query->whereNotNull('progress.became_active_at');
        } elseif ($request->input('status') === 'inactive') {
            $query->whereNull('progress.became_active_at');
        }

        $totalReferred = User::where('referral_id', '>', 0)->count();
        $activeUsers = Schema::hasTable('referral_progress')
            ? ReferralProgress::whereNotNull('became_active_at')->count()
            : 0;
        $totalRewardEntries = Schema::hasTable('referral_rewards')
            ? (float) ReferralReward::sum('amount')
            : 0;
        $totalBonusBalances = (float) User::sum('commission');

        $rewards = Schema::hasTable('referral_rewards')
            ? ReferralReward::with(['beneficiary:id,firstname,lastname,email'])
                ->latest()
                ->limit(25)
                ->get()
            : [];

        return $this->sendResponse([
            'summary' => [
                'total_referred' => $totalReferred,
                'active_users' => $activeUsers,
                'inactive_users' => max(0, $totalReferred - $activeUsers),
                // Current balance source of truth: users.commission.
                'total_bonus_balances' => round($totalBonusBalances, 2),
                // Historical audit total; retained for API compatibility.
                'total_rewards' => round($totalRewardEntries, 2),
            ],
            'settings' => ReferralProgramConfig::all(),
            'referrals' => $query->latest('referred.created_at')->paginate(20),
            'rewards' => $rewards,
        ], 'Referral administration retrieved successfully.');
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'program_starts_at' => ['required', 'date'],
            'welcome\.minimum_spend' => ['required', 'numeric', 'min:1'],
            'welcome\.referrer_reward' => ['required', 'numeric', 'min:0'],
            'welcome\.friend_reward' => ['required', 'numeric', 'min:0'],
            'active\.minimum_spend' => ['required', 'numeric', 'min:1'],
            'active\.referrer_reward' => ['required', 'numeric', 'min:0'],
            'diamond\.users' => ['required', 'integer', 'min:1'],
            'diamond\.reward' => ['required', 'numeric', 'min:0'],
            'silver\.users' => ['required', 'integer', 'gt:diamond\.users'],
            'silver\.reward' => ['required', 'numeric', 'gte:diamond\.reward'],
            'gold\.users' => ['required', 'integer', 'gt:silver\.users'],
            'gold\.reward' => ['required', 'numeric', 'gte:silver\.reward'],
            'qualifying_categories' => ['required', 'string', 'max:500'],
            'data_category' => ['required', 'string', 'max:100'],
            'data_commission\.subcategory_id' => ['required', 'integer', 'min:1'],
            'order_history\.subcategory_id' => ['required', 'integer', 'min:1'],
        ]);

        foreach ($validated as $key => $value) {
            ReferralProgramSetting::updateOrCreate(
                ['key' => $key],
                ['value' => is_scalar($value) ? (string) $value : json_encode($value)]
            );
        }

        ReferralProgramConfig::forget();

        return $this->sendResponse(
            ReferralProgramConfig::all(),
            'Referral program settings updated successfully.'
        );
    }
}
