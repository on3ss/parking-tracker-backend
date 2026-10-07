<?php

namespace App\Filament\Schemas\Shared\Infolists;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;

final class DescriptionSection
{
    public static function make(): Section
    {
        return Section::make(__('Description'))
            ->columnSpanFull()
            ->schema([
                TextEntry::make('description')
                    ->label(__('Description'))
                    ->html()
                    ->columnSpanFull(),
            ]);
    }
}
