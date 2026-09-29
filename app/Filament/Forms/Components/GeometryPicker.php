<?php

namespace App\Filament\Forms\Components;

use Clickbar\Magellan\Data\Geometries\Geometry;
use Clickbar\Magellan\IO\Parser\Geojson\GeojsonParser;
use Closure;
use Filament\Forms\Components\Field;
use InvalidArgumentException;

class GeometryPicker extends Field
{
    protected string $view = 'filament.forms.components.geometry-picker';

    private const TYPES = [
        'point' => ['GeoJSON' => 'Point', 'min' => 1],
        'linestring' => ['GeoJSON' => 'LineString', 'min' => 2],
        'polygon' => ['GeoJSON' => 'Polygon', 'min' => 4], // closed ring
    ];

    protected string $geometryType = 'linestring';
    protected array $center = [25.5779, 91.8837]; // [lat, lng]
    protected int $zoom = 14;
    protected int $height = 400;
    protected int $maxVertices = 500;

    public function geometryType(string $type): static
    {
        if (!isset(self::TYPES[$type])) {
            throw new InvalidArgumentException("Unsupported geometry type [{$type}].");
        }

        $this->geometryType = $type;

        return $this;
    }

    public function center(float $lat, float $lng): static
    {
        $this->center = [$lat, $lng];

        return $this;
    }

    public function zoom(int $zoom): static
    {
        $this->zoom = $zoom;
        return $this;
    }
    public function height(int $px): static
    {
        $this->height = $px;
        return $this;
    }
    public function maxVertices(int $max): static
    {
        $this->maxVertices = $max;
        return $this;
    }

    public function getGeometryType(): string
    {
        return $this->geometryType;
    }
    public function getCenter(): array
    {
        return $this->center;
    }
    public function getZoom(): int
    {
        return $this->zoom;
    }
    public function getHeight(): int
    {
        return $this->height;
    }
    public function getMaxVertices(): int
    {
        return $this->maxVertices;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Model attribute (Magellan geometry) -> form state (GeoJSON array)
        $this->afterStateHydrated(
            fn(GeometryPicker $component, mixed $state) => $component->state(static::toGeoJson($state))
        );

        // Form state (GeoJSON array) -> model attribute (Magellan geometry, SRID 4326)
        $this->dehydrateStateUsing(fn(mixed $state) => static::toGeometry($state));

        $this->rule(fn() => function (string $attribute, mixed $value, Closure $fail): void {
            if (filled($value) && ($error = $this->validateGeoJson($value))) {
                $fail($error);
            }
        });
    }

    public static function toGeoJson(mixed $value): ?array
    {
        return match (true) {
            $value instanceof Geometry => json_decode(json_encode($value), true),
            is_string($value) => json_decode($value, true) ?: null,
            is_array($value) => $value ?: null,
            default => null,
        };
    }

    public static function toGeometry(mixed $state): ?Geometry
    {
        return filled($state)
            ? app(GeojsonParser::class)->parse(json_encode($state)) // defaults to SRID 4326
            : null;
    }

    protected function validateGeoJson(mixed $value): ?string
    {
        $config = self::TYPES[$this->geometryType];
        $expected = $config['GeoJSON'];

        if (!is_array($value) || ($value['type'] ?? null) !== $expected || !is_array($value['coordinates'] ?? null)) {
            return __('The geometry must be a :type.', ['type' => $expected]);
        }

        $coordinates = $value['coordinates'];

        $vertices = match ($expected) {
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

        if (count($vertices) > $this->maxVertices) {
            return __('The geometry has too many points (maximum :max).', ['max' => $this->maxVertices]);
        }

        return null;
    }
}