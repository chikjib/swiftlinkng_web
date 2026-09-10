<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\Subcategory;
use App\Models\ReferralProgress;
use App\Models\ReferralReward;
use App\Traits\ReferenceTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class RewardService
{
    use ReferenceTrait;

    /**
     * Process every referral reward for a successful order.
     */
    public static function processSuccessfulOrder($order)
    {
        if (!$order instanceof Order) {
            $order = Order::find($order);
        }

        if (!$order || (int) $order->status !== 1) {
            return false;
        }

        $order->load('subcategory.category');

        if (!self::isQualifyingOrder($order)) {
            return false;
        }

        $friend = User::find($order->user_id);

        if (!$friend || !method_exists($friend, 'referrer')) {
            return false;
        }

        $referrer = $friend->referrer();

        if ($referrer instanceof Relation) {
            $referrer = $referrer->first();
        }

        if (!$referrer || $referrer->id == $friend->id) {
            return false;
        }

        return DB::transaction(function () use (
            $order,
            $friend,
            $referrer
        ) {
            /*
             * Lock both users in a consistent order to prevent
             * concurrent commission updates.
             */
            $users = User::whereIn('id', [
                    $friend->id,
                    $referrer->id
                ])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $friend = $users->get($friend->id);
            $referrer = $users->get($referrer->id);

            if (!$friend || !$referrer) {
                return false;
            }

            /*
             * Data commission remains valid for both existing and new
             * referrals. Its unique reward key prevents repeat credits.
             */
            if (self::isDataOrder($order)) {
                self::awardDataCommission(
                    $referrer,
                    $friend,
                    $order
                );
            }

            /*
             * Fixed signup/activation rewards require proof that the referred
             * customer owns the registered email address. Normal lifetime
             * data commission is based on a paid purchase and remains intact.
             */
            if (is_null($friend->email_verified_at)) {
                Log::info('Fixed referral rewards deferred until email verification', [
                    'referred_user_id' => $friend->id,
                    'order_id' => $order->id,
                ]);

                return true;
            }

            /*
             * All fixed rewards begin with referrals registered on or
             * after the configured referral-program launch timestamp.
             */
            if (!self::isNewProgramReferral($friend)) {
                return true;
            }

            $progress = ReferralProgress::where(
                'referred_user_id',
                $friend->id
            )->lockForUpdate()->first();

            if (!$progress) {
                $progress = new ReferralProgress();
                $progress->referrer_id = $referrer->id;
                $progress->referred_user_id = $friend->id;
                $progress->qualifying_spend = 0;
                $progress->save();
            }

            /*
             * Recalculate successful purchase total. This ensures that
             * repeated callbacks do not count an order more than once.
             */
            $progress->qualifying_spend =
                self::successfulPurchaseAmount($friend->id);

            $progress->save();

            /*
             * ₦50 welcome reward for both users after ₦500 spend.
             */
            self::checkWelcomeReward(
                $progress,
                $referrer,
                $friend,
                $order
            );

            /*
             * ₦400 to the referrer after the friend reaches ₦50,000.
             */
            self::checkActiveStatus(
                $progress,
                $referrer,
                $friend
            );

            /*
             * Silver, Diamond and Gold milestone rewards.
             */
            self::checkMilestones($referrer);

            return true;
        }, 3);
    }

    /**
     * Recalculate one referred user's fixed rewards from their latest
     * qualifying successful purchase. Used after verification and for audits.
     */
    public static function reconcileUser($user)
    {
        if (!$user instanceof User) {
            $user = User::find($user);
        }

        if (!$user || !$user->referrer()) {
            return false;
        }

        $order = self::successfulPurchaseQuery($user->id)
            ->latest('id')
            ->first();

        return $order ? self::processSuccessfulOrder($order) : false;
    }

    /**
     * Award ₦50 to both parties after the referred user reaches ₦500.
     */
    public static function checkWelcomeReward(
        ReferralProgress $progress,
        User $referrer,
        User $friend,
        Order $order
    ) {
        // Keep the launch boundary here as well as in the main processor so
        // maintenance/reconciliation callers cannot credit old referrals.
        if (!self::isNewProgramReferral($friend)) {
            Log::info('Pre-launch referral welcome reward skipped', [
                'referred_user_id' => $friend->id,
                'registered_at' => optional($friend->created_at)->toIso8601String(),
                'program_starts_at' => self::programStartsAt()->toIso8601String(),
            ]);

            return false;
        }

        $minimumSpend = (float) config(
            'referrals.welcome.minimum_spend',
            500
        );
        $minimumSpend = (float) ReferralProgramConfig::get(
            'welcome.minimum_spend',
            $minimumSpend
        );

        if ((float) $progress->qualifying_spend < $minimumSpend) {
            return false;
        }

        if (!is_null($progress->welcome_rewarded_at)) {
            return false;
        }

        self::creditCommission(
            $referrer,
            ReferralProgramConfig::get('welcome.referrer_reward', 50),
            'welcome_referrer',
            'welcome-referrer:' . $friend->id,
            $friend->id,
            $order->id,
            'Referral Welcome Reward for referring ' . self::userName($friend)
        );

        self::creditCommission(
            $friend,
            ReferralProgramConfig::get('welcome.friend_reward', 50),
            'welcome_friend',
            'welcome-friend:' . $friend->id,
            $friend->id,
            $order->id,
            'Referral Welcome Reward after reaching N500 in purchases'
        );

        $progress->welcome_rewarded_at = now();
        $progress->save();

        return true;
    }

    /**
     * Award the normal percentage commission on each data purchase.
     *
     * The commission percentage is read from Subcategory ID 23,
     * using the same getUserLevel() logic as the old controller.
     */
    public static function awardDataCommission(
        User $referrer,
        User $friend,
        Order $order
    ) {
        $subcategoryId = ReferralProgramConfig::get(
            'data_commission.subcategory_id',
            23
        );

        $subcategory = Subcategory::find($subcategoryId);

        if (!$subcategory || empty($subcategory->products)) {
            Log::error('Data commission configuration missing', [
                'subcategory_id' => $subcategoryId,
                'order_id' => $order->id
            ]);

            return false;
        }

        $products = json_decode($subcategory->products);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Invalid data commission products JSON', [
                'subcategory_id' => $subcategoryId,
                'order_id' => $order->id,
                'json_error' => json_last_error_msg()
            ]);

            return false;
        }

        /*
         * getUserLevel() is an instance method from OrderTrait.
         */
        $service = new self();

        $percentage = $service->getUserLevel(
            $products,
            $friend->userlevel
        );

        $percentage = (float) $percentage;

        if ($percentage <= 0) {
            return false;
        }

        $purchaseAmount = (float) $order->subtotal > 0
            ? (float) $order->subtotal
            : (float) $order->total;

        $commission = round(($percentage / 100) * $purchaseAmount, 2);

        if ($commission <= 0) {
            return false;
        }

        return self::creditCommission(
            $referrer,
            $commission,
            'data_commission',
            'data-commission:' . $order->id,
            $friend->id,
            $order->id,
            $percentage . '% Data Referral Commission from ' . self::userName($friend)
        );
    }

    /**
     * A referred user becomes active after ₦50,000 in successful
     * cumulative qualifying purchases.
     *
     * Only the referrer receives the ₦400 reward.
     */
    public static function checkActiveStatus(
        ReferralProgress $progress,
        User $referrer,
        User $friend
    ) {
        /*
         * Recover safely if active status was previously saved but
         * the active reward was not completed.
         */
        if (!is_null($progress->became_active_at)) {
            if (is_null($progress->active_rewarded_at)) {
                self::awardActiveReferral(
                    $progress,
                    $referrer,
                    $friend
                );
            }

            return false;
        }

        $minimumSpend = (float) config(
            'referrals.active.minimum_spend',
            50000
        );
        $minimumSpend = (float) ReferralProgramConfig::get(
            'active.minimum_spend',
            $minimumSpend
        );

        if ((float) $progress->qualifying_spend < $minimumSpend) {
            return false;
        }

        $progress->became_active_at = now();
        $progress->save();

        self::awardActiveReferral(
            $progress,
            $referrer,
            $friend
        );

        return true;
    }

    /**
     * Credit the ₦400 active-referral reward to the referrer only.
     */
    public static function awardActiveReferral(
        ReferralProgress $progress,
        User $referrer,
        User $friend
    ) {
        self::creditCommission(
            $referrer,
            ReferralProgramConfig::get('active.referrer_reward', 400),
            'active_referral',
            'active-user:' . $friend->id,
            $friend->id,
            null,
            'Active Referral Reward for ' . self::userName($friend) . ' reaching N50,000 in purchases'
        );

        $progress->active_rewarded_at = now();
        $progress->save();

        return true;
    }

    /**
     * Award Silver, Diamond and Gold milestone rewards.
     */
    public static function checkMilestones(User $referrer)
    {
        $activeCount = ReferralProgress::where(
                'referrer_id',
                $referrer->id
            )
            ->where('became_active_at', '>=', self::programStartsAt())
            ->whereIn('referred_user_id', function ($query) {
                $query->select('id')
                    ->from('users')
                    ->where(
                        'created_at',
                        '>=',
                        self::programStartsAt()
                    );
            })
            ->count();

        $milestones = ReferralProgramConfig::milestones();

        ksort($milestones);

        foreach ($milestones as $required => $milestone) {
            if ($activeCount < (int) $required) {
                continue;
            }

            $name = isset($milestone['name'])
                ? $milestone['name']
                : 'Level ' . $required;

            $amount = isset($milestone['reward'])
                ? $milestone['reward']
                : 0;

            $slug = strtolower(
                preg_replace('/[^a-zA-Z0-9]+/', '-', $name)
            );

            self::creditCommission(
                $referrer,
                $amount,
                'referral_milestone',
                'milestone-' . $slug . ':' . $referrer->id,
                null,
                null,
                $name . ' Referral Milestone Reward for ' . $required . ' active referrals'
            );
        }

        return $activeCount;
    }

    /**
     * Credit a user's commission exactly once.
     */
    protected static function creditCommission(
        User $user,
        $amount,
        $type,
        $rewardKey,
        $referredUserId = null,
        $orderId = null,
        $description = null
    ) {
        $amount = round((float) $amount, 2);

        if ($amount <= 0) {
            return false;
        }

        /*
         * reward_key has a unique database constraint.
         */
        $existingReward = ReferralReward::where(
            'reward_key',
            $rewardKey
        )->first();

        if ($existingReward) {
            self::storeRewardOrder(
                $user,
                $existingReward->amount,
                $existingReward->balance_before,
                $existingReward->balance_after,
                $type,
                $rewardKey,
                $referredUserId,
                $orderId,
                $description
            );

            return false;
        }

        $balanceBefore = (float) $user->commission;
        $balanceAfter = round($balanceBefore + $amount, 2);

        $reward = new ReferralReward();
        $reward->beneficiary_id = $user->id;
        $reward->referred_user_id = $referredUserId;
        $reward->order_id = $orderId;
        $reward->type = $type;
        $reward->wallet_type = 'commission';
        $reward->reward_key = $rewardKey;
        $reward->amount = $amount;
        $reward->balance_before = $balanceBefore;
        $reward->balance_after = $balanceAfter;
        $reward->save();

        User::where('id', $user->id)->increment(
            'commission',
            $amount
        );

        $user->commission = $balanceAfter;

        self::storeRewardOrder(
            $user,
            $amount,
            $balanceBefore,
            $balanceAfter,
            $type,
            $rewardKey,
            $referredUserId,
            $orderId,
            $description
        );

        Log::info('Referral commission credited', [
            'beneficiary_id' => $user->id,
            'referred_user_id' => $referredUserId,
            'order_id' => $orderId,
            'type' => $type,
            'amount' => $amount,
            'reward_key' => $rewardKey,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter
        ]);

        return true;
    }

    /**
     * Store the commission credit in the normal order history.
     * The deterministic reference prevents duplicate history rows.
     */
    protected static function storeRewardOrder(
        User $user,
        $amount,
        $balanceBefore,
        $balanceAfter,
        $type,
        $rewardKey,
        $referredUserId = null,
        $sourceOrderId = null,
        $description = null
    ) {
        $reference = self::rewardOrderReference($rewardKey);

        if (Order::where('ref', $reference)->exists()) {
            return false;
        }

        $subcategoryId = ReferralProgramConfig::get(
            'order_history.subcategory_id',
            ReferralProgramConfig::get('data_commission.subcategory_id', 23)
        );

        $subcategory = Subcategory::find($subcategoryId);

        if (!$subcategory) {
            throw new RuntimeException(
                'Referral reward order subcategory not found: ' . $subcategoryId
            );
        }

        $sourceOrder = $sourceOrderId
            ? Order::find($sourceOrderId)
            : null;

        $label = $description ?: ucwords(
            str_replace('_', ' ', $type)
        );

        $history = new Order();
        $history->ref = $reference;
        $history->user_id = $user->id;
        $history->subcategory_id = $subcategory->id;
        $history->plan = $subcategory->title . ' - ' . $label;
        $history->amount = $amount;
        $history->quantity = 1;
        $history->subtotal = $amount;
        $history->total = $amount;
        $history->bal = $balanceAfter;
        $history->prev_bal = $balanceBefore;
        $history->channel = $sourceOrder && $sourceOrder->channel
            ? $sourceOrder->channel
            : 'System';
        $history->description = $label;
        $history->response = sprintf(
            '%s. ₦%s has been successfully credited to your referral bonus balance. New balance: ₦%s.',
            rtrim(trim($label), '.'),
            number_format((float) $amount, 2),
            number_format((float) $balanceAfter, 2)
        );
        $history->status = 1;
        $history->save();

        return $history;
    }

    protected static function rewardOrderReference($rewardKey)
    {
        return 'RWD-' . strtoupper(substr(hash('sha256', $rewardKey), 0, 24));
    }

    protected static function userName(User $user)
    {
        $name = trim($user->firstname . ' ' . $user->lastname);

        return $name !== '' ? $name : 'User #' . $user->id;
    }

    /**
     * Purchases counted toward the ₦500 and ₦50,000 targets.
     */
    protected static function successfulPurchaseQuery($userId)
    {
        $categories = array_map(
            'strtolower',
            ReferralProgramConfig::categories()
        );

        $rewardSubcategoryId = ReferralProgramConfig::get(
            'order_history.subcategory_id',
            ReferralProgramConfig::get('data_commission.subcategory_id', 23)
        );

        return Order::where('user_id', $userId)
            ->where('status', 1)
            ->where(function ($query) {
                $query->where('subtotal', '>', 0)
                    ->orWhere('total', '>', 0);
            })
            ->where('created_at', '>=', self::programStartsAt())
            ->where('subcategory_id', '!=', $rewardSubcategoryId)
            ->where('ref', 'not like', 'RWD-%')
            ->whereHas(
                'subcategory.category',
                function ($query) use ($categories) {
                    $query->whereIn(
                        DB::raw('LOWER(title)'),
                        $categories
                    );
                }
            );
    }

    protected static function successfulPurchaseAmount($userId)
    {
        $amountExpression = 'CASE WHEN COALESCE(subtotal, 0) > 0 '
            .'THEN subtotal ELSE COALESCE(total, 0) END';

        $summary = self::successfulPurchaseQuery($userId)
            ->selectRaw("COALESCE(SUM({$amountExpression}), 0) as qualifying_total")
            ->first();

        return round((float) ($summary->qualifying_total ?? 0), 2);
    }

    protected static function isQualifyingOrder(Order $order)
    {
        $rewardSubcategoryId = ReferralProgramConfig::get(
            'order_history.subcategory_id',
            ReferralProgramConfig::get('data_commission.subcategory_id', 23)
        );

        if (
            (int) $order->subcategory_id === (int) $rewardSubcategoryId ||
            strpos((string) $order->ref, 'RWD-') === 0
        ) {
            return false;
        }

        if (
            !$order->subcategory ||
            !$order->subcategory->category
        ) {
            return false;
        }

        $category = strtolower(
            $order->subcategory->category->title
        );

        $categories = array_map(
            'strtolower',
            ReferralProgramConfig::categories()
        );

        return in_array($category, $categories);
    }

    protected static function isDataOrder(Order $order)
    {
        if (
            !$order->subcategory ||
            !$order->subcategory->category
        ) {
            return false;
        }

        $category = strtolower(
            $order->subcategory->category->title
        );

        $dataCategory = strtolower(
            ReferralProgramConfig::get('data_category', 'Data')
        );

        return $category === $dataCategory;
    }

    /**
     * Fixed rewards apply only to referrals registered from launch.
     * Data commissions deliberately do not use this restriction.
     */
    protected static function isNewProgramReferral(User $friend)
    {
        if (is_null($friend->created_at)) {
            return false;
        }

        return $friend->created_at->gte(self::programStartsAt());
    }

    protected static function programStartsAt()
    {
        return Carbon::parse(
            ReferralProgramConfig::get(
                'program_starts_at',
                '2026-08-01T15:08:27+01:00'
            )
        )->setTimezone(config('app.timezone', 'UTC'));
    }
}
