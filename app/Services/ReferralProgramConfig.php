<?php

namespace App\Services;

use App\Models\ReferralProgramSetting;
use Illuminate\Support\Facades\Schema;

class ReferralProgramConfig
{
    private static $cache;

    public static function defaults(): array
    {
        return [
            'program_starts_at' => config('referrals.program_starts_at'),
            'welcome.minimum_spend' => config('referrals.welcome.minimum_spend', 500),
            'welcome.referrer_reward' => config('referrals.welcome.referrer_reward', 50),
            'welcome.friend_reward' => config('referrals.welcome.friend_reward', 50),
            'active.minimum_spend' => config('referrals.active.minimum_spend', 50000),
            'active.referrer_reward' => config('referrals.active.referrer_reward', 400),
            'diamond.users' => 5,
            'diamond.reward' => 2000,
            'silver.users' => 10,
            'silver.reward' => 8000,
            'gold.users' => 20,
            'gold.reward' => 32000,
            'qualifying_categories' => implode(
                ',',
                config('referrals.qualifying_categories', [])
            ),
            'data_category' => config('referrals.data_category', 'Data'),
            'data_commission.subcategory_id' => config(
                'referrals.data_commission.subcategory_id',
                23
            ),
            'order_history.subcategory_id' => config(
                'referrals.order_history.subcategory_id',
                23
            ),
        ];
    }

    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $values = self::defaults();

        if (!Schema::hasTable('referral_program_settings')) {
            return self::$cache = $values;
        }

        foreach (ReferralProgramSetting::all() as $setting) {
            if (array_key_exists($setting->key, $values)) {
                $values[$setting->key] = self::cast(
                    $setting->key,
                    $setting->value
                );
            }
        }

        return self::$cache = $values;
    }

    public static function get(string $key, $fallback = null)
    {
        $values = self::all();

        return $values[$key] ?? $fallback;
    }

    public static function categories(): array
    {
        return array_values(array_filter(array_map(
            'trim',
            explode(',', (string) self::get('qualifying_categories', ''))
        )));
    }

    public static function milestones(): array
    {
        $values = self::all();

        return [
            (int) $values['diamond.users'] => [
                'name' => 'Diamond',
                'reward' => (float) $values['diamond.reward'],
            ],
            (int) $values['silver.users'] => [
                'name' => 'Silver',
                'reward' => (float) $values['silver.reward'],
            ],
            (int) $values['gold.users'] => [
                'name' => 'Gold',
                'reward' => (float) $values['gold.reward'],
            ],
        ];
    }

    public static function forget(): void
    {
        self::$cache = null;
    }

    private static function cast(string $key, $value)
    {
        if (in_array($key, [
            'diamond.users',
            'silver.users',
            'gold.users',
            'data_commission.subcategory_id',
            'order_history.subcategory_id',
        ], true)) {
            return (int) $value;
        }

        if (strpos($key, 'reward') !== false || strpos($key, 'spend') !== false) {
            return (float) $value;
        }

        return $value;
    }
}
