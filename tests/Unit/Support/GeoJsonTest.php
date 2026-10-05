<?php

use App\Support\Geo\GeoJson;

describe('from', function () {
    it('converts latitude and longitude to a GeoJSON point', function () {
        expect(GeoJson::from([
            'latitude' => 25.5788,
            'longitude' => 91.8933,
        ]))->toBe([
            'type' => 'Point',
            'coordinates' => [
                91.8933,
                25.5788,
            ],
        ]);
    });

    it('accepts an existing GeoJSON geometry array', function () {
        $geometry = [
            'type' => 'Point',
            'coordinates' => [91.8933, 25.5788],
        ];

        expect(GeoJson::from($geometry))
            ->toBe($geometry);
    });

    it('parses a GeoJSON JSON string', function () {
        $geometry = [
            'type' => 'Point',
            'coordinates' => [91.8933, 25.5788],
        ];

        expect(GeoJson::from(json_encode($geometry)))
            ->toBe($geometry);
    });

    it('returns null for invalid JSON', function () {
        expect(GeoJson::from('{invalid-json'))
            ->toBeNull();
    });

    it('returns null for unsupported input', function (mixed $value) {
        expect(GeoJson::from($value))
            ->toBeNull();
    })->with([
        'null' => [null],
        'string' => ['not geojson'],
        'integer' => [123],
        'object' => [new stdClass],
        'empty array' => [[]],
    ]);

    it('casts latitude and longitude to floats', function () {
        expect(GeoJson::from([
            'latitude' => '25.5788',
            'longitude' => '91.8933',
        ]))->toBe([
            'type' => 'Point',
            'coordinates' => [
                91.8933,
                25.5788,
            ],
        ]);
    });
});

describe('validate', function () {
    it('accepts a valid point', function () {
        expect(GeoJson::validate(
            [
                'type' => 'Point',
                'coordinates' => [91.8933, 25.5788],
            ],
            'point',
        ))->toBeNull();
    });

    it('accepts a valid linestring', function () {
        expect(GeoJson::validate(
            [
                'type' => 'LineString',
                'coordinates' => [
                    [91.8933, 25.5788],
                    [91.8940, 25.5790],
                ],
            ],
            'linestring',
        ))->toBeNull();
    });

    it('accepts a valid closed polygon', function () {
        expect(GeoJson::validate(
            [
                'type' => 'Polygon',
                'coordinates' => [
                    [
                        [91.8933, 25.5788],
                        [91.8940, 25.5788],
                        [91.8940, 25.5790],
                        [91.8933, 25.5788],
                    ],
                ],
            ],
            'polygon',
        ))->toBeNull();
    });

    it('rejects unsupported geometry types', function () {
        expect(GeoJson::validate(
            [
                'type' => 'MultiPoint',
                'coordinates' => [[91.8933, 25.5788]],
            ],
            'multipoint',
        ))->toBe('Unsupported geometry type.');
    });

    it('rejects a geometry whose type does not match the requested type', function () {
        expect(GeoJson::validate(
            [
                'type' => 'LineString',
                'coordinates' => [
                    [91.8933, 25.5788],
                    [91.8940, 25.5790],
                ],
            ],
            'point',
        ))->toBe('The geometry must be a Point.');
    });

    it('rejects missing coordinates', function () {
        expect(GeoJson::validate(
            ['type' => 'Point'],
            'point',
        ))->toBe('The geometry must be a Point.');
    });
});

describe('vertex count', function () {
    it('rejects a point without coordinates', function () {
        expect(GeoJson::validate(
            [
                'type' => 'Point',
                'coordinates' => [],
            ],
            'point',
        ))->toBe('The geometry contains an invalid coordinate.');
    });

    it('requires at least two points for a linestring', function () {
        expect(GeoJson::validate(
            [
                'type' => 'LineString',
                'coordinates' => [
                    [91.8933, 25.5788],
                ],
            ],
            'linestring',
        ))->toBe('The geometry needs at least 2 points.');
    });

    it('requires at least four points for a polygon ring', function () {
        expect(GeoJson::validate(
            [
                'type' => 'Polygon',
                'coordinates' => [
                    [
                        [91.8933, 25.5788],
                        [91.8940, 25.5788],
                        [91.8940, 25.5790],
                    ],
                ],
            ],
            'polygon',
        ))->toBe('The geometry needs at least 4 points.');
    });

    it('rejects geometries exceeding the maximum vertex count', function () {
        $coordinates = array_fill(
            0,
            501,
            [91.8933, 25.5788],
        );

        expect(GeoJson::validate(
            [
                'type' => 'LineString',
                'coordinates' => $coordinates,
            ],
            'linestring',
        ))->toBe(
            'The geometry has too many points (maximum 500).'
        );
    });

    it('allows a custom maximum vertex count', function () {
        $coordinates = [
            [91.8933, 25.5788],
            [91.8940, 25.5790],
            [91.8950, 25.5800],
        ];

        expect(GeoJson::validate(
            [
                'type' => 'LineString',
                'coordinates' => $coordinates,
            ],
            'linestring',
            2,
        ))->toBe(
            'The geometry has too many points (maximum 2).'
        );
    });
});

describe('coordinates', function () {
    it('rejects coordinates with fewer than two values', function () {
        expect(GeoJson::validate(
            [
                'type' => 'Point',
                'coordinates' => [91.8933],
            ],
            'point',
        ))->toBe('The geometry contains an invalid coordinate.');
    });

    it('rejects non-numeric coordinates', function () {
        expect(GeoJson::validate(
            [
                'type' => 'Point',
                'coordinates' => ['longitude', 'latitude'],
            ],
            'point',
        ))->toBe('The geometry contains an invalid coordinate.');
    });

    it('rejects longitude below -180', function () {
        expect(GeoJson::validate(
            [
                'type' => 'Point',
                'coordinates' => [-180.001, 25.5788],
            ],
            'point',
        ))->toBe('The geometry contains an invalid longitude.');
    });

    it('rejects longitude above 180', function () {
        expect(GeoJson::validate(
            [
                'type' => 'Point',
                'coordinates' => [180.001, 25.5788],
            ],
            'point',
        ))->toBe('The geometry contains an invalid longitude.');
    });

    it('rejects latitude below -90', function () {
        expect(GeoJson::validate(
            [
                'type' => 'Point',
                'coordinates' => [91.8933, -90.001],
            ],
            'point',
        ))->toBe('The geometry contains an invalid latitude.');
    });

    it('rejects latitude above 90', function () {
        expect(GeoJson::validate(
            [
                'type' => 'Point',
                'coordinates' => [91.8933, 90.001],
            ],
            'point',
        ))->toBe('The geometry contains an invalid latitude.');
    });

    it('accepts coordinate boundary values', function () {
        expect(GeoJson::validate(
            [
                'type' => 'Point',
                'coordinates' => [180, 90],
            ],
            'point',
        ))->toBeNull();

        expect(GeoJson::validate(
            [
                'type' => 'Point',
                'coordinates' => [-180, -90],
            ],
            'point',
        ))->toBeNull();
    });
});
