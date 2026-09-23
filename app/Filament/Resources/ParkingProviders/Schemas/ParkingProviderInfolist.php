<?php

namespace App\Filament\Resources\ParkingProviders\Schemas;

use App\Models\ParkingProvider;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ParkingProviderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label(__('Name')),

                TextEntry::make('slug')
                    ->label(__('Slug'))
                    ->fontFamily('mono')
                    ->copyable()
                    ->copyMessage(__('Slug copied')),

                TextEntry::make('type')
                    ->label(__('Type'))
                    ->badge(),

                TextEntry::make('description')
                    ->label(__('Description'))
                    ->html()
                    ->placeholder(__('—'))
                    ->columnSpanFull(),

                IconEntry::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),

                TextEntry::make('deleted_at')
                    ->label(__('Deleted at'))
                    ->dateTime()
                    ->visible(fn (ParkingProvider $record): bool => $record->trashed()),

                TextEntry::make('created_at')
                    ->label(__('Created at'))
                    ->dateTime()
                    ->placeholder(__('—')),

                TextEntry::make('updated_at')
                    ->label(__('Updated at'))
                    ->dateTime()
                    ->placeholder(__('—')),
            ]);
    }
}
