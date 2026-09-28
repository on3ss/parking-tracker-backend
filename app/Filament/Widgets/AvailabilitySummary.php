<?php

namespace App\Filament\Widgets;

use App\Enums\AvailabilityStatus;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AvailabilitySummary extends StatsOverviewWidget
{
    protected ?string $heading = 'Availability';

    protected ?string $description =
        'Current availability across active parking locations.';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        return [
            $this->stat(
                AvailabilityStatus::AVAILABLE,
                'success',
                'heroicon-m-check-circle',
            ),

            $this->stat(
                AvailabilityStatus::LIMITED,
                'warning',
                'heroicon-m-exclamation-triangle',
            ),

            $this->stat(
                AvailabilityStatus::FULL,
                'danger',
                'heroicon-m-x-circle',
            ),

            $this->stat(
                AvailabilityStatus::UNKNOWN,
                'gray',
                'heroicon-m-question-mark-circle',
            ),

            Stat::make(
                __('Stale'),
                $this->staleCount(),
            )
                ->description(__('No update within the last hour'))
                ->descriptionIcon('heroicon-m-clock')
                ->color(
                    $this->staleCount() > 0
                    ? 'warning'
                    : 'success',
                ),
        ];
    }

    private function stat(
        AvailabilityStatus $status,
        string $color,
        string $icon,
    ): Stat {
        return Stat::make(
            $status->getLabel(),
            $this->count($status),
        )
            ->description($this->description($status))
            ->descriptionIcon($icon)
            ->color($color);
    }

    private function count(AvailabilityStatus $status): int
    {
        return ParkingFacility::query()
            ->where('status', 'ACTIVE')
            ->where('availability_status', $status)
            ->count()
            +
            StreetParking::query()
                ->where('status', 'ACTIVE')
                ->where('availability_status', $status)
                ->count();
    }

    private function description(
        AvailabilityStatus $status,
    ): string {
        return match ($status) {
            AvailabilityStatus::AVAILABLE =>
                __('Spaces currently available'),

            AvailabilityStatus::LIMITED =>
                __('Low availability'),

            AvailabilityStatus::FULL =>
                __('No spaces available'),

            AvailabilityStatus::UNKNOWN =>
                __('Availability is not known'),
        };
    }

    private function staleCount(): int
    {
        $cutoff = now()->subHour();

        return ParkingFacility::query()
            ->where('status', 'ACTIVE')
            ->where(function ($query) use ($cutoff) {
                $query
                    ->whereNull('availability_updated_at')
                    ->orWhere(
                        'availability_updated_at',
                        '<',
                        $cutoff,
                    );
            })
            ->count()
            +
            StreetParking::query()
                ->where('status', 'ACTIVE')
                ->where(function ($query) use ($cutoff) {
                    $query
                        ->whereNull('availability_updated_at')
                        ->orWhere(
                            'availability_updated_at',
                            '<',
                            $cutoff,
                        );
                })
                ->count();
    }
}