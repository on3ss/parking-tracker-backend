<?php

namespace App\Filament\Components\StreetParkings\Tables;

use App\Enums\AvailabilityStatus;
use App\Enums\ParkingStatus;
use App\Enums\StreetParkingType;
use Filament\Tables\Filters\SelectFilter;

final class StreetParkingFilters
{
    public static function provider(): SelectFilter
    {
        return SelectFilter::make('parking_provider_id')
            ->label(__('Provider'))
            ->relationship('provider', 'name')
            ->searchable()
            ->preload();
    }

    public static function parkingType(): SelectFilter
    {
        return SelectFilter::make('parking_type')
            ->label(__('Parking type'))
            ->options(StreetParkingType::class);
    }

    public static function status(): SelectFilter
    {
        return SelectFilter::make('status')
            ->label(__('Status'))
            ->options(ParkingStatus::class);
    }

    public static function availability(): SelectFilter
    {
        return SelectFilter::make('availability_status')
            ->label(__('Availability'))
            ->options(AvailabilityStatus::class);
    }
}