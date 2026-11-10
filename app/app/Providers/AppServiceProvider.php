<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach (['register', 'login', 'google'] as $action) {
            RateLimiter::for("auth-{$action}", function (Request $request): array {
                $identity = Str::lower((string) $request->input('email', ''));

                return [
                    Limit::perMinute(5)->by($identity.'|'.$request->ip()),
                    Limit::perMinute(20)->by($request->ip()),
                ];
            });
        }

        foreach (['verify-email', 'resend-otp'] as $action) {
            RateLimiter::for("auth-{$action}", function (Request $request): array {
                $identity = Str::lower((string) $request->input('email', ''));

                return [
                    Limit::perMinute(5)->by($identity.'|'.$request->ip()),
                    Limit::perMinute(15)->by($request->ip()),
                ];
            });
        }

        RateLimiter::for('auth-refresh', fn (Request $request): array => [
            Limit::perMinute(10)->by($request->ip()),
        ]);
    }
}
