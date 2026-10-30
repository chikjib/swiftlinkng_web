<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */


    public function boot()
    {
        // Capture every provider response made during a payment/account request.
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Http\Client\Events\ResponseReceived::class, function ($event) {
            if (!\App\Services\PaymentResponseLogger::active()) return;
            \App\Services\PaymentResponseLogger::record('provider_response', array_merge([
                'provider' => parse_url($event->request->url(), PHP_URL_HOST),
                'operation' => parse_url($event->request->url(), PHP_URL_PATH),
                'http_status' => $event->response->status(),
            ], \App\Services\PaymentResponseLogger::body($event->response->body())));
        });
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Http\Client\Events\ConnectionFailed::class, function ($event) {
            if (!\App\Services\PaymentResponseLogger::active()) return;
            \App\Services\PaymentResponseLogger::record('provider_connection_failed', [
                'provider' => parse_url($event->request->url(), PHP_URL_HOST),
                'operation' => parse_url($event->request->url(), PHP_URL_PATH),
            ]);
        });
        //

        if (App::environment('production', 'staging')) {

            $this->app['request']->server->set('HTTPS', 'on'); // this line
            URL::forceScheme('https');
        }

        // Resolve database-backed view data only when rendering a view. Artisan
        // commands (including migrations and package discovery) must boot without it.
        view()->composer('*', function ($view) {
            $settings = Cache::remember('social_settings', 60 * 12, function () {
                return Setting::where('key', 'SOCIAL')->first();
            });
            [$facebook, $instagram, $twitter] = array_pad(
                explode('|', $settings?->value ?? ''), 3, ''
            );
            $view->with('data', compact('facebook', 'instagram', 'twitter'));
        });
    }
}
