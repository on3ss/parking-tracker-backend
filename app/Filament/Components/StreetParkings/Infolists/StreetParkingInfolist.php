<?php

namespace App\Filament\Components\StreetParkings\Infolists;

use App\Filament\Infolists\Components\GeometryEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;

final class StreetParkingInfolist
{
    public static function information(): Section
    {
        return Section::make(__('Street Parking Information'))
            ->schema([
                TextEntry::make('name')
                    ->label(__('Name')),

                TextEntry::make('slug')
                    ->label(__('Slug')),

                TextEntry::make('provider.name')
                    ->label(__('Provider')),

                TextEntry::make('road_name')
                    ->label(__('Road name')),

                TextEntry::make('side')
                    ->label(__('Side')),

                TextEntry::make('parking_type')
                    ->label(__('Parking type')),

                TextEntry::make('status')
                    ->label(__('Status')),

                TextEntry::make('capacity')
                    ->label(__('Capacity')),
            ])
            ->columns(2);
    }

    public static function location(): Section
    {
        return Section::make(__('Location'))
            ->schema([
                TextEntry::make('location.address_line1')
                    ->label(__('Address')),

                TextEntry::make('location.address_line2')
                    ->label(__('Address line 2')),

                TextEntry::make('location.locality')
                    ->label(__('Locality')),

                TextEntry::make('location.administrative_area')
                    ->label(__('Administrative area')),

                TextEntry::make('location.postal_code')
                    ->label(__('Postal code')),

                TextEntry::make('location.country_code')
                    ->label(__('Country')),
            ])
            ->columns(2);
    }

    public static function geometry(): Section
    {
        return Section::make(__('Street Geometry'))
            ->schema([
                GeometryEntry::make('geometry')
                    ->label(__('Street segment'))
                    ->height(350)
                    ->columnSpanFull(),
            ])
            ->columnSpanFull();
    }

    public static function availability(): Section
    {
        return Section::make(__('Availability'))
            ->schema([
                TextEntry::make('availability_status')
                    ->label(__('Status')),

                TextEntry::make('available_spaces')
                    ->label(__('Available spaces')),

                TextEntry::make('availability_updated_at')
                    ->label(__('Last updated'))
                    ->dateTime(),
            ])
            ->columns(3);
    }

    public static function description(): Section
    {
        return Section::make(__('Description'))
            ->schema([
                TextEntry::make('description')
                    ->label(__('Description'))
                    ->html()
                    ->columnSpanFull(),
            ])
            ->columnSpanFull();
    }

    public static function recordInformation(): Section
    {
        return Section::make(__('Record Information'))
            ->schema([
                TextEntry::make('id')
                    ->label(__('ID')),

                TextEntry::make('created_at')
                    ->label(__('Created'))
                    ->dateTime(),

                TextEntry::make('updated_at')
                    ->label(__('Updated'))
                    ->dateTime(),

                TextEntry::make('deleted_at')
                    ->label(__('Deleted'))
                    ->dateTime(),
            ])
            ->columns(3);
    }
}
