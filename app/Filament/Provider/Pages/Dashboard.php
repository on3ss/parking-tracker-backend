<?php

namespace App\Filament\Provider\Pages;

use App\Filament\Provider\Widgets\ParkingNeedingAttention;
use App\Filament\Provider\Widgets\ProviderAvailabilityOverview;
use App\Filament\Provider\Widgets\ProviderStatsOverview;
use App\Filament\Provider\Widgets\RecentOccupancyReports;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            ProviderStatsOverview::class,
            ProviderAvailabilityOverview::class,
            RecentOccupancyReports::class,
            ParkingNeedingAttention::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 4,
        ];
    }
}
