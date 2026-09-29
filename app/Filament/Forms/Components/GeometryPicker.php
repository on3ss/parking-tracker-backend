<?php

namespace App\Filament\Forms\Components;

use App\Filament\Concerns\HasMapOptions;
use App\Support\Geo\GeoJson;
use Closure;
use Filament\Forms\Components\Field;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

class GeometryPicker extends Field
{
    use HasMapOptions;

    protected string $view = 'filament.forms.components.geometry-picker';

    protected string $geometryType = 'linestring';

    protected int $maxVertices = 500;

    /**
     * @var array{0: string, 1: string}|null
     */
    protected ?array $coordinateFields = null;

    public function geometryType(string $type): static
    {
        if (!isset(GeoJson::TYPES[$type])) {
            throw new InvalidArgumentException(
                "Unsupported geometry type [{$type}].",
            );
        }

        $this->geometryType = $type;

        return $this;
    }

    public function maxVertices(int $max): static
    {
        if ($max < 1) {
            throw new InvalidArgumentException(
                'Maximum vertices must be greater than zero.',
            );
        }

        $this->maxVertices = $max;

        return $this;
    }

    /**
     * Configure this field as a point picker that edits sibling
     * latitude / longitude state instead of storing GeoJSON.
     */
    public function coordinateFields(
        string $latitude = 'latitude',
        string $longitude = 'longitude',
    ): static {
        $this->geometryType('point');

        $this->coordinateFields = [
            $latitude,
            $longitude,
        ];

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
        if ($this->coordinateFields === null) {
            throw new LogicException(
                'The geometry picker is not configured for coordinate fields.',
            );
        }

        return $this->siblingStatePath(
            $this->coordinateFields[0],
        );
    }

    public function getLongitudeStatePath(): string
    {
        if ($this->coordinateFields === null) {
            throw new LogicException(
                'The geometry picker is not configured for coordinate fields.',
            );
        }

        return $this->siblingStatePath(
            $this->coordinateFields[1],
        );
    }

    protected function siblingStatePath(string $name): string
    {
        return Str::beforeLast(
            $this->getStatePath(),
            '.',
        ) . '.' . $name;
    }

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * Model state:
         *
         * Magellan Geometry
         *
         * becomes:
         *
         * GeoJSON array
         */
        $this->afterStateHydrated(
            fn(GeometryPicker $component, mixed $state) =>
                $component->state(GeoJson::from($state)),
        );

        /*
         * Form state:
         *
         * GeoJSON array
         *
         * becomes:
         *
         * Magellan Geometry
         */
        $this->dehydrateStateUsing(
            fn(mixed $state) => GeoJson::toGeometry($state),
        );

        $this->rule(
            fn() => function (string $attribute, mixed $value, Closure $fail, ): void {
                if (blank($value)) {
                    return;
                }

                $error = GeoJson::validate(
                    $value,
                    $this->geometryType,
                    $this->maxVertices,
                );

                if ($error !== null) {
                    $fail($error);
                }
            },
        );
    }
}