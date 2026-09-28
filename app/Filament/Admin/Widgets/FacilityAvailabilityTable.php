<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\AvailabilityStatus;
use App\Enums\ParkingStatus;
use App\Models\ParkingFacility;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class FacilityAvailabilityTable extends TableWidget
{
    protected static ?string $heading = 'Parking Facilities';

    protected static ?string $description =
        'Current availability for facility-based parking.';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ParkingFacility::query()
                    ->with('provider')
                    ->where('status', ParkingStatus::ACTIVE),
            )
            ->defaultSort('availability_updated_at', 'asc')
            ->columns([
                TextColumn::make('name')
                    ->label(__('Parking'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('provider.name')
                    ->label(__('Provider'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('capacity')
                    ->label(__('Capacity'))
                    ->numeric()
                    ->sortable(),

                TextColumn::make('available_spaces')
                    ->label(__('Available'))
                    ->numeric()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('availability_status')
                    ->label(__('Availability'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('availability_updated_at')
                    ->label(__('Last updated'))
                    ->dateTime()
                    ->since()
                    ->sortable()
                    ->color(
                        fn ($record): string => $this->isStale($record)
                            ? 'warning'
                            : 'gray',
                    ),
            ])
            ->filters([
                SelectFilter::make('availability_status')
                    ->label(__('Availability'))
                    ->options(AvailabilityStatus::class)
                    ->multiple(),

                SelectFilter::make('provider')
                    ->label(__('Provider'))
                    ->relationship('provider', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label(__('View'))
                    ->icon('heroicon-m-eye')
                    ->url(
                        fn (ParkingFacility $record): string => route(
                            'filament.admin.resources.parking-facilities.view',
                            $record,
                        ),
                    ),

                Action::make('report')
                    ->label(__('Report'))
                    ->icon('heroicon-m-arrow-path')
                    ->color('primary')
                    ->modalHeading(
                        fn (ParkingFacility $record): string => __('Report availability — :name', [
                            'name' => $record->name,
                        ]),
                    ),
            ]);
    }

    private function isStale(ParkingFacility $record): bool
    {
        return blank($record->availability_updated_at)
            || $record->availability_updated_at->isBefore(
                now()->subHour(),
            );
    }
}
