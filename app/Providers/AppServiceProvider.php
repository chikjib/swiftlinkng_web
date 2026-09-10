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
        //

        if (App::environment('production', 'staging')) {

            $this->app['request']->server->set('HTTPS', 'on'); // this line
            URL::forceScheme('https');
        }
        
        $settings = Cache::remember("social_settings",60*12,function() {
            return Setting::where('key', 'SOCIAL')->first();
        });
        
        $tawkto = Cache::remember("live_chat",60*12,function() {
            return Setting::where('key', 'LIVECHAT')->first();
        });
        
        $setting = explode('|', $settings->value);
        $facebook = $setting[0];
        $instagram = $setting[1];
        $twitter = $setting[2];

        $data = array("facebook" => $facebook, "instagram" => $instagram, "twitter" => $twitter,"live_chat" => $tawkto->value);

        view()->share("data", $data);

    }
}
