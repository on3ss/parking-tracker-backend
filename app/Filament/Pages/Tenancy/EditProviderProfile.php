<?php

namespace App\Filament\Pages\Tenancy;

use App\Enums\ParkingProviderType;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditProviderProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return __('Provider Profile');
    }

    public function form(Schema $schema): Schema
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
                            ->helperText(
                                __('Generated automatically and cannot be changed.')
                            ),

                        Select::make('type')
                            ->label(__('Type'))
                            ->options(ParkingProviderType::class)
                            ->native(false)
                            ->required(),

                        Toggle::make('is_active')
                            ->label(__('Active'))
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