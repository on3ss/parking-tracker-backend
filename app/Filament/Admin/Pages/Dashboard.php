<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\AvailabilityOverview;
use App\Filament\Admin\Widgets\ParkingAttention;
use App\Filament\Admin\Widgets\ParkingOverview;
use App\Filament\Admin\Widgets\RecentOccupancyReports;
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
