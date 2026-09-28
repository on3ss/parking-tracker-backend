<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum VehicleType: string implements HasColor, HasIcon, HasLabel
{
    case ALL = 'ALL';
    case CAR = 'CAR';
    case MOTORCYCLE = 'MOTORCYCLE';

    public function getLabel(): string
    {
        return match ($this) {
            self::ALL => __('All'),
            self::CAR => __('Car'),
            self::MOTORCYCLE => __('Motorcycle'),
        };
    }

    public function getColor(): string|array
    {
        return match ($this) {
            self::ALL => Color::Gray,
            self::CAR => Color::Blue,
            self::MOTORCYCLE => Color::Orange,
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::ALL => 'heroicon-o-queue-list',
            self::CAR => 'heroicon-o-truck',
            self::MOTORCYCLE => 'heroicon-o-bolt',
        };
    }
}
