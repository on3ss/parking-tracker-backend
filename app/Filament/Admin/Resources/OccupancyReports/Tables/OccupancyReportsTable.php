<?php

namespace App\Filament\Admin\Resources\OccupancyReports\Tables;

use App\Enums\ParkingSource;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OccupancyReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('reported_at', 'desc')

            ->columns([
                TextColumn::make('parking_name')
                    ->label(__('Parking'))
                    ->state(
                        fn($record): string =>
                            $record->parkingFacility?->name
                            ?? $record->streetParking?->name
                            ?? '—',
                    )
                    ->description(
                        fn($record): string =>
                            $record->parkingFacility
                            ? __('Facility')
                            : __('Street Parking'),
                    )
                    ->searchable(
                        query: function (Builder $query, string $search, ): Builder {
                            return $query->where(function (Builder $query) use ($search) {
                                $query
                                    ->whereHas(
                                        'parkingFacility',
                                        fn(Builder $query) =>
                                            $query->where('name', 'ilike', "%{$search}%"),
                                    )
                                    ->orWhereHas(
                                        'streetParking',
                                        fn(Builder $query) =>
                                            $query->where('name', 'ilike', "%{$search}%"),
                                    );
                            });
                        },
                    )
                    ->weight('medium'),

                TextColumn::make('available_spaces')
                    ->label(__('Available'))
                    ->numeric()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('occupied_spaces')
                    ->label(__('Occupied'))
                    ->numeric()
                    ->sortable(),

                TextColumn::make('source')
                    ->label(__('Source'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('confidence')
                    ->label(__('Confidence'))
                    ->formatStateUsing(
                        fn($state): string => $state === null
                            ? '—'
                            : number_format((float) $state * 100, 1) . '%',
                    )
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label(__('Reported By'))
                    ->placeholder(__('System'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('reported_at')
                    ->label(__('Reported'))
                    ->dateTime()
                    ->sortable()
                    ->description(
                        fn($record): string =>
                            $record->reported_at?->diffForHumans() ?? '—',
                    ),

                TextColumn::make('id')
                    ->label(__('ID'))
                    ->fontFamily('mono')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                SelectFilter::make('source')
                    ->label(__('Source'))
                    ->options(ParkingSource::class)
                    ->multiple(),

                SelectFilter::make('parking_facility_id')
                    ->label(__('Facility'))
                    ->relationship('parkingFacility', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('street_parking_id')
                    ->label(__('Street Parking'))
                    ->relationship('streetParking', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('has_user')
                    ->label(__('User Reported'))
                    ->query(
                        fn(Builder $query): Builder =>
                            $query->whereNotNull('user_id'),
                    ),

                Filter::make('system_report')
                    ->label(__('System Report'))
                    ->query(
                        fn(Builder $query): Builder =>
                            $query->whereNull('user_id'),
                    ),
            ])

            ->recordActions([
                ViewAction::make(),
            ]);
    }
}