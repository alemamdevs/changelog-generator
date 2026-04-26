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
        $this->app->register(ChangelogServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('webhook', function (Request $request): Limit {
            return Limit::perMinute(30)
                ->by($request->ip() ?? 'webhook')
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many webhook requests.',
                    ], 429, $headers);
                });
        });
    }
}
