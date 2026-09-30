<?php

namespace App\Filament\Provider\Widgets;

use App\Enums\AvailabilityStatus;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class ParkingNeedingAttention extends TableWidget
{
    protected static ?string $heading = 'Parking needing attention';

    protected int|string|array $columnSpan = [
        'default' => 'full',
    ];

    public function table(Table $table): Table
    {
        return $table
            ->records(function (): array {
                $provider = Filament::getTenant();

                if ($provider === null) {
                    return [];
                }

                $facilities = ParkingFacility::query()
                    ->where(
                        'parking_provider_id',
                        $provider->getKey(),
                    )
                    ->where(function (Builder $query) {
                        $query
                            ->whereNull('capacity')
                            ->orWhere(
                                'availability_status',
                                AvailabilityStatus::UNKNOWN->value,
                            )
                            ->orWhereNull('availability_updated_at');
                    })
                    ->get()
                    ->map(fn (ParkingFacility $parking): array => [
                        'name' => $parking->name,
                        'type' => 'Facility',
                        'issue' => $this->issueFor($parking),
                        'updated_at' => $parking->availability_updated_at,
                    ]);

                $streetParking = StreetParking::query()
                    ->where(
                        'parking_provider_id',
                        $provider->getKey(),
                    )
                    ->where(function (Builder $query) {
                        $query
                            ->whereNull('capacity')
                            ->orWhere(
                                'availability_status',
                                AvailabilityStatus::UNKNOWN->value,
                            )
                            ->orWhereNull('availability_updated_at');
                    })
                    ->get()
                    ->map(fn (StreetParking $parking): array => [
                        'name' => $parking->name,
                        'type' => 'Street',
                        'issue' => $this->issueFor($parking),
                        'updated_at' => $parking->availability_updated_at,
                    ]);

                return $facilities
                    ->concat($streetParking)
                    ->take(10)
                    ->values()
                    ->all();
            })
            ->paginated(false)
            ->columns([
                TextColumn::make('name')
                    ->label(__('Parking'))
                    ->weight('medium'),

                TextColumn::make('type')
                    ->label(__('Type'))
                    ->badge(),

                TextColumn::make('issue')
                    ->label(__('Issue'))
                    ->wrap(),

                TextColumn::make('updated_at')
                    ->label(__('Updated'))
                    ->dateTime()
                    ->placeholder(__('Never')),
            ]);
    }

    private function issueFor(
        ParkingFacility|StreetParking $parking,
    ): string {
        if ($parking->capacity === null) {
            return __('Capacity not configured.');
        }

        if ($parking->availability_updated_at === null) {
            return __('Availability never reported.');
        }

        if (
            $parking->availability_status ===
            AvailabilityStatus::UNKNOWN
        ) {
            return __('Availability unknown.');
        }

        return __('Availability requires attention.');
    }
}
