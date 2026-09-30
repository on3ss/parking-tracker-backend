<?php

namespace App\Filament\Provider\Widgets;

use App\Enums\AvailabilityStatus;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProviderAvailabilityOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $provider = Filament::getTenant();

        if ($provider === null) {
            return [];
        }

        $facilityQuery = $provider->parkingFacilities();
        $streetQuery = $provider->streetParkings();

        $counts = [];

        foreach (AvailabilityStatus::cases() as $status) {
            $counts[$status->value] =
                (clone $facilityQuery)
                    ->where('availability_status', $status->value)
                    ->count()
                +
                (clone $streetQuery)
                    ->where('availability_status', $status->value)
                    ->count();
        }

        return [
            Stat::make(
                __('Available'),
                number_format(
                    $counts[AvailabilityStatus::AVAILABLE->value] ?? 0,
                ),
            )
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            Stat::make(
                __('Limited'),
                number_format(
                    $counts[AvailabilityStatus::LIMITED->value] ?? 0,
                ),
            )
                ->color('warning')
                ->icon('heroicon-o-exclamation-triangle'),

            Stat::make(
                __('Full'),
                number_format(
                    $counts[AvailabilityStatus::FULL->value] ?? 0,
                ),
            )
                ->color('danger')
                ->icon('heroicon-o-x-circle'),

            Stat::make(
                __('Unknown'),
                number_format(
                    $counts[AvailabilityStatus::UNKNOWN->value] ?? 0,
                ),
            )
                ->color('gray')
                ->icon('heroicon-o-question-mark-circle'),
        ];
    }
}
