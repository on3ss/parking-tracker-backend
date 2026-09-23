<?php

namespace App\Filament\Resources\ParkingProviders\Schemas;

use App\Models\ParkingProvider;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ParkingProviderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Provider Information'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('Name'))
                            ->placeholder(__('—')),

                        TextEntry::make('slug')
                            ->label(__('Slug'))
                            ->fontFamily('mono')
                            ->copyable()
                            ->copyMessage(__('Slug copied'))
                            ->placeholder(__('—')),

                        TextEntry::make('type')
                            ->label(__('Type'))
                            ->badge()
                            ->placeholder(__('—')),

                        IconEntry::make('is_active')
                            ->label(__('Active'))
                            ->boolean(),
                    ])
                    ->columns(2),

                Section::make(__('Record Information'))
                    ->schema([
                        TextEntry::make('created_at')
                            ->label(__('Created'))
                            ->dateTime()
                            ->placeholder(__('—')),

                        TextEntry::make('updated_at')
                            ->label(__('Updated'))
                            ->dateTime()
                            ->placeholder(__('—')),

                        TextEntry::make('deleted_at')
                            ->label(__('Deleted'))
                            ->dateTime()
                            ->placeholder(__('—'))
                            ->visible(
                                fn(ParkingProvider $record): bool => $record->trashed()
                            ),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Section::make(__('Description'))
                    ->schema([
                        TextEntry::make('description')
                            ->label(__('Description'))
                            ->html()
                            ->placeholder(__('—'))
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}