<?php

namespace App\Filament\Admin\Resources\StreetParkings\Schemas;

use App\Enums\AvailabilityStatus;
use App\Enums\ParkingStatus;
use App\Enums\StreetParkingType;
use App\Filament\Admin\Support\StripGeometry;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StreetParkingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Parking Information'))
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
                            ->helperText(__('Generated automatically and cannot be changed.')),

                        Select::make('parking_provider_id')
                            ->label(__('Provider'))
                            ->relationship('provider', 'name')
                            ->searchable()
                            ->preload(),

                        TextInput::make('road_name')
                            ->label(__('Road'))
                            ->maxLength(255),

                        TextInput::make('side')
                            ->label(__('Side'))
                            ->maxLength(20),

                        Select::make('parking_type')
                            ->label(__('Parking type'))
                            ->options(StreetParkingType::class)
                            ->default(StreetParkingType::CURBSIDE)
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
                            ->minValue(0),
                    ])
                    ->columns(2),

                Section::make(__('Location'))
                    ->relationship('location')
                    ->mutateRelationshipDataBeforeFillUsing(
                        fn(array $data): array => StripGeometry::from($data),
                    )
                    ->schema([
                        TextInput::make('address_line1')
                            ->label(__('Address'))
                            ->maxLength(255),

                        TextInput::make('address_line2')
                            ->label(__('Address line 2'))
                            ->maxLength(255),

                        TextInput::make('locality')
                            ->label(__('Locality'))
                            ->maxLength(255)
                            ->required(),

                        TextInput::make('administrative_area')
                            ->label(__('Administrative area'))
                            ->maxLength(255),

                        TextInput::make('postal_code')
                            ->label(__('Postal code'))
                            ->maxLength(20),

                        TextInput::make('country_code')
                            ->label(__('Country code'))
                            ->maxLength(2)
                            ->default('IN')
                            ->required(),
                    ])
                    ->columns(2),

                Section::make(__('Availability'))
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('available_spaces')
                            ->label(__('Available spaces'))
                            ->numeric()
                            ->integer()
                            ->disabled()
                            ->dehydrated(false),

                        Select::make('availability_status')
                            ->label(__('Availability status'))
                            ->options(AvailabilityStatus::class)
                            ->disabled()
                            ->dehydrated(false),

                        DateTimePicker::make('availability_updated_at')
                            ->label(__('Last updated'))
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(3),

                Section::make(__('Description'))
                    ->columnSpanFull()
                    ->schema([
                        RichEditor::make('description')
                            ->label(__('Description'))
                            ->maxLength(5000)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}