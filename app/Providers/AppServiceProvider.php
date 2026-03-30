<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register application service providers that need to be loaded early.
        // Ensure the ChangelogServiceProvider bindings are available without
        // requiring manual config changes.
        $this->app->register(\App\Providers\ChangelogServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
