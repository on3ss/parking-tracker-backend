<?php

namespace App\Filament\Resources;

use Filament\Facades\Filament;
use Filament\Resources\Resource;

abstract class BaseResource extends Resource
{
    public static function hasTenant(): bool
    {
        return Filament::getTenant() !== null;
    }

    public static function isGlobalContext(): bool
    {
        return ! static::hasTenant();
    }
}
