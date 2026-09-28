<?php

namespace App\Filament\Widgets;

use App\Enums\AvailabilityStatus;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AvailabilityOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Current Availability';

    protected ?string $description =
        'Latest known availability across all parking locations.';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $counts = [];

        foreach (AvailabilityStatus::cases() as $status) {
            $counts[$status->value] = $this->countByStatus($status);
        }

        return [
            Stat::make(
                __('Available'),
                $counts[AvailabilityStatus::AVAILABLE->value] ?? 0,
            )
                ->description(__('Plenty of spaces available'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make(
                __('Limited'),
                $counts[AvailabilityStatus::LIMITED->value] ?? 0,
            )
                ->description(__('Low availability'))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),

            Stat::make(
                __('Full'),
                $counts[AvailabilityStatus::FULL->value] ?? 0,
            )
                ->description(__('No spaces available'))
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make(
                __('Unknown'),
                $counts[AvailabilityStatus::UNKNOWN->value] ?? 0,
            )
                ->description(__('No reliable availability'))
                ->descriptionIcon('heroicon-m-question-mark-circle')
                ->color('gray'),
        ];
    }

    private function countByStatus(
        AvailabilityStatus $status,
    ): int {
        return ParkingFacility::query()
            ->where('availability_status', $status)
            ->count()
            +
            StreetParking::query()
                ->where('availability_status', $status)
                ->count();
    }
}