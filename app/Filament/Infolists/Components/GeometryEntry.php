<?php

namespace App\Filament\Infolists\Components;

use App\Filament\Forms\Components\GeometryPicker;
use Filament\Infolists\Components\Entry;

class GeometryEntry extends Entry
{
    protected string $view = 'filament.infolists.components.geometry-entry';

    protected array $center = [25.5779, 91.8837]; // [lat, lng], fallback only
    protected int $zoom = 14;
    protected int $height = 350;

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

    // Reuses the picker's converter: Magellan geometry (or array/string) -> GeoJSON array
    public function getGeoJson(): ?array
    {
        return GeometryPicker::toGeoJson($this->getState());
    }
}