<?php

namespace App\Filament\Components\ParkingFacilities\Tables;

use App\Enums\AvailabilityStatus;
use App\Enums\ParkingFacilityType;
use App\Enums\ParkingStatus;
use Filament\Tables\Filters\SelectFilter;

final class ParkingFacilityFilters
{
    public static function provider(): SelectFilter
    {
        return SelectFilter::make('provider')
            ->label(__('Provider'))
            ->relationship('provider', 'name')
            ->searchable()
            ->preload();
    }

    public static function type(): SelectFilter
    {
        return SelectFilter::make('type')
            ->label(__('Type'))
            ->options(ParkingFacilityType::class)
            ->multiple();
    }

    public static function status(): SelectFilter
    {
        return SelectFilter::make('status')
            ->label(__('Status'))
            ->options(ParkingStatus::class)
            ->multiple();
    }

    public static function availability(): SelectFilter
    {
        return SelectFilter::make('availability_status')
            ->label(__('Availability'))
            ->options(AvailabilityStatus::class)
            ->multiple();
    }
}
