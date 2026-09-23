<?php

namespace App\Filament\Resources\ParkingProviders\Schemas;

use App\Enums\ParkingProviderType;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ParkingProviderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        if (blank($state)) {
                            return;
                        }

                        $set('slug', Str::slug($state));
                    }),

                TextInput::make('slug')
                    ->label(__('Slug'))
                    ->disabled()
                    ->dehydrated()
                    ->maxLength(255)
                    ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                    ->unique(
                        table: 'parking_providers',
                        column: 'slug',
                        ignorable: fn ($record) => $record,
                    )
                    ->helperText(__('Generated automatically from the name.')),

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

                RichEditor::make('description')
                    ->label(__('Description'))
                    ->maxLength(5000)
                    ->columnSpanFull(),
            ]);
    }
}
