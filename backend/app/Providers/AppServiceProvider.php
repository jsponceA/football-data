<?php

namespace App\Providers;

use App\Services\FootballData\ApiRateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // una sola instancia para que todas las llamadas compartan el mismo límite
        $this->app->singleton(ApiRateLimiter::class, fn() => new ApiRateLimiter(
            config('services.football_data.rate_limit'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // por defecto Passport lo deja un año, es demasiado a mi criterio mejor es 8 horas
        Passport::personalAccessTokensExpireIn(now()->addHours(8));
    }
}
