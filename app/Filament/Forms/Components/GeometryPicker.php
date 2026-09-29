<?php

namespace App\Filament\Forms\Components;

use App\Filament\Concerns\HasMapOptions;
use App\Support\Geo\GeoJson;
use Clickbar\Magellan\Data\Geometries\Geometry;
use Closure;
use Filament\Forms\Components\Field;
use Illuminate\Support\Str;
use InvalidArgumentException;

class GeometryPicker extends Field
{
    use HasMapOptions;

    protected string $view = 'filament.forms.components.geometry-picker';

    protected string $geometryType = 'linestring';
    protected int $maxVertices = 500;

    /** @var array{0: string, 1: string}|null */
    protected ?array $coordinateFields = null;

    public function geometryType(string $type): static
    {
        if (!isset(GeoJson::TYPES[$type])) {
            throw new InvalidArgumentException("Unsupported geometry type [{$type}].");
        }

        $this->geometryType = $type;

        return $this;
    }

    public function maxVertices(int $max): static
    {
        $this->maxVertices = $max;

        return $this;
    }

    /**
     * Point picker that edits sibling latitude/longitude inputs instead of
     * storing GeoJSON. The picker itself is not dehydrated. Put `required()`
     * on the inputs, not on the picker.
     */
    public function coordinateFields(string $latitude = 'latitude', string $longitude = 'longitude'): static
    {
        $this->geometryType('point');
        $this->coordinateFields = [$latitude, $longitude];
        $this->dehydrated(false);

        return $this;
    }

    public function getGeometryType(): string
    {
        return $this->geometryType;
    }

    public function isBoundToCoordinates(): bool
    {
        return $this->coordinateFields !== null;
    }

    public function getLatitudeStatePath(): string
    {
        return $this->siblingStatePath($this->coordinateFields[0]);
    }

    public function getLongitudeStatePath(): string
    {
        return $this->siblingStatePath($this->coordinateFields[1]);
    }

    protected function siblingStatePath(string $name): string
    {
        return Str::beforeLast($this->getStatePath(), '.') . '.' . $name;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Model attribute (Magellan geometry) -> form state (GeoJSON array)
        $this->afterStateHydrated(
            fn(GeometryPicker $component, mixed $state) => $component->state(GeoJson::from($state))
        );

        // Form state (GeoJSON array) -> model attribute (Magellan geometry, SRID 4326)
        $this->dehydrateStateUsing(fn(mixed $state) => GeoJson::toGeometry($state));

        $this->rule(fn() => function (string $attribute, mixed $value, Closure $fail): void {
            if (filled($value) && ($error = GeoJson::validate($value, $this->geometryType, $this->maxVertices))) {
                $fail($error);
            }
        });
    }

    // Kept so existing calls like GeometryPicker::toGeoJson($record->geometry) still work.
    public static function toGeoJson(mixed $value): ?array
    {
        return GeoJson::from($value);
    }

    public static function toGeometry(mixed $state): ?Geometry
    {
        return GeoJson::toGeometry($state);
    }
}