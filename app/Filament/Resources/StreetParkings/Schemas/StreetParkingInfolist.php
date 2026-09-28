<?php

namespace App\Filament\Resources\StreetParkings\Schemas;

use App\Models\StreetParking;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StreetParkingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Parking Information'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('Name'))
                            ->placeholder(__('—')),

                        TextEntry::make('slug')
                            ->label(__('Slug'))
                            ->fontFamily('mono')
                            ->copyable()
                            ->copyMessage(__('Slug copied'))
                            ->placeholder(__('—')),

                        TextEntry::make('provider.name')
                            ->label(__('Provider'))
                            ->placeholder(__('—')),

                        TextEntry::make('road_name')
                            ->label(__('Road'))
                            ->placeholder(__('—')),

                        TextEntry::make('side')
                            ->label(__('Side'))
                            ->placeholder(__('—')),

                        TextEntry::make('parking_type')
                            ->label(__('Parking type'))
                            ->badge()
                            ->placeholder(__('—')),

                        TextEntry::make('status')
                            ->label(__('Status'))
                            ->badge()
                            ->placeholder(__('—')),

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
                            ->placeholder(__('—')),

                        TextEntry::make('location.address_line2')
                            ->label(__('Address line 2'))
                            ->placeholder(__('—')),

                        TextEntry::make('location.locality')
                            ->label(__('Locality'))
                            ->placeholder(__('—')),

                        TextEntry::make('location.administrative_area')
                            ->label(__('Administrative area'))
                            ->placeholder(__('—')),

                        TextEntry::make('location.postal_code')
                            ->label(__('Postal code'))
                            ->placeholder(__('—')),

                        TextEntry::make('location.country_code')
                            ->label(__('Country code'))
                            ->placeholder(__('—')),
                    ])
                    ->columns(2),

                Section::make(__('Availability'))
                    ->schema([
                        TextEntry::make('available_spaces')
                            ->label(__('Available spaces'))
                            ->numeric()
                            ->placeholder(__('—')),

                        TextEntry::make('availability_status')
                            ->label(__('Availability status'))
                            ->badge()
                            ->placeholder(__('—')),

                        TextEntry::make('availability_updated_at')
                            ->label(__('Last updated'))
                            ->dateTime()
                            ->placeholder(__('—')),
                    ])
                    ->columns(3),

                Section::make(__('Description'))
                    ->schema([
                        TextEntry::make('description')
                            ->label(__('Description'))
                            ->html()
                            ->placeholder(__('—'))
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

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
                                fn(StreetParking $record): bool => $record->trashed()
                            ),
                    ])
                    ->columns(3)
                    ->collapsible(),
            ]);
    }
}