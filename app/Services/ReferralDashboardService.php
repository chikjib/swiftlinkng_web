<?php

namespace App\Services;

use App\Models\ReferralProgress;
use App\Models\ReferralReward;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class ReferralDashboardService
{
    /**
     * Return the referral figures consumed by the mobile and web dashboards.
     * Total referrals come directly from users.referral_id so a newly
     * registered referral is visible before they make their first purchase.
     */
    public static function forUser(User $user): array
    {
        $referralQuery = User::where('referral_id', $user->id);
        $totalReferred = (clone $referralQuery)->count();
        $verifiedReferrals = (clone $referralQuery)
            ->whereNotNull('email_verified_at')
            ->count();
        $activeUsers = 0;
        $allTimeEarnings = 0.0;

        if (Schema::hasTable('referral_progress')) {
            $activeUsers = ReferralProgress::where('referrer_id', $user->id)
                ->whereNotNull('became_active_at')
                ->count();
        }

        if (Schema::hasTable('referral_rewards')) {
            $allTimeEarnings = (float) ReferralReward::where(
                'beneficiary_id',
                $user->id
            )->sum('amount');
        }

        return [
            'total_referred' => $totalReferred,
            'active_users' => $activeUsers,
            'inactive_users' => max(0, $totalReferred - $activeUsers),
            'verified_referrals' => $verifiedReferrals,
            'unverified_referrals' => max(0, $totalReferred - $verifiedReferrals),
            'all_time_earnings' => round($allTimeEarnings, 2),
        ];
    }

    public static function referralsForUser(
        User $user,
        ?string $verification = null,
        int $perPage = 20
    ) {
        $query = User::query()
            ->where('referral_id', $user->id)
            ->select([
                'id',
                'firstname',
                'lastname',
                'email',
                'email_verified_at',
                'created_at',
            ]);

        if ($verification === 'verified') {
            $query->whereNotNull('email_verified_at');
        } elseif ($verification === 'unverified') {
            $query->whereNull('email_verified_at');
        }

        $referrals = $query->latest('created_at')->paginate(
            max(5, min($perPage, 50))
        );
        $progressByUser = collect();

        if (Schema::hasTable('referral_progress')) {
            $progressByUser = ReferralProgress::query()
                ->where('referrer_id', $user->id)
                ->whereIn('referred_user_id', $referrals->getCollection()->pluck('id'))
                ->get()
                ->keyBy('referred_user_id');
        }

        $referrals->setCollection(
            $referrals->getCollection()->map(function (User $referral) use ($progressByUser) {
                $progress = $progressByUser->get($referral->id);
                $name = trim($referral->firstname.' '.$referral->lastname);

                return [
                    'name' => $name !== '' ? $name : 'Swiftlink user',
                    'email' => self::maskEmail((string) $referral->email),
                    'email_verified' => ! is_null($referral->email_verified_at),
                    'active' => $progress && ! is_null($progress->became_active_at),
                    'qualifying_spend' => round((float) ($progress->qualifying_spend ?? 0), 2),
                    'joined_at' => $referral->created_at
                        ? $referral->created_at->format('d M Y')
                        : null,
                ];
            })
        );

        return $referrals;
    }

    private static function maskEmail(string $email): string
    {
        if (strpos($email, '@') === false) {
            return 'Hidden';
        }

        [$local, $domain] = explode('@', $email, 2);
        $visible = substr($local, 0, min(2, strlen($local)));

        return $visible.str_repeat('*', max(3, strlen($local) - strlen($visible)))
            .'@'.$domain;
    }
}
