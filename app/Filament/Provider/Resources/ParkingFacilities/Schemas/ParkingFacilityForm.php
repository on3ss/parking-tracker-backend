<?php

namespace App\Filament\Provider\Resources\ParkingFacilities\Schemas;

use App\Enums\AvailabilityStatus;
use App\Enums\ParkingFacilityType;
use App\Enums\ParkingStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class ParkingFacilityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('parking_provider_id')
                    ->numeric(),
                Select::make('location_id')
                    ->relationship('location', 'id')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Select::make('type')
                    ->options(ParkingFacilityType::class)
                    ->default('PUBLIC')
                    ->required(),
                Select::make('status')
                    ->options(ParkingStatus::class)
                    ->default('ACTIVE')
                    ->required(),
                TextInput::make('capacity')
                    ->numeric(),
                TimePicker::make('opening_time'),
                TimePicker::make('closing_time'),
                TextInput::make('available_spaces')
                    ->numeric(),
                Select::make('availability_status')
                    ->options(AvailabilityStatus::class)
                    ->default('UNKNOWN')
                    ->required(),
                DateTimePicker::make('availability_updated_at'),
                Textarea::make('description')
                    ->columnSpanFull(),
            ]);
    }
}
