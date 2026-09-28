<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\AvailabilitySummary;
use App\Filament\Admin\Widgets\FacilityAvailabilityTable;
use App\Filament\Admin\Widgets\StreetParkingAvailabilityTable;
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