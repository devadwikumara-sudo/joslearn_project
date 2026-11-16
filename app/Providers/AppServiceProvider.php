<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        // Izinkan file view berekstensi .php diproses dengan compiler Blade
        app('view')->addExtension('php', 'blade');

        RateLimiter::for('web-auth-otp', function (Request $request): Limit {
            $path = $request->path();
            $attempts = match ($path) {
                'verifikasi-akun' => 6,
                'verifikasi-akun/kirim-ulang', 'reset-sandi' => 3,
                default => 5,
            };

            return Limit::perMinute($attempts)->by($path.'|'.$request->ip());
        });

        RateLimiter::for('mobile-auth-otp', function (Request $request): Limit {
            $path = $request->path();
            $attempts = match ($path) {
                'api/v1/verifikasi-otp' => 6,
                'api/v1/kirim-ulang-otp', 'api/v1/lupa-password' => 3,
                default => 5,
            };

            return Limit::perMinute($attempts)->by($path.'|'.$request->ip());
        });
    }
}
