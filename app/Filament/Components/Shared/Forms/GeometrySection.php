<?php

namespace App\Filament\Components\Shared\Forms;

use App\Filament\Forms\Components\GeometryPicker;
use Filament\Schemas\Components\Section;

final class GeometrySection
{
    public static function make(
        string $name,
        string $label,
        string $geometryType,
        int $height = 450,
    ): Section {
        return Section::make($label)
            ->columnSpanFull()
            ->schema([
                GeometryPicker::make($name)
                    ->label($label)
                    ->geometryType($geometryType)
                    ->zoom(16)
                    ->height($height)
                    ->maxVertices(500)
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}