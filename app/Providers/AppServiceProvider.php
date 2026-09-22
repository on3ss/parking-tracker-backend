<?php

namespace App\Providers;

use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        Relation::enforceMorphMap([
            'facility' => ParkingFacility::class,
            'street' => StreetParking::class,
        ]);
    }
}
