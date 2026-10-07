<?php

namespace App\Filament\Resources\StreetParkings\Schemas\Tables;

use Filament\Tables\Columns\TextColumn;

final class StreetParkingColumns
{
    public static function name(): TextColumn
    {
        return TextColumn::make('name')
            ->label(__('Name'))
            ->searchable()
            ->sortable();
    }

    public static function provider(): TextColumn
    {
        return TextColumn::make('provider.name')
            ->label(__('Provider'))
            ->searchable()
            ->sortable();
    }

    public static function roadName(): TextColumn
    {
        return TextColumn::make('road_name')
            ->label(__('Road'))
            ->searchable()
            ->sortable();
    }

    public static function side(): TextColumn
    {
        return TextColumn::make('side')
            ->label(__('Side'))
            ->sortable();
    }

    public static function parkingType(): TextColumn
    {
        return TextColumn::make('parking_type')
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
        ];
    }

    public static function locality(): TextColumn
    {
        return TextColumn::make('location.locality')
            ->label(__('Locality'))
            ->searchable()
            ->sortable();
    }

    public static function slug(): TextColumn
    {
        return TextColumn::make('slug')
            ->label(__('Slug'))
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: true);
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
        ];
    }
}
