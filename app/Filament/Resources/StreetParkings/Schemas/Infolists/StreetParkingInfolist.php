<?php

namespace App\Filament\Resources\StreetParkings\Schemas\Infolists;

use App\Filament\Schemas\Shared\Infolists\DescriptionSection;
use App\Filament\Schemas\Shared\Infolists\GeometrySection;
use App\Filament\Schemas\Shared\Infolists\LocationSection;
use App\Filament\Schemas\Shared\Infolists\RecordInformationSection;
use App\Filament\Support\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;

final class StreetParkingInfolist
{
    public static function information(): Section
    {
        return Section::make(__('Street Parking Information'))
            ->columnSpanFull()
            ->columns(Grid::THREE)
            ->schema([
                TextEntry::make('name')
                    ->label(__('Name'))
                    ->weight('medium')
                    ->columnSpan(2),

                TextEntry::make('status')
                    ->label(__('Status'))
                    ->badge(),

                TextEntry::make('slug')
                    ->label(__('Slug'))
                    ->fontFamily('mono'),

                TextEntry::make('provider.name')
                    ->label(__('Provider'))
                    ->columnSpan(2),

                TextEntry::make('road_name')
                    ->label(__('Road name'))
                    ->columnSpan(2),

                TextEntry::make('side')
                    ->label(__('Side'))
                    ->badge(),

                TextEntry::make('parking_type')
                    ->label(__('Parking type'))
                    ->badge()
                    ->columnSpan(2),

                TextEntry::make('capacity')
                    ->label(__('Capacity'))
                    ->numeric(),
            ]);
    }

    public static function location(): Section
    {
        return LocationSection::make('location');
    }

    public static function geometry(): Section
    {
        return GeometrySection::make(
            name: 'geometry',
            label: __('Street segment'),
        );
    }

    public static function availability(): Section
    {
        return Section::make(__('Availability'))
            ->columnSpanFull()
            ->columns(Grid::THREE)
            ->schema([
                TextEntry::make('availability_status')
                    ->label(__('Status'))
                    ->badge()
                    ->columnSpan(2),

                TextEntry::make('available_spaces')
                    ->label(__('Available spaces'))
                    ->numeric(),

                TextEntry::make('availability_updated_at')
                    ->label(__('Last updated'))
                    ->dateTime()
                    ->columnSpan(3),
            ]);
    }

    public static function description(): Section
    {
        return DescriptionSection::make();
    }

    public static function recordInformation(): Section
    {
        return RecordInformationSection::make();
    }
}
