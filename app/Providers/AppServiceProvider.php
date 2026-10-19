<?php

namespace App\Providers;

use App\Services\Rbac\ProviderMembershipLookup;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(
            ProviderMembershipLookup::class,
            fn() => new ProviderMembershipLookup(),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
