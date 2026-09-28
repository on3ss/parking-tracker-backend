<?php

namespace App\Filament\Widgets;

use App\Enums\ParkingStatus;
use App\Models\ParkingFacility;
use App\Models\ParkingProvider;
use App\Models\StreetParking;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ParkingOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Parking Inventory';

    protected ?string $description =
        'Registered parking locations and providers.';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $providers = ParkingProvider::query()->count();

        $activeProviders = ParkingProvider::query()
            ->where('is_active', true)
            ->count();

        $facilities = ParkingFacility::query()->count();

        $activeFacilities = ParkingFacility::query()
            ->where('status', ParkingStatus::ACTIVE)
            ->count();

        $streetParking = StreetParking::query()->count();

        $activeStreetParking = StreetParking::query()
            ->where('status', ParkingStatus::ACTIVE)
            ->count();

        return [
            Stat::make(__('Providers'), $providers)
                ->description(
                    __(':count active', [
                        'count' => $activeProviders,
                    ]),
                )
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary'),

            Stat::make(__('Facilities'), $facilities)
                ->description(
                    __(':count active', [
                        'count' => $activeFacilities,
                    ]),
                )
                ->descriptionIcon('heroicon-m-building-office')
                ->color('primary'),

            Stat::make(__('Street Parking'), $streetParking)
                ->description(
                    __(':count active', [
                        'count' => $activeStreetParking,
                    ]),
                )
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('primary'),
        ];
    }
}