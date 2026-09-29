<?php

namespace App\Providers;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        FilamentAsset::register([
            AlpineComponent::make(
                'geometry-map',
                resource_path('js/filament/geometry-map.js'),
            ),

            AlpineComponent::make(
                'leaflet-adapter',
                resource_path('js/filament/leaflet-adapter.js'),
            ),
        ]);
    }
}