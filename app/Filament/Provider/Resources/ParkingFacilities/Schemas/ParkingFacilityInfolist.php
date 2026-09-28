<?php

namespace App\Filament\Provider\Resources\ParkingFacilities\Schemas;

use App\Models\ParkingFacility;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ParkingFacilityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('parking_provider_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('location.id')
                    ->label('Location'),
                TextEntry::make('name'),
                TextEntry::make('slug'),
                TextEntry::make('type')
                    ->badge(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('capacity')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('opening_time')
                    ->time()
                    ->placeholder('-'),
                TextEntry::make('closing_time')
                    ->time()
                    ->placeholder('-'),
                TextEntry::make('available_spaces')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('availability_status')
                    ->badge(),
                TextEntry::make('availability_updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (ParkingFacility $record): bool => $record->trashed()),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
