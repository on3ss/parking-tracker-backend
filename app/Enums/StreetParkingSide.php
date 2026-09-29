<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum StreetParkingSide: string implements HasColor, HasIcon, HasLabel
{
    case LEFT = 'LEFT';
    case RIGHT = 'RIGHT';
    case BOTH = 'BOTH';

    public function getLabel(): string
    {
        return match ($this) {
            self::LEFT => __('Left'),
            self::RIGHT => __('Right'),
            self::BOTH => __('Both sides'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::LEFT => 'info',
            self::RIGHT => 'warning',
            self::BOTH => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::LEFT => 'heroicon-o-arrow-left',
            self::RIGHT => 'heroicon-o-arrow-right',
            self::BOTH => 'heroicon-o-arrows-right-left',
        };
    }
}