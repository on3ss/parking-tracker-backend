<?php

namespace App\Filament\Concerns;

trait HasMapOptions
{
    protected ?array $mapCenter = null;

    protected ?int $mapZoom = null;

    protected ?int $mapHeight = null;

    public function center(float $latitude, float $longitude): static
    {
        $this->mapCenter = [
            $latitude,
            $longitude,
        ];

        return $this;
    }

    public function zoom(int $zoom): static
    {
        $this->mapZoom = $zoom;

        return $this;
    }

    public function height(int $pixels): static
    {
        $this->mapHeight = $pixels;

        return $this;
    }

    protected function defaultMapHeight(): int
    {
        return 400;
    }

    public function getHeight(): int
    {
        return $this->mapHeight
            ?? $this->defaultMapHeight();
    }

    public function getMapOptions(): array
    {
        return [
            'center' => $this->mapCenter ?? [
                config('maps.center.latitude'),
                config('maps.center.longitude'),
            ],

            'zoom' => $this->mapZoom ?? config('maps.zoom'),

            // Adapter defaults — defined here so the JS never needs
            // its own fallbacks.
            'precision' => 7,
            'fitPadding' => [30, 30],
            'fitMaxZoom' => 18,

            'tiles' => [
                'url' => config('maps.tiles.url'),
                'attribution' => config('maps.tiles.attribution'),
                'maxZoom' => config('maps.tiles.max_zoom'),
                'maxNativeZoom' => config('maps.tiles.max_native_zoom'),
            ],
        ];
    }
}
