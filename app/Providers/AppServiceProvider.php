<?php

namespace App\Providers;

use App\Models\QrGeneration;
use App\Models\QrLink;
use App\Policies\QrGenerationPolicy;
use App\Policies\QrLinkPolicy;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(QrGeneration::class, QrGenerationPolicy::class);
        Gate::policy(QrLink::class, QrLinkPolicy::class);
    }
}
