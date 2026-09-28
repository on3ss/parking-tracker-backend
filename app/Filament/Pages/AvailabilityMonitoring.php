<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AvailabilitySummary;
use App\Filament\Widgets\FacilityAvailabilityTable;
use App\Filament\Widgets\StreetParkingAvailabilityTable;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class AvailabilityMonitoring extends Page
{
    protected static ?string $navigationLabel =
        'Availability';

    protected static ?string $title =
        'Availability Monitoring';

    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-signal';

    protected static string|UnitEnum|null $navigationGroup =
        'Parking';

    protected string $view =
        'filament.pages.availability-monitoring';

    protected function getHeaderWidgets(): array
    {
        return [
            AvailabilitySummary::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            FacilityAvailabilityTable::class,
            StreetParkingAvailabilityTable::class,
        ];
    }
}