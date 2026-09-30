<?php

namespace App\Filament\Provider\Widgets;

use App\Models\OccupancyReport;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProviderStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $provider = Filament::getTenant();

        if ($provider === null) {
            return [];
        }

        $facilities = $provider->parkingFacilities();

        $streetParking = $provider->streetParkings();

        $facilityCount = (clone $facilities)->count();

        $streetParkingCount = (clone $streetParking)->count();

        $totalCapacity =
            (clone $facilities)->sum('capacity')
            + (clone $streetParking)->sum('capacity');

        $reportsToday = OccupancyReport::query()
            ->where(function ($query) use ($provider) {
                $query
                    ->whereHas(
                        'parkingFacility',
                        fn ($query) => $query->where(
                            'parking_provider_id',
                            $provider->getKey(),
                        ),
                    )
                    ->orWhereHas(
                        'streetParking',
                        fn ($query) => $query->where(
                            'parking_provider_id',
                            $provider->getKey(),
                        ),
                    );
            })
            ->where('reported_at', '>=', today())
            ->count();

        return [
            Stat::make(
                __('Parking facilities'),
                number_format($facilityCount),
            )
                ->icon('heroicon-o-building-office-2'),

            Stat::make(
                __('Street parking'),
                number_format($streetParkingCount),
            )
                ->icon('heroicon-o-map'),

            Stat::make(
                __('Total capacity'),
                number_format($totalCapacity),
            )
                ->icon('heroicon-o-square-3-stack-3d'),

            Stat::make(
                __('Reports today'),
                number_format($reportsToday),
            )
                ->icon('heroicon-o-clipboard-document-check'),
        ];
    }
}
