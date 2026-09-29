<?php

namespace App\Filament\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OccupancyReportsRelationManager extends RelationManager
{
    protected static string $relationship = 'occupancyReports';

    protected static ?string $title = 'Occupancy reports';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('reported_at', 'desc')
            ->columns([
                TextColumn::make('source')
                    ->label(__('Source'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label(__('Reported by'))
                    ->placeholder(__('System')),

                TextColumn::make('occupied_spaces')
                    ->label(__('Occupied'))
                    ->numeric()
                    ->sortable(),

                TextColumn::make('available_spaces')
                    ->label(__('Available'))
                    ->numeric()
                    ->sortable(),

                TextColumn::make('reported_at')
                    ->label(__('Reported at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([]);
    }
}
