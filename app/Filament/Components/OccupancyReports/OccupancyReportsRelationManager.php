<?php

namespace App\Filament\Components\OccupancyReports;

use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OccupancyReportsRelationManager extends RelationManager
{
    protected static string $relationship = 'occupancyReports';

    protected static ?string $title = 'Availability history';

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Report'))
                ->schema([
                    TextEntry::make('source')
                        ->label(__('Source'))
                        ->badge(),

                    TextEntry::make('user.name')
                        ->label(__('Reported by'))
                        ->placeholder(__('System')),

                    TextEntry::make('available_spaces')
                        ->label(__('Available'))
                        ->numeric(),

                    TextEntry::make('occupied_spaces')
                        ->label(__('Occupied'))
                        ->numeric(),

                    TextEntry::make('confidence')
                        ->label(__('Confidence'))
                        ->formatStateUsing(
                            fn($state): string => $state === null
                                ? '—'
                                : number_format(
                                    (float) $state * 100,
                                    1
                                ) . '%',
                        ),

                    TextEntry::make('reported_at')
                        ->label(__('Reported'))
                        ->dateTime(),
                ])
                ->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('reported_at', 'desc')

            ->columns([
                TextColumn::make('reported_at')
                    ->label(__('Reported'))
                    ->dateTime()
                    ->sortable()
                    ->description(
                        fn($record): string =>
                            $record->reported_at?->diffForHumans() ?? '—',
                    ),

                TextColumn::make('available_spaces')
                    ->label(__('Available'))
                    ->numeric()
                    ->sortable(),

                TextColumn::make('occupied_spaces')
                    ->label(__('Occupied'))
                    ->numeric()
                    ->sortable(),

                TextColumn::make('source')
                    ->label(__('Source'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label(__('Reported by'))
                    ->placeholder(__('System'))
                    ->sortable(),
            ])

            ->recordActions([
                ViewAction::make(),
            ]);
    }
}