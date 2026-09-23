<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Colors\Color;

enum AvailabilityStatus: string implements HasColor, HasIcon, HasLabel
{
    case UNKNOWN = 'UNKNOWN';
    case AVAILABLE = 'AVAILABLE';
    case LIMITED = 'LIMITED';
    case FULL = 'FULL';

    public function getLabel(): string
    {
        return match ($this) {
            self::UNKNOWN => __('Unknown'),
            self::AVAILABLE => __('Available'),
            self::LIMITED => __('Limited'),
            self::FULL => __('Full'),
        };
    }

    public function getColor(): string|array
    {
        return match ($this) {
            self::UNKNOWN => Color::Gray,
            self::AVAILABLE => Color::Green,
            self::LIMITED => Color::Yellow,
            self::FULL => Color::Red,
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::UNKNOWN => 'heroicon-m-question-mark-circle',
            self::AVAILABLE => 'heroicon-m-check-circle',
            self::LIMITED => 'heroicon-m-exclamation-triangle',
            self::FULL => 'heroicon-m-x-circle',
        };
    }
}