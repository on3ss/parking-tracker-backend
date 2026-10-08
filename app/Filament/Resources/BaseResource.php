<?php

namespace App\Filament\Resources;

use Filament\Facades\Filament;
use Filament\Resources\Resource;

abstract class BaseResource extends Resource
{
    protected static function hasTenant(): bool
    {
        return Filament::getTenant() !== null;
    }

    protected static function isGlobalContext(): bool
    {
        return !static::hasTenant();
    }
}