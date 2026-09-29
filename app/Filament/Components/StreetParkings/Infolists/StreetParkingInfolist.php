<?php

namespace App\Filament\Components\StreetParkings\Infolists;

use App\Filament\Infolists\Components\GeometryEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;

final class StreetParkingInfolist
{
    /**
     * Shared responsive grid definition for the sections below.
     *
     * default : 1 column  (mobile, < 640px)
     * sm      : 2 columns (small tablets, >= 640px)
     * lg      : 4 columns (desktop, >= 1024px)
     */
    private const GRID = [
        'default' => 1,
        'sm' => 2,
        'lg' => 4,
    ];

    public static function information(): Section
    {
        return Section::make(__('Street Parking Information'))
            ->columnSpanFull()
            ->columns([
                'default' => 1,
                'sm' => 2,
                'lg' => 3,
            ])
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
                    ->fontFamily('mono')
                    ->columnSpan(2),

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
        return Section::make(__('Location'))
            ->columnSpanFull()
            ->columns(self::GRID)
            ->schema([
                TextEntry::make('location.address_line1')
                    ->label(__('Address'))
                    ->columnSpanFull(),

                TextEntry::make('location.address_line2')
                    ->label(__('Address line 2'))
                    ->columnSpanFull(),

                TextEntry::make('location.locality')
                    ->label(__('Locality'))
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 1,
                        'lg' => 2,
                    ]),

                TextEntry::make('location.administrative_area')
                    ->label(__('Administrative area'))
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 1,
                        'lg' => 2,
                    ]),

                TextEntry::make('location.postal_code')
                    ->label(__('Postal code'))
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 1,
                        'lg' => 2,
                    ]),

                TextEntry::make('location.country_code')
                    ->label(__('Country'))
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 1,
                        'lg' => 2,
                    ]),

                GeometryEntry::make('location.coordinates')
                    ->label(__('Map'))
                    ->zoom(16)
                    ->height(260)
                    ->hidden(fn(?Model $record): bool => blank($record?->location?->coordinates))
                    ->columnSpanFull(),
            ]);
    }

    public static function geometry(): Section
    {
        return Section::make(__('Street Geometry'))
            ->columnSpanFull()
            ->schema([
                GeometryEntry::make('geometry')
                    ->label(__('Street segment'))
                    ->columnSpanFull(),
            ]);
    }

    public static function availability(): Section
    {
        return Section::make(__('Availability'))
            ->columnSpanFull()
            ->columns(self::GRID)
            ->schema([
                TextEntry::make('availability_status')
                    ->label(__('Status'))
                    ->badge()
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 1,
                        'lg' => 2,
                    ]),

                TextEntry::make('available_spaces')
                    ->label(__('Available spaces'))
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 1,
                        'lg' => 1,
                    ]),

                TextEntry::make('availability_updated_at')
                    ->label(__('Last updated'))
                    ->dateTime()
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 1,
                    ]),
            ]);
    }

    public static function description(): Section
    {
        return Section::make(__('Description'))
            ->columnSpanFull()
            ->schema([
                TextEntry::make('description')
                    ->label(__('Description'))
                    ->html()
                    ->columnSpanFull(),
            ]);
    }

    public static function recordInformation(): Section
    {
        return Section::make(__('Record Information'))
            ->columnSpanFull()
            ->columns(self::GRID)
            ->schema([
                TextEntry::make('id')
                    ->label(__('ID'))
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 1,
                        'lg' => 1,
                    ]),

                TextEntry::make('created_at')
                    ->label(__('Created'))
                    ->dateTime()
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 1,
                        'lg' => 1,
                    ]),

                TextEntry::make('updated_at')
                    ->label(__('Updated'))
                    ->dateTime()
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 1,
                        'lg' => 1,
                    ]),

                TextEntry::make('deleted_at')
                    ->label(__('Deleted'))
                    ->dateTime()
                    ->placeholder('—')
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 1,
                        'lg' => 1,
                    ]),
            ]);
    }
}