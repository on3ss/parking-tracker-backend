<?php

namespace App\Filament\Components\Shared\Forms;

use App\Filament\Forms\Components\GeometryPicker;
use App\Filament\Support\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

final class LocationSection
{
    public static function make(string $statePath = 'location'): Section
    {
        return Section::make(__('Location'))
            ->statePath($statePath)
            ->columnSpanFull()
            ->columns(Grid::FOUR)
            ->schema([
                TextInput::make('address_line1')
                    ->label(__('Address'))
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('address_line2')
                    ->label(__('Address line 2'))
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('locality')
                    ->label(__('Locality'))
                    ->maxLength(255)
                    ->columnSpan(2),

                TextInput::make('administrative_area')
                    ->label(__('Administrative area'))
                    ->maxLength(255)
                    ->columnSpan(2),

                TextInput::make('postal_code')
                    ->label(__('Postal code'))
                    ->maxLength(20)
                    ->columnSpan(2),

                TextInput::make('country_code')
                    ->label(__('Country code'))
                    ->length(2)
                    ->default('IN')
                    ->required()
                    ->columnSpan(2),

                TextInput::make('latitude')
                    ->label(__('Latitude'))
                    ->numeric()
                    ->required()
                    ->columnSpan(2),

                TextInput::make('longitude')
                    ->label(__('Longitude'))
                    ->numeric()
                    ->required()
                    ->columnSpan(2),

                GeometryPicker::make('coordinates')
                    ->label(__('Coordinates'))
                    ->coordinateFields()
                    ->zoom(16)
                    ->height(260)
                    ->columnSpanFull(),
            ]);
    }
}