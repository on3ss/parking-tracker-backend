<?php

namespace App\Filament\Components\ParkingFacilities\Tables;

use Filament\Tables\Columns\TextColumn;

final class ParkingFacilityColumns
{
    public static function name(): TextColumn
    {
        return TextColumn::make('name')
            ->label(__('Name'))
            ->searchable()
            ->sortable()
            ->weight('medium');
    }

    public static function provider(): TextColumn
    {
        return TextColumn::make('provider.name')
            ->label(__('Provider'))
            ->searchable()
            ->sortable();
    }

    public static function type(): TextColumn
    {
        return TextColumn::make('type')
            ->label(__('Type'))
            ->badge()
            ->sortable();
    }

    public static function status(): TextColumn
    {
        return TextColumn::make('status')
            ->label(__('Status'))
            ->badge()
            ->sortable();
    }

    public static function capacity(): TextColumn
    {
        return TextColumn::make('capacity')
            ->label(__('Capacity'))
            ->numeric()
            ->sortable();
    }

    public static function availability(): array
    {
        return [
            TextColumn::make('available_spaces')
                ->label(__('Available'))
                ->numeric()
                ->sortable(),

            TextColumn::make('availability_status')
                ->label(__('Availability'))
                ->badge()
                ->sortable(),

            TextColumn::make('availability_updated_at')
                ->label(__('Availability Updated'))
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    public static function locality(): TextColumn
    {
        return TextColumn::make('location.locality')
            ->label(__('Locality'))
            ->searchable()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function slug(): TextColumn
    {
        return TextColumn::make('slug')
            ->label(__('Slug'))
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function operatingHours(): array
    {
        return [
            TextColumn::make('opening_time')
                ->label(__('Opens'))
                ->time()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('closing_time')
                ->label(__('Closes'))
                ->time()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    public static function timestamps(): array
    {
        return [
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
        ];
    }
}
