<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class ServiceControls {
    public static function get(bool $lock = false): object {
        $query = DB::table('service_controls')->where('id', 1);
        if ($lock) $query->lockForUpdate();
        $row = $query->first();
        abort_unless($row, 503, 'Services are being configured.');
        return $row;
    }
    public static function enabled(string $service, bool $lock = false): object {
        $settings = self::get($lock);
        abort_unless($settings->{$service.'_enabled'}, 503, ucfirst($service).' is currently switched off. Please try again later.');
        return $settings;
    }
    public static function kobo(int $units, string $rate): int {
        abort_unless(preg_match('/^\d{1,6}(\.\d{1,4})?$/', $rate), 503, 'Invalid exchange rate.');
        [$whole,$decimal] = array_pad(explode('.', $rate, 2), 2, '');
        $scaled = ((int) $whole * 10000) + (int) str_pad($decimal, 4, '0');
        abort_unless($units > 0 && $units <= 100000000 && $scaled > 0 && $scaled <= 1000000000, 503, 'Plan pricing is unavailable.');
        return intdiv($units * $scaled + 999999, 1000000);
    }
}
