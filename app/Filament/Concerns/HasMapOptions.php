<?php

namespace App\Filament\Concerns;

trait HasMapOptions
{
    protected ?array $mapCenter = null;
    protected ?int $mapZoom = null;
    protected ?int $mapHeight = null;

    public function center(float $lat, float $lng): static
    {
        $this->mapCenter = [$lat, $lng];

        return $this;
    }

    public function zoom(int $zoom): static
    {
        $this->mapZoom = $zoom;

        return $this;
    }

    public function height(int $px): static
    {
        $this->mapHeight = $px;

        return $this;
    }

    /** Classes using this trait can override this to change their default. */
    protected function defaultMapHeight(): int
    {
        return 400;
    }

    public function getHeight(): int
    {
        return $this->mapHeight ?? $this->defaultMapHeight();
    }

    /** Everything the JS component needs, with config fallbacks. */
    public function getMapOptions(): array
    {
        return [
            'center' => $this->mapCenter ?? [
                config('maps.center.latitude'),
                config('maps.center.longitude'),
            ],
            'zoom' => $this->mapZoom ?? config('maps.zoom'),
            'tiles' => [
                'url' => config('maps.tiles.url'),
                'attribution' => config('maps.tiles.attribution'),
                'maxZoom' => config('maps.tiles.max_zoom'),
            ],
        ];
    }
}