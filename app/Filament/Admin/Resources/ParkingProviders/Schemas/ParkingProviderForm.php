<?php

namespace App\Filament\Admin\Resources\ParkingProviders\Schemas;

use App\Enums\ParkingProviderType;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ParkingProviderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Provider Information'))
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

                        Select::make('type')
                            ->label(__('Type'))
                            ->options(ParkingProviderType::class)
                            ->default(ParkingProviderType::PRIVATE)
                            ->native(false)
                            ->required(),

                        Toggle::make('is_active')
                            ->label(__('Active'))
                            ->default(true)
                            ->inline(false),
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
