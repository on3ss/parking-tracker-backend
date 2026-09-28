<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\AvailabilityStatus;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ParkingAttention extends StatsOverviewWidget
{
    protected ?string $heading = 'Needs Attention';

    protected ?string $description =
        'Parking locations where availability may need review.';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $stale = $this->staleCount();

        $unknown = $this->unknownCount();

        return [
            Stat::make(__('Stale Availability'), $stale)
                ->description(
                    __('Not updated in the last hour'),
                )
                ->descriptionIcon('heroicon-m-clock')
                ->color($stale > 0 ? 'warning' : 'success'),

            Stat::make(__('Unknown Availability'), $unknown)
                ->description(
                    __('No usable availability state'),
                )
                ->descriptionIcon('heroicon-m-question-mark-circle')
                ->color($unknown > 0 ? 'danger' : 'success'),
        ];
    }

    private function staleCount(): int
    {
        $cutoff = now()->subHour();

        return ParkingFacility::query()
            ->whereNotNull('availability_updated_at')
            ->where('availability_updated_at', '<', $cutoff)
            ->count()
            +
            StreetParking::query()
                ->whereNotNull('availability_updated_at')
                ->where('availability_updated_at', '<', $cutoff)
                ->count();
    }

    private function unknownCount(): int
    {
        return ParkingFacility::query()
            ->where('availability_status', AvailabilityStatus::UNKNOWN)
            ->count()
            +
            StreetParking::query()
                ->where('availability_status', AvailabilityStatus::UNKNOWN)
                ->count();
    }
}
