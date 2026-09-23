<?php

namespace App\Filament\Resources\ParkingFacilities\Schemas;

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
                            ->label(__('Slug'))
                            ->fontFamily('mono')
                            ->copyable()
                            ->copyMessage(__('Slug copied')),

                        TextEntry::make('provider.name')
                            ->label(__('Provider'))
                            ->placeholder(__('—')),

                        TextEntry::make('type')
                            ->label(__('Type'))
                            ->badge(),

                        TextEntry::make('status')
                            ->label(__('Status'))
                            ->badge(),

                        TextEntry::make('capacity')
                            ->label(__('Capacity'))
                            ->numeric()
                            ->placeholder(__('—')),
                    ])
                    ->columns(2),

                Section::make(__('Location'))
                    ->schema([
                        TextEntry::make('location.address_line1')
                            ->label(__('Address'))
                            ->placeholder(__('-')),

                        TextEntry::make('location.locality')
                            ->label(__('Locality'))
                            ->placeholder(__('-')),

                        TextEntry::make('location.administrative_area')
                            ->label(__('Administrative area'))
                            ->placeholder(__('-')),

                        TextEntry::make('location.postal_code')
                            ->label(__('Postal code'))
                            ->placeholder(__('-')),

                        TextEntry::make('location.country_code')
                            ->label(__('Country'))
                            ->placeholder(__('-')),

                        TextEntry::make('location.coordinates')
                            ->label(__('Coordinates'))
                            ->placeholder(__('-')),
                    ])
                    ->columns(2),

                Section::make(__('Operating Hours'))
                    ->schema([
                        TextEntry::make('opening_time')
                            ->label(__('Opening time'))
                            ->time()
                            ->placeholder(__('-')),

                        TextEntry::make('closing_time')
                            ->label(__('Closing time'))
                            ->time()
                            ->placeholder(__('-')),
                    ])
                    ->columns(2),

                Section::make(__('Availability'))
                    ->schema([
                        TextEntry::make('available_spaces')
                            ->label(__('Available spaces'))
                            ->numeric()
                            ->placeholder(__('-')),

                        TextEntry::make('availability_status')
                            ->label(__('Status'))
                            ->badge(),

                        TextEntry::make('availability_updated_at')
                            ->label(__('Last updated'))
                            ->dateTime()
                            ->placeholder(__('-')),
                    ])
                    ->columns(3),

                Section::make(__('Description'))
                    ->schema([
                        TextEntry::make('description')
                            ->label(__('Description'))
                            ->html()
                            ->placeholder(__('-'))
                            ->columnSpanFull(),
                    ]),

                Section::make(__('Record Information'))
                    ->schema([
                        TextEntry::make('created_at')
                            ->label(__('Created'))
                            ->dateTime()
                            ->placeholder(__('—')),

                        TextEntry::make('updated_at')
                            ->label(__('Updated'))
                            ->dateTime()
                            ->placeholder(__('—')),

                        TextEntry::make('deleted_at')
                            ->label(__('Deleted'))
                            ->dateTime()
                            ->placeholder(__('—'))
                            ->visible(
                                fn($record): bool => $record->trashed(),
                            ),
                    ])
                    ->columns(3)
                    ->collapsible(),
            ]);
    }
}