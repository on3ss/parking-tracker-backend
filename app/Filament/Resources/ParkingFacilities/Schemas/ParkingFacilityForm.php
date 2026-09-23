<?php

namespace App\Filament\Resources\ParkingFacilities\Schemas;

use App\Enums\ParkingFacilityType;
use App\Enums\ParkingStatus;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Hamcrest\Core\Set;
use Illuminate\Support\Str;

class ParkingFacilityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Facility Information'))
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state, ): void {
                                if (blank($state) || filled($get('slug'))) {
                                    return;
                                }

                                $set('slug', Str::slug($state));
                            }),

                        TextInput::make('slug')
                            ->label(__('Slug'))
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->maxLength(255)
                            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                            ->unique(
                                table: 'parking_facilities',
                                column: 'slug',
                                ignorable: fn($record) => $record,
                            )
                            ->helperText(__('Generated automatically and cannot be changed.')),

                        Select::make('parking_provider_id')
                            ->label(__('Provider'))
                            ->relationship('provider', 'name')
                            ->searchable()
                            ->preload(),

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
                    ->columns(2),

                Section::make(__('Operating Hours'))
                    ->schema([
                        TimePicker::make('opening_time')
                            ->label(__('Opening time')),

                        TimePicker::make('closing_time')
                            ->label(__('Closing time'))
                            ->after('opening_time'),
                    ])
                    ->columns(2),

                Section::make(__('Location'))
                    ->relationship('location')
                    ->mutateRelationshipDataBeforeFillUsing(
                        function (array $data): array {
                            unset($data['coordinates']);

                            return $data;
                        }
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