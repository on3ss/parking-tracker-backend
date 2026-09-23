<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ParkingProviderType: string implements HasColor, HasIcon, HasLabel
{
    case MUNICIPAL = 'MUNICIPAL';
    case PRIVATE = 'PRIVATE';
    case COMMERCIAL = 'COMMERCIAL';
    case HOTEL = 'HOTEL';
    case HOSPITAL = 'HOSPITAL';
    case MALL = 'MALL';
    case OTHER = 'OTHER';

    public function getLabel(): string
    {
        return match ($this) {
            self::MUNICIPAL => __('Municipal'),
            self::PRIVATE => __('Private'),
            self::COMMERCIAL => __('Commercial'),
            self::HOTEL => __('Hotel'),
            self::HOSPITAL => __('Hospital'),
            self::MALL => __('Mall'),
            self::OTHER => __('Other'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::MUNICIPAL => 'info',
            self::PRIVATE => 'gray',
            self::COMMERCIAL => 'primary',
            self::HOTEL => 'warning',
            self::HOSPITAL => 'danger',
            self::MALL => 'success',
            self::OTHER => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::MUNICIPAL => 'heroicon-o-building-library',
            self::PRIVATE => 'heroicon-o-user',
            self::COMMERCIAL => 'heroicon-o-briefcase',
            self::HOTEL => 'heroicon-o-building-office-2',
            self::HOSPITAL => 'heroicon-o-plus-circle',
            self::MALL => 'heroicon-o-shopping-bag',
            self::OTHER => 'heroicon-o-ellipsis-horizontal-circle',
        };
    }
}
