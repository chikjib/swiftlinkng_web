<?php

namespace App\Services;

use App\Models\Subcategory;
use Illuminate\Support\Facades\DB;

class InternetAdmin
{
    public const PROVIDERS = ['fibrex', 'smile', 'spectranet', 'nitel'];

    public function settings(string $provider, bool $lock = false): object
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);
        $query = DB::table('internet_provider_settings')->where('provider', $provider);
        $row = ($lock ? $query->lockForUpdate() : $query)->first();
        abort_unless($row, 503, 'Internet admin setup is incomplete. Run the latest migrations.');
        return $row;
    }

    public static function packageKey(string $provider, array $plan): string
    {
        if ($provider === 'fibrex') return $plan['source_subcategory_id'].':'.hash('sha256', $plan['plan']);
        return (string) ($plan['code'] ?? $plan['plan']);
    }

    public function fibrePlan(Subcategory $source, array $plan, int $level): array
    {
        $amounts = [];
        for ($tier = 0; $tier < 4; $tier++) $amounts[$tier] = $plan['amount'.($tier + 1)] ?? null;
        return [
            'plan' => $plan['plan'], 'name' => $plan['plan'],
            'amount' => $amounts[$level] ?? $amounts[0], 'amounts' => $amounts,
            'source_subcategory_id' => $source->id, 'cost' => (float) ($plan['cost_price'] ?? 0),
        ];
    }

    public function rawPlans(string $provider): array
    {
        if ($provider !== 'fibrex') return app(InternetService::class)->providerPlans($provider, true);
        $plans = [];
        foreach (Subcategory::where('description', 'AUTOSYNCAWUF')->where('status', 1)->get() as $source) {
            foreach (json_decode($source->products ?: '[]', true) ?: [] as $plan) {
                if (is_array($plan) && InternetService::isFibre($source->title.' '.($plan['plan'] ?? ''))) {
                    $plans[] = $this->fibrePlan($source, $plan, 0);
                }
            }
        }
        return $plans;
    }

    public function price(object $settings, array $plan, ?object $package): ?int
    {
        $baseline = (float) ($plan['amount'] ?? 0);
        $cost = (float) ($plan['cost'] ?? 0);
        $price = $package && $package->price_override !== null
            ? (float) $package->price_override
            : ($settings->multiplier !== null ? ($cost > 0 ? $cost : $baseline) * (float) $settings->multiplier : $baseline) + (float) $settings->fixed_fee;
        if ($price <= 0 || $price > 100000000 || $price < $cost) return null;
        return (int) ceil(round($price, 6));
    }

    public function apply(string $provider, array $plans, bool $lock = false): array
    {
        $settings = $this->settings($provider, $lock);
        if (!$settings->enabled) return [];
        $query = DB::table('internet_packages')->where('provider', $provider);
        $packages = ($lock ? $query->lockForUpdate() : $query)->get()->keyBy('package_key');
        $result = [];
        foreach ($plans as $plan) {
            $package = $packages->get(self::packageKey($provider, $plan));
            if ($settings->synced_at && !$package) continue;
            if ($package && (!$package->enabled || !$package->available)) continue;
            $price = $this->price($settings, $plan, $package);
            if ($price === null) continue;
            $plan['amount'] = $price;
            $result[] = $plan;
        }
        return $result;
    }

    public function fibrePrice(Subcategory $source, array $plan, int $level): ?int
    {
        $managed = $this->apply('fibrex', [$this->fibrePlan($source, $plan, $level)]);
        return $managed[0]['amount'] ?? null;
    }

    public function sync(string $provider, int $admin): int
    {
        // Never mark old packages missing until the entire provider response succeeds.
        $plans = $this->rawPlans($provider);
        return DB::transaction(function () use ($provider, $admin, $plans) {
            $this->settings($provider, true);
            DB::table('internet_packages')->where('provider', $provider)->update(['available' => false]);
            foreach ($plans as $plan) {
                $key = self::packageKey($provider, $plan);
                $values = ['name' => $plan['name'] ?? $plan['plan'], 'source_subcategory_id' => $plan['source_subcategory_id'] ?? null, 'cost' => $plan['cost'] ?? 0, 'snapshot' => json_encode($plan, JSON_THROW_ON_ERROR), 'available' => true, 'updated_at' => now()];
                $query = DB::table('internet_packages')->where('provider', $provider)->where('package_key', $key);
                if ($query->exists()) $query->update($values);
                else DB::table('internet_packages')->insert($values + ['provider' => $provider, 'package_key' => $key, 'enabled' => true, 'created_at' => now()]);
            }
            DB::table('internet_provider_settings')->where('provider', $provider)->update(['synced_at' => now(), 'updated_at' => now()]);
            $this->audit($admin, ['provider' => $provider, 'action' => 'sync', 'packages' => count($plans)]);
            return count($plans);
        });
    }

    public function audit(int $admin, array $after, array $before = []): void
    {
        DB::table('service_control_audits')->insert(['admin_id' => $admin, 'before_values' => json_encode($before), 'after_values' => json_encode(['service' => 'internet'] + $after), 'created_at' => now(), 'updated_at' => now()]);
    }
}
