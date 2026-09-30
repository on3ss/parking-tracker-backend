<?php

namespace App\Filament\Provider\Widgets;

use App\Models\OccupancyReport;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentOccupancyReports extends TableWidget
{
    protected static ?string $heading = 'Recent availability reports';

    protected int|string|array $columnSpan = [
        'default' => 'full',
    ];

    public function table(Table $table): Table
    {
        $provider = Filament::getTenant();

        $query = OccupancyReport::query()
            ->with([
                'parkingFacility',
                'streetParking',
                'user',
            ])
            ->when(
                $provider,
                function ($query) use ($provider) {
                    $query->where(function ($query) use ($provider) {
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
                    });
                },
            )
            ->latest('reported_at');

        return $table
            ->query($query)
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10])
            ->columns([
                TextColumn::make('parking_name')
                    ->label(__('Parking'))
                    ->state(
                        fn (OccupancyReport $record): string => $record->parkingFacility?->name
                            ?? $record->streetParking?->name
                            ?? '—',
                    ),

                TextColumn::make('available_spaces')
                    ->label(__('Available'))
                    ->numeric(),

                TextColumn::make('occupied_spaces')
                    ->label(__('Occupied'))
                    ->numeric(),

                TextColumn::make('source')
                    ->label(__('Source'))
                    ->badge(),

                TextColumn::make('user.name')
                    ->label(__('Reported by'))
                    ->placeholder(__('System')),

                TextColumn::make('reported_at')
                    ->label(__('Reported'))
                    ->dateTime()
                    ->since(),
            ])
            ->defaultSort('reported_at', 'desc');
    }
}
