<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum StreetParkingType: string implements HasColor, HasIcon, HasLabel
{
    case CURBSIDE = 'CURBSIDE';
    case PARALLEL = 'PARALLEL';
    case ANGLED = 'ANGLED';
    case PERPENDICULAR = 'PERPENDICULAR';
    case OTHER = 'OTHER';

    public function getLabel(): string
    {
        return match ($this) {
            self::CURBSIDE => __('Curbside'),
            self::PARALLEL => __('Parallel'),
            self::ANGLED => __('Angled'),
            self::PERPENDICULAR => __('Perpendicular'),
            self::OTHER => __('Other'),
        };
    }

    public function getColor(): string|array
    {
        return match ($this) {
            self::CURBSIDE => Color::Blue,
            self::PARALLEL => Color::Green,
            self::ANGLED => Color::Orange,
            self::PERPENDICULAR => Color::Purple,
            self::OTHER => Color::Gray,
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::CURBSIDE => 'heroicon-o-minus',
            self::PARALLEL => 'heroicon-o-bars-3',
            self::ANGLED => 'heroicon-o-chevron-up',
            self::PERPENDICULAR => 'heroicon-o-plus',
            self::OTHER => 'heroicon-o-ellipsis-horizontal',
        };
    }
}
