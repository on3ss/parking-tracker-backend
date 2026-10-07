<?php

namespace App\Filament\Resources\ParkingFacilities\Schemas\Forms;

use App\Enums\ParkingFacilityType;
use App\Enums\ParkingStatus;
use App\Filament\Schemas\Shared\Forms\DescriptionSection;
use App\Filament\Schemas\Shared\Forms\LocationSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;

final class ParkingFacilityForm
{
    public static function information(): Section
    {
        return Section::make(__('Facility Information'))
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
                    ->helperText(
                        __('Generated automatically and cannot be changed.')
                    ),

                Select::make('type')
                    ->label(__('Type'))
                    ->options(ParkingFacilityType::class)
                    ->default(ParkingFacilityType::PUBLIC)
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

    /**
     * Admin-only provider selector.
     */
    public static function provider(): Select
    {
        return Select::make('parking_provider_id')
            ->label(__('Provider'))
            ->relationship('provider', 'name')
            ->searchable()
            ->preload()
            ->required();
    }

    public static function operatingHours(): Section
    {
        return Section::make(__('Operating Hours'))
            ->schema([
                TimePicker::make('opening_time')
                    ->label(__('Opening time')),

                TimePicker::make('closing_time')
                    ->label(__('Closing time'))
                    ->after('opening_time'),
            ])
            ->columns(2);
    }

    public static function location(): Section
    {
        return LocationSection::make('location');
    }

    public static function description(): Section
    {
        return DescriptionSection::make();
    }
}
