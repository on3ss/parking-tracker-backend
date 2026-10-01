<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ParkingSource: string implements HasColor, HasIcon, HasLabel
{
    case USER = 'USER';
    case OPERATOR = 'OPERATOR';
    case SENSOR = 'SENSOR';
    case CAMERA = 'CAMERA';
    case SYSTEM = 'SYSTEM';

    public function getLabel(): string
    {
        return match ($this) {
            self::USER => __('User'),
            self::OPERATOR => __('Operator'),
            self::SENSOR => __('Sensor'),
            self::CAMERA => __('Camera'),
            self::SYSTEM => __('System'),
        };
    }

    public function getColor(): string|array
    {
        return match ($this) {
            self::USER => Color::Blue,
            self::OPERATOR => Color::Green,
            self::SENSOR => Color::Purple,
            self::CAMERA => Color::Orange,
            self::SYSTEM => Color::Gray,
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::USER => 'heroicon-m-user',
            self::OPERATOR => 'heroicon-m-building-office',
            self::SENSOR => 'heroicon-m-cpu-chip',
            self::CAMERA => 'heroicon-m-video-camera',
            self::SYSTEM => 'heroicon-m-cog-6-tooth',
        };
    }

    /**
     * Trust ranking. Higher wins when two fresh observations compete
     * for the same parking lot. Gaps leave room for future sources.
     */
    public function trustLevel(): int
    {
        return match ($this) {
            self::SYSTEM => 100,
            self::OPERATOR => 80,
            self::SENSOR => 60,
            self::CAMERA => 50,
            self::USER => 20,
        };
    }

    /**
     * How long a fresh observation from this source stays authoritative
     * before any lower-trust source is allowed to supersede it.
     */
    public function freshnessTtlMinutes(): int
    {
        return match ($this) {
            self::SYSTEM => 1,
            self::SENSOR => 2,
            self::CAMERA => 5,
            self::OPERATOR => 30,
            self::USER => 15,
        };
    }
}
