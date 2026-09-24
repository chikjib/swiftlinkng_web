<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/dashboard/home';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            if ($request->is('api/login')) {
                return Limit::perMinute(120)->by('api-login:'.$this->loginRateLimitKey($request));
            }

            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)
                ->by('login:'.$this->loginRateLimitKey($request))
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many login attempts for this account. Please wait one minute and try again.',
                    ], 429, $headers);
                });
        });
    }

    private function loginRateLimitKey(Request $request): string
    {
        $email = strtolower(trim((string) $request->input('email')));

        if (strpos($email, '@') !== false) {
            [$local, $domain] = explode('@', $email, 2);
            if (in_array($domain, ['gmail.com', 'googlemail.com'], true)) {
                $local = explode('+', $local, 2)[0];
                $local = str_replace('.', '', $local);
                $email = $local.'@gmail.com';
            }
        }

        $identity = $email !== '' ? 'email:'.$email : 'ip:'.$request->ip();

        return hash('sha256', $identity);
    }
}
