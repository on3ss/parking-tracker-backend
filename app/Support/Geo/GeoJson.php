<?php

namespace App\Support\Geo;

use Clickbar\Magellan\Data\Geometries\Geometry;
use Clickbar\Magellan\IO\Parser\Geojson\GeojsonParser;
use InvalidArgumentException;

final class GeoJson
{
    public const TYPES = [
        'point' => [
            'name' => 'Point',
            'min' => 1,
        ],

        'linestring' => [
            'name' => 'LineString',
            'min' => 2,
        ],

        'polygon' => [
            'name' => 'Polygon',
            'min' => 4,
        ],
    ];

    /**
     * Normalize a value into a GeoJSON geometry array.
     *
     * Supported inputs:
     * - Magellan Geometry
     * - GeoJSON array
     * - GeoJSON JSON string
     * - ['latitude' => ..., 'longitude' => ...]
     */
    public static function from(mixed $value): ?array
    {
        return match (true) {
            $value instanceof Geometry => self::geometryToArray($value),

            is_string($value) => self::fromJson($value),

            is_array($value) && isset($value['type']) => $value,

            is_array($value)
            && is_numeric($value['latitude'] ?? null)
            && is_numeric($value['longitude'] ?? null) => [
                'type' => 'Point',
                'coordinates' => [
                    (float) $value['longitude'],
                    (float) $value['latitude'],
                ],
            ],

            default => null,
        };
    }

    /**
     * Convert GeoJSON geometry state into a Magellan geometry.
     */
    public static function toGeometry(mixed $state): ?Geometry
    {
        if (blank($state)) {
            return null;
        }

        $error = self::validate(
            $state,
            strtolower($state['type'] ?? ''),
        );

        if ($error !== null) {
            throw new InvalidArgumentException($error);
        }

        return app(GeojsonParser::class)->parse(
            json_encode($state, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * Validate a GeoJSON geometry.
     *
     * Returns an error message, or null when valid.
     */
    public static function validate(
        mixed $value,
        string $type,
        int $maxVertices = 500,
    ): ?string {
        if (! isset(self::TYPES[$type])) {
            return __('Unsupported geometry type.');
        }

        $definition = self::TYPES[$type];
        $name = $definition['name'];

        if (
            ! is_array($value)
            || ($value['type'] ?? null) !== $name
            || ! is_array($value['coordinates'] ?? null)
        ) {
            return __('The geometry must be a :type.', [
                'type' => $name,
            ]);
        }

        $coordinates = $value['coordinates'];

        $vertices = match ($name) {
            'Point' => [$coordinates],

            'LineString' => $coordinates,

            'Polygon' => is_array($coordinates[0] ?? null)
                ? $coordinates[0]
                : [],
        };

        if (count($vertices) < $definition['min']) {
            return __('The geometry needs at least :count points.', [
                'count' => $definition['min'],
            ]);
        }

        if (count($vertices) > $maxVertices) {
            return __('The geometry has too many points (maximum :max).', [
                'max' => $maxVertices,
            ]);
        }

        foreach ($vertices as $vertex) {
            if (
                ! is_array($vertex)
                || count($vertex) < 2
                || ! is_numeric($vertex[0] ?? null)
                || ! is_numeric($vertex[1] ?? null)
            ) {
                return __('The geometry contains an invalid coordinate.');
            }

            $longitude = (float) $vertex[0];
            $latitude = (float) $vertex[1];

            if ($longitude < -180 || $longitude > 180) {
                return __('The geometry contains an invalid longitude.');
            }

            if ($latitude < -90 || $latitude > 90) {
                return __('The geometry contains an invalid latitude.');
            }
        }

        if ($name === 'Polygon') {
            $first = $vertices[0];
            $last = $vertices[count($vertices) - 1];

            if (
                (float) $first[0] !== (float) $last[0]
                || (float) $first[1] !== (float) $last[1]
            ) {
                return __('The polygon ring must be closed.');
            }
        }

        return null;
    }

    private static function fromJson(string $value): ?array
    {
        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE
            ? self::from($decoded)
            : null;
    }

    private static function geometryToArray(Geometry $geometry): ?array
    {
        $json = json_encode($geometry);

        if ($json === false) {
            return null;
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }
}
