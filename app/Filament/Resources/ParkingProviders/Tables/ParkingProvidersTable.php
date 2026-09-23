<?php

namespace App\Filament\Resources\ParkingProviders\Tables;

use App\Enums\ParkingProviderType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ParkingProvidersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')

            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('type')
                    ->label(__('Type'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('parking_facilities_count')
                    ->label(__('Facilities'))
                    ->counts('parkingFacilities')
                    ->sortable(),

                TextColumn::make('street_parkings_count')
                    ->label(__('Street Parking'))
                    ->counts('streetParkings')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label(__('Slug'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('Updated'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->label(__('Deleted'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                SelectFilter::make('type')
                    ->label(__('Type'))
                    ->options(ParkingProviderType::class)
                    ->multiple(),

                SelectFilter::make('is_active')
                    ->label(__('Status'))
                    ->options([
                        '1' => __('Active'),
                        '0' => __('Inactive'),
                    ]),

                TrashedFilter::make(),
            ])

            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
