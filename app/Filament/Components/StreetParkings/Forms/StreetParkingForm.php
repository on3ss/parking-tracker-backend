<?php

namespace App\Filament\Components\StreetParkings\Forms;

use App\Enums\ParkingStatus;
use App\Enums\StreetParkingType;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

final class StreetParkingForm
{
    public static function information(): Section
    {
        return Section::make(__('Street Parking Information'))
            ->columnSpanFull()
            ->schema([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255),

                TextInput::make('slug')
                    ->label(__('Slug'))
                    ->disabled()
                    ->dehydrated(false)
                    ->visibleOn('edit')
                    ->maxLength(255)
                    ->helperText(__('Generated automatically and cannot be changed.')),

                TextInput::make('road_name')
                    ->label(__('Road name'))
                    ->required()
                    ->maxLength(255),

                TextInput::make('side')
                    ->label(__('Side'))
                    ->maxLength(50),

                Select::make('parking_type')
                    ->label(__('Parking type'))
                    ->options(StreetParkingType::class)
                    ->native(false)
                    ->required(),

                Select::make('status')
                    ->label(__('Status'))
                    ->options(ParkingStatus::class)
                    ->default(ParkingStatus::ACTIVE)
                    ->native(false)
                    ->required(),

                TextInput::make('capacity')
                    ->label(__('Capacity'))
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->required(),
            ])
            ->columns(2);
    }

    public static function provider(): Select
    {
        return Select::make('parking_provider_id')
            ->label(__('Provider'))
            ->relationship('provider', 'name')
            ->searchable()
            ->preload()
            ->required();
    }

    public static function location(): Section
    {
        return Section::make(__('Location'))
            ->statePath('location')
            ->schema([
                TextInput::make('address_line1')
                    ->label(__('Address'))
                    ->maxLength(255),

                TextInput::make('address_line2')
                    ->label(__('Address line 2'))
                    ->maxLength(255),

                TextInput::make('locality')
                    ->label(__('Locality'))
                    ->maxLength(255),

                TextInput::make('administrative_area')
                    ->label(__('Administrative area'))
                    ->maxLength(255),

                TextInput::make('postal_code')
                    ->label(__('Postal code'))
                    ->maxLength(20),

                TextInput::make('country_code')
                    ->label(__('Country code'))
                    ->length(2)
                    ->default('IN')
                    ->required(),

                TextInput::make('latitude')
                    ->label(__('Latitude'))
                    ->numeric(),

                TextInput::make('longitude')
                    ->label(__('Longitude'))
                    ->numeric(),
            ])
            ->columns(2);
    }

    public static function description(): Section
    {
        return Section::make(__('Description'))
            ->columnSpanFull()
            ->schema([
                RichEditor::make('description')
                    ->label(__('Description'))
                    ->maxLength(5000)
                    ->columnSpanFull(),
            ]);
    }
}