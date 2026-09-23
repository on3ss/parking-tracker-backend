<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Colors\Color;

enum ParkingStatus: string implements HasColor, HasIcon, HasLabel
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case TEMPORARILY_CLOSED = 'TEMPORARILY_CLOSED';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => __('Active'),
            self::INACTIVE => __('Inactive'),
            self::TEMPORARILY_CLOSED => __('Temporarily closed'),
        };
    }

    public function getColor(): string|array
    {
        return match ($this) {
            self::ACTIVE => Color::Green,
            self::INACTIVE => Color::Gray,
            self::TEMPORARILY_CLOSED => Color::Orange,
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::ACTIVE => 'heroicon-m-check-circle',
            self::INACTIVE => 'heroicon-m-pause-circle',
            self::TEMPORARILY_CLOSED => 'heroicon-m-clock',
        };
    }
}