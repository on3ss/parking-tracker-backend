<?php

namespace App\Filament\Infolists\Components;

use App\Filament\Concerns\HasMapOptions;
use App\Support\Geo\GeoJson;
use Filament\Infolists\Components\Entry;

class GeometryEntry extends Entry
{
    use HasMapOptions;

    protected string $view = 'filament.infolists.components.geometry-entry';

    protected function defaultMapHeight(): int
    {
        return 350;
    }

    /** State may be a Magellan geometry, GeoJSON, or ['latitude' => .., 'longitude' => ..]. */
    public function getGeoJson(): ?array
    {
        return GeoJson::from($this->getState());
    }
}