<?php

namespace App\Filament\Components\StreetParkings\Forms;

use App\Enums\ParkingStatus;
use App\Enums\StreetParkingSide;
use App\Enums\StreetParkingType;
use App\Filament\Forms\Components\GeometryPicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

final class StreetParkingForm
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
            ->columns(self::GRID)
            ->schema([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 3,
                    ]),

                Select::make('status')
                    ->label(__('Status'))
                    ->options(ParkingStatus::class)
                    ->default(ParkingStatus::ACTIVE)
                    ->native(false)
                    ->required(),

                TextInput::make('slug')
                    ->label(__('Slug'))
                    ->disabled()
                    ->dehydrated(false)
                    ->visibleOn('edit')
                    ->maxLength(255)
                    ->helperText(
                        __('Generated automatically and cannot be changed.'),
                    )
                    ->columnSpanFull(),

                TextInput::make('road_name')
                    ->label(__('Road name'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 3,
                    ]),

                Select::make('side')
                    ->label(__('Side'))
                    ->options(StreetParkingSide::class)
                    ->native(false)
                    ->required(),

                Select::make('parking_type')
                    ->label(__('Parking type'))
                    ->options(StreetParkingType::class)
                    ->native(false)
                    ->required()
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 1,
                        'lg' => 2,
                    ]),

                TextInput::make('capacity')
                    ->label(__('Capacity'))
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->required()
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 1,
                        'lg' => 2,
                    ]),
            ]);
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
            ->columnSpanFull()
            ->columns(self::GRID)
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
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 1,
                        'lg' => 2,
                    ]),

                TextInput::make('administrative_area')
                    ->label(__('Administrative area'))
                    ->maxLength(255)
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 1,
                        'lg' => 2,
                    ]),

                TextInput::make('postal_code')
                    ->label(__('Postal code'))
                    ->maxLength(20)
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 1,
                        'lg' => 2,
                    ]),

                TextInput::make('country_code')
                    ->label(__('Country code'))
                    ->length(2)
                    ->default('IN')
                    ->required()
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 1,
                        'lg' => 2,
                    ]),

                TextInput::make('latitude')
                    ->label(__('Latitude'))
                    ->numeric()
                    ->required()
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 1,
                        'lg' => 2,
                    ]),

                TextInput::make('longitude')
                    ->label(__('Longitude'))
                    ->numeric()
                    ->required()
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 1,
                        'lg' => 2,
                    ]),

                GeometryPicker::make('coordinates')
                    ->label(__('Coordinates'))
                    ->coordinateFields()
                    ->zoom(16)
                    ->height(260)
                    ->columnSpanFull(),
            ]);
    }

    public static function geometry(): Section
    {
        return Section::make(__('Street Geometry'))
            ->columnSpanFull()
            ->schema([
                GeometryPicker::make('geometry')
                    ->label(__('Street segment'))
                    ->geometryType('linestring')
                    ->zoom(16)
                    ->height(450)
                    ->maxVertices(500)
                    ->required()
                    ->columnSpanFull(),
            ]);
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