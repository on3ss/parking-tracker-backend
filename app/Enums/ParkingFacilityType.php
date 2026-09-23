<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Colors\Color;

enum ParkingFacilityType: string implements HasColor, HasIcon, HasLabel
{
    case PUBLIC = 'PUBLIC';
    case PRIVATE = 'PRIVATE';
    case COMMERCIAL = 'COMMERCIAL';
    case HOTEL = 'HOTEL';
    case HOSPITAL = 'HOSPITAL';
    case MALL = 'MALL';
    case OTHER = 'OTHER';

    public function getLabel(): string
    {
        return match ($this) {
            self::PUBLIC => __('Public'),
            self::PRIVATE => __('Private'),
            self::COMMERCIAL => __('Commercial'),
            self::HOTEL => __('Hotel'),
            self::HOSPITAL => __('Hospital'),
            self::MALL => __('Mall'),
            self::OTHER => __('Other'),
        };
    }

    public function getColor(): string|array
    {
        return match ($this) {
            self::PUBLIC => Color::Blue,
            self::PRIVATE => Color::Gray,
            self::COMMERCIAL => Color::Purple,
            self::HOTEL => Color::Amber,
            self::HOSPITAL => Color::Red,
            self::MALL => Color::Pink,
            self::OTHER => Color::Slate,
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PUBLIC => 'heroicon-m-building-office-2',
            self::PRIVATE => 'heroicon-m-lock-closed',
            self::COMMERCIAL => 'heroicon-m-building-storefront',
            self::HOTEL => 'heroicon-m-building-office',
            self::HOSPITAL => 'heroicon-m-heart',
            self::MALL => 'heroicon-m-shopping-bag',
            self::OTHER => 'heroicon-m-ellipsis-horizontal-circle',
        };
    }
}