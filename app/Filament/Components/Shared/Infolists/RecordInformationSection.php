<?php

namespace App\Filament\Components\Shared\Infolists;

use App\Filament\Support\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;

final class RecordInformationSection
{
    public static function make(): Section
    {
        return Section::make(__('Record Information'))
            ->columnSpanFull()
            ->columns(Grid::FOUR)
            ->schema([
                TextEntry::make('id')
                    ->label(__('ID')),

                TextEntry::make('created_at')
                    ->label(__('Created'))
                    ->dateTime(),

                TextEntry::make('updated_at')
                    ->label(__('Updated'))
                    ->dateTime(),

                TextEntry::make('deleted_at')
                    ->label(__('Deleted'))
                    ->dateTime()
                    ->placeholder('—'),
            ]);
    }
}
