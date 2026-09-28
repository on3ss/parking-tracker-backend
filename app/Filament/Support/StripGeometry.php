<?php

namespace App\Filament\Support;

final class StripGeometry
{
    /**
     * Remove Magellan geometry attributes from Filament form state.
     *
     * Geometry objects must remain in Eloquent/PostGIS, but must not
     * enter Livewire component state.
     */
    public static function from(
        array $data,
        array $attributes = [
            'coordinates',
            'geometry',
        ]
    ): array {
        foreach ($attributes as $attribute) {
            unset($data[$attribute]);
        }

        return $data;
    }
}