<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AvailabilityOverview;
use App\Filament\Widgets\ParkingAttention;
use App\Filament\Widgets\ParkingOverview;
use App\Filament\Widgets\RecentOccupancyReports;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            ParkingOverview::class,
            AvailabilityOverview::class,
            ParkingAttention::class,
            RecentOccupancyReports::class,
        ];
    }
}