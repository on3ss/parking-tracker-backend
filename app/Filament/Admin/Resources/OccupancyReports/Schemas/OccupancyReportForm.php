<?php

namespace App\Filament\Admin\Resources\OccupancyReports\Schemas;

use App\Enums\ParkingSource;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OccupancyReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('parking_facility_id')
                    ->relationship('parkingFacility', 'name'),
                Select::make('street_parking_id')
                    ->relationship('streetParking', 'name'),
                Select::make('user_id')
                    ->relationship('user', 'name'),
                Select::make('source')
                    ->options(ParkingSource::class)
                    ->required(),
                TextInput::make('occupied_spaces')
                    ->numeric(),
                TextInput::make('available_spaces')
                    ->numeric(),
                TextInput::make('reported_confidence')
                    ->numeric(),
                DateTimePicker::make('reported_at')
                    ->required(),
            ]);
    }
}
