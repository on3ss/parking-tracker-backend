<?php

namespace App\Filament\Components\Shared\Forms;

use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Section;

final class DescriptionSection
{
    public static function make(): Section
    {
        return Section::make(__('Description'))
            ->columnSpanFull()
            ->schema([
                RichEditor::make('description')
                    ->label(__('Description'))
                    ->maxLength(5000)
                    ->columnSpanFull(),
            ]);
    }
}