<?php

namespace App\Filament\Components\StreetParkings\Forms;

use App\Enums\ParkingStatus;
use App\Enums\StreetParkingSide;
use App\Enums\StreetParkingType;
use App\Filament\Components\Shared\Forms\DescriptionSection;
use App\Filament\Components\Shared\Forms\GeometrySection;
use App\Filament\Components\Shared\Forms\LocationSection;
use App\Filament\Support\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

final class StreetParkingForm
{
    public static function information(): Section
    {
        return Section::make(__('Street Parking Information'))
            ->columnSpanFull()
            ->columns(Grid::THREE)
            ->schema([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255)
                    ->autofocus()
                    ->columnSpan(2),

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
                    ),

                TextInput::make('road_name')
                    ->label(__('Road name'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(2),

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
                    ->columnSpan(2),

                TextInput::make('capacity')
                    ->label(__('Capacity'))
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->required(),
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
        return LocationSection::make('location');
    }

    public static function geometry(): Section
    {
        return GeometrySection::make(
            name: 'geometry',
            label: __('Street segment'),
            geometryType: 'linestring',
        );
    }

    public static function description(): Section
    {
        return DescriptionSection::make();
    }
}
