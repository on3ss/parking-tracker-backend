<?php

namespace App\Filament\Resources\ParkingFacilities\Schemas;

use App\Models\ParkingFacility;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ParkingFacilityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Facility Information'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('Name')),

                        TextEntry::make('slug')
                            ->label(__('Slug')),

                        TextEntry::make('provider.name')
                            ->label(__('Provider'))
                            ->placeholder('-'),

                        TextEntry::make('type')
                            ->label(__('Type'))
                            ->badge(),

                        TextEntry::make('status')
                            ->label(__('Status'))
                            ->badge(),

                        TextEntry::make('capacity')
                            ->label(__('Capacity'))
                            ->numeric()
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                Section::make(__('Location'))
                    ->schema([
                        TextEntry::make('location.address_line1')
                            ->label(__('Address'))
                            ->placeholder('-'),

                        TextEntry::make('location.locality')
                            ->label(__('Locality'))
                            ->placeholder('-'),

                        TextEntry::make('location.administrative_area')
                            ->label(__('Administrative area'))
                            ->placeholder('-'),

                        TextEntry::make('location.postal_code')
                            ->label(__('Postal code'))
                            ->placeholder('-'),

                        TextEntry::make('location.country_code')
                            ->label(__('Country'))
                            ->placeholder('-'),

                        TextEntry::make('location.coordinates')
                            ->label(__('Coordinates'))
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                Section::make(__('Operating Hours'))
                    ->schema([
                        TextEntry::make('opening_time')
                            ->label(__('Opening time'))
                            ->time()
                            ->placeholder('-'),

                        TextEntry::make('closing_time')
                            ->label(__('Closing time'))
                            ->time()
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                Section::make(__('Availability'))
                    ->schema([
                        TextEntry::make('available_spaces')
                            ->label(__('Available spaces'))
                            ->numeric()
                            ->placeholder('-'),

                        TextEntry::make('availability_status')
                            ->label(__('Status'))
                            ->badge(),

                        TextEntry::make('availability_updated_at')
                            ->label(__('Last updated'))
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(3),

                Section::make(__('Description'))
                    ->schema([
                        TextEntry::make('description')
                            ->label(__('Description'))
                            ->html()
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make(__('Record Information'))
                    ->schema([
                        TextEntry::make('created_at')
                            ->label(__('Created'))
                            ->dateTime()
                            ->placeholder('-'),

                        TextEntry::make('updated_at')
                            ->label(__('Updated'))
                            ->dateTime()
                            ->placeholder('-'),

                        TextEntry::make('deleted_at')
                            ->label(__('Deleted'))
                            ->dateTime()
                            ->visible(
                                fn(ParkingFacility $record): bool => $record->trashed(),
                            ),
                    ])
                    ->columns(3)
                    ->collapsible(),
            ]);
    }
}