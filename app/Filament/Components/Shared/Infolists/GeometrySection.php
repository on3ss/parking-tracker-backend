<?php

namespace App\Filament\Components\Shared\Infolists;

use App\Filament\Infolists\Components\GeometryEntry;
use Filament\Schemas\Components\Section;

final class GeometrySection
{
    public static function make(
        string $name,
        string $label,
    ): Section {
        return Section::make($label)
            ->columnSpanFull()
            ->schema([
                GeometryEntry::make($name)
                    ->label($label)
                    ->columnSpanFull(),
            ]);
    }
}