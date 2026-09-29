<?php

namespace App\Support\Geo;

use Clickbar\Magellan\Data\Geometries\Geometry;
use Clickbar\Magellan\IO\Parser\Geojson\GeojsonParser;

final class GeoJson
{
    public const TYPES = [
        'point' => ['name' => 'Point', 'min' => 1],
        'linestring' => ['name' => 'LineString', 'min' => 2],
        'polygon' => ['name' => 'Polygon', 'min' => 4], // closed ring
    ];

    /**
     * Normalize anything we might be handed into a GeoJSON geometry array:
     * a Magellan geometry, a GeoJSON array or string, or a latitude/longitude array.
     */
    public static function from(mixed $value): ?array
    {
        return match (true) {
            $value instanceof Geometry => json_decode(json_encode($value), true),
            is_string($value) => static::from(json_decode($value, true)),
            is_array($value) && isset($value['type']) => $value,
            is_array($value)
            && is_numeric($value['latitude'] ?? null)
            && is_numeric($value['longitude'] ?? null) => [
                'type' => 'Point',
                'coordinates' => [(float) $value['longitude'], (float) $value['latitude']],
            ],
            default => null,
        };
    }

    /** GeoJSON array -> Magellan geometry (SRID 4326). */
    public static function toGeometry(mixed $state): ?Geometry
    {
        return filled($state)
            ? app(GeojsonParser::class)->parse(json_encode($state))
            : null;
    }

    /** Returns an error message, or null when the geometry is valid. */
    public static function validate(mixed $value, string $type, int $maxVertices = 500): ?string
    {
        $config = self::TYPES[$type];
        $name = $config['name'];

        if (!is_array($value) || ($value['type'] ?? null) !== $name || !is_array($value['coordinates'] ?? null)) {
            return __('The geometry must be a :type.', ['type' => $name]);
        }

        $coordinates = $value['coordinates'];

        $vertices = match ($name) {
            'Point' => [$coordinates],
            'LineString' => $coordinates,
            'Polygon' => is_array($coordinates[0] ?? null) ? $coordinates[0] : [],
        };

        foreach ($vertices as $vertex) {
            if (
                !is_array($vertex) || count($vertex) < 2
                || !is_numeric($vertex[0]) || !is_numeric($vertex[1])
                || abs($vertex[0]) > 180 || abs($vertex[1]) > 90
            ) {
                return __('The geometry contains an invalid coordinate.');
            }
        }

        if (count($vertices) < $config['min']) {
            return __('The geometry needs at least :count points.', ['count' => $config['min']]);
        }

        if (count($vertices) > $maxVertices) {
            return __('The geometry has too many points (maximum :max).', ['max' => $maxVertices]);
        }

        return null;
    }
}