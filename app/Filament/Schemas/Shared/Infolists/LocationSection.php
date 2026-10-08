<?php

namespace App\Filament\Schemas\Shared\Infolists;

use App\Filament\Infolists\Components\GeometryEntry;
use App\Filament\Support\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;

final class LocationSection
{
    public static function make(string $statePath = 'location'): Section
    {
        return Section::make(__('Location'))
            ->columnSpanFull()
            ->columns(Grid::FOUR)
            ->schema([
                TextEntry::make("{$statePath}.address_line1")
                    ->label(__('Address'))
                    ->columnSpanFull(),

                TextEntry::make("{$statePath}.address_line2")
                    ->label(__('Address line 2'))
                    ->columnSpanFull(),

                TextEntry::make("{$statePath}.locality")
                    ->label(__('Locality'))
                    ->columnSpan(2),

                TextEntry::make("{$statePath}.administrative_area")
                    ->label(__('Administrative area'))
                    ->columnSpan(2),

                TextEntry::make("{$statePath}.postal_code")
                    ->label(__('Postal code'))
                    ->columnSpan(2),

                TextEntry::make("{$statePath}.country_code")
                    ->label(__('Country'))
                    ->columnSpan(2),

                GeometryEntry::make("{$statePath}.coordinates")
                    ->label(__('Map'))
                    ->zoom(16)
                    ->height(260)
                    ->hidden(fn (?Model $record): bool => blank($record?->{$statePath}?->coordinates))
                    ->columnSpanFull(),
            ]);
    }
}
