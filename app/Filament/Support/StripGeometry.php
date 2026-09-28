<?php

namespace App\Filament\Support;

final class StripGeometry
{
    private const GEOMETRY_ATTRIBUTES = [
        'coordinates',
        'geometry',
    ];

    /**
     * Remove Magellan geometry attributes from Filament form state.
     *
     * Geometry objects remain in Eloquent/PostGIS but must not
     * enter Livewire component state.
     */
    public static function from(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array($key, self::GEOMETRY_ATTRIBUTES, true)) {
                unset($data[$key]);

                continue;
            }

            if (is_array($value)) {
                $data[$key] = self::from($value);
            }
        }

        return $data;
    }
}
