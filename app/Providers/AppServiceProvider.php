<?php

namespace App\Providers;

use App\Models\ParkingFacility;
use App\Models\StreetParking;
use App\Models\User;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Facades\FilamentAsset;
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
            'user' => User::class,
            'facility' => ParkingFacility::class,
            'street' => StreetParking::class,
        ]);

        FilamentAsset::register([
            AlpineComponent::make('geometry-map', resource_path('js/filament/geometry-map.js')),
            AlpineComponent::make(
                'leaflet-adapter',
                resource_path('js/filament/leaflet-adapter.js'),
            ),
        ]);
    }
}
