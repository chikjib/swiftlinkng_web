<?php

namespace App\Http\Controllers\API;

use App\Models\Subcategory;
use App\Services\{InternetAdmin, InternationalTopup, Sochitel};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Http, Log};

class InternetAdminController extends BaseController
{
    public function index(InternetAdmin $admin)
    {
        $providers = collect(InternetAdmin::PROVIDERS)->map(function ($provider) use ($admin) {
            $settings = $admin->settings($provider);
            if ($provider !== 'fibrex') {
                $sourceEnabled = Subcategory::where('description', 'INTERNET_'.strtoupper($provider))->value('status');
                $settings->enabled = (bool) $settings->enabled && (bool) $sourceEnabled;
            }
            $settings->packages = DB::table('internet_packages')->where('provider', $provider)->count();
            return $settings;
        });
        return response()->json(['data' => $providers]);
    }

    public function packages(string $provider, InternetAdmin $admin)
    {
        $settings = $admin->settings($provider);
        $rows = DB::table('internet_packages')->where('provider', $provider)->orderBy('name')->get()->map(function ($row) use ($admin, $settings) {
            $plan = json_decode($row->snapshot, true);
            // Internal provider payloads never leave the backend.
            return ['id' => $row->id, 'name' => $row->name, 'enabled' => (bool) $row->enabled, 'available' => (bool) $row->available, 'cost' => (float) $row->cost, 'price_override' => $row->price_override, 'selling_price' => $admin->price($settings, $plan, $row), 'updated_at' => $row->updated_at, 'source_subcategory_id' => $row->source_subcategory_id];
        });
        return response()->json(['data' => $rows]);
    }

    public function updateProvider(Request $request, string $provider, InternetAdmin $admin)
    {
        $values = $request->validate(['enabled' => 'required|boolean', 'multiplier' => 'nullable|numeric|min:1|max:10|regex:/^\d+(\.\d{1,4})?$/', 'fixed_fee' => 'required|numeric|min:0|max:1000000|regex:/^\d+(\.\d{1,2})?$/']);
        DB::transaction(function () use ($request, $provider, $admin, $values) {
            $before = $admin->settings($provider, true);
            DB::table('internet_provider_settings')->where('provider', $provider)->update($values + ['updated_at' => now()]);
            if ($provider !== 'fibrex') Subcategory::where('description', 'INTERNET_'.strtoupper($provider))->update(['status' => $values['enabled'], 'updated_at' => now()]);
            $admin->audit($request->user()->id, ['provider' => $provider, 'action' => 'settings'] + $values, (array) $before);
        });
        return $this->index($admin);
    }

    public function updatePackage(Request $request, string $provider, int $id, InternetAdmin $admin)
    {
        $values = $request->validate(['enabled' => 'required|boolean', 'price_override' => 'nullable|numeric|min:1|max:100000000|regex:/^\d+(\.\d{1,2})?$/']);
        DB::transaction(function () use ($request, $provider, $id, $admin, $values) {
            $settings = $admin->settings($provider, true);
            $row = DB::table('internet_packages')->where('provider', $provider)->where('id', $id)->lockForUpdate()->first();
            abort_unless($row, 404);
            $next = (object) array_merge((array) $row, $values);
            abort_unless(!$values['enabled'] || $admin->price($settings, json_decode($row->snapshot, true), $next) !== null, 422, 'Selling price must cover the current provider cost.');
            DB::table('internet_packages')->where('id', $id)->update($values + ['updated_at' => now()]);
            $admin->audit($request->user()->id, ['provider' => $provider, 'package_id' => $id, 'action' => 'package'] + $values, ['enabled' => $row->enabled, 'price_override' => $row->price_override]);
        });
        return $this->packages($provider, $admin);
    }

    public function sync(Request $request, string $provider, InternetAdmin $admin)
    {
        $admin->settings($provider);
        try {
            $count = $admin->sync($provider, $request->user()->id);
            return response()->json(['message' => "Synced {$count} packages. Existing switches and overrides were preserved."]);
        } catch (\Throwable $e) {
            Log::warning('internet.admin_sync_failed', ['provider' => $provider, 'error' => $e->getMessage()]);
            return response()->json(['message' => $e instanceof \App\Services\SochitelConnectionException ? $e->getMessage() : 'Package sync failed. The previous package list was preserved. Check the provider configuration and retry.'], 502);
        }
    }

    public function test(Request $request, string $provider, InternetAdmin $admin)
    {
        $admin->settings($provider);
        try {
            if (in_array($provider, ['spectranet', 'nitel'], true)) app(Sochitel::class)->verify(app(InternationalTopup::class)->settings());
            if ($provider === 'fibrex') {
                // AutoSync documents GET /me for authenticated account checks.
                $base = rtrim((string) config('app.autoSyncUrl'), '/');
                abort_unless($base && config('app.autoSyncToken'), 503, 'Configure AutoSync credentials.');
                $response = Http::withToken(config('app.autoSyncToken'))->acceptJson()->connectTimeout(10)->timeout(30)->get($base.'/me');
                abort_unless($response->successful() && $response->json('status') === 'ok', 503, 'AutoSync account connection failed.');
            }
            $count = count($admin->rawPlans($provider));
            $message = $provider === 'fibrex' ? "AutoSync account connection succeeded; {$count} configured Fibre X packages." : "Catalogue connection succeeded; {$count} compatible packages. No purchase was made.";
            $status = 'success';
        } catch (\Throwable $e) {
            Log::warning('internet.admin_test_failed', ['provider' => $provider, 'error' => $e->getMessage()]);
            $status = 'failed';
            $message = $e instanceof \App\Services\SochitelConnectionException ? $e->getMessage() : 'Connection test failed. Check the configured provider credentials and account access.';
        }
        DB::transaction(function () use ($provider, $admin, $request, $status, $message) {
            $admin->settings($provider, true);
            DB::table('internet_provider_settings')->where('provider', $provider)->update(['tested_at' => now(), 'test_status' => $status, 'test_message' => $message, 'updated_at' => now()]);
            $admin->audit($request->user()->id, ['provider' => $provider, 'action' => 'connection_test', 'result' => $status]);
        });
        return response()->json(['message' => $message, 'status' => $status], $status === 'success' ? 200 : 502);
    }
}
