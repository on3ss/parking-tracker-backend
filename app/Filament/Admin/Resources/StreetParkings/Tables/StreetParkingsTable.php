<?php

namespace App\Filament\Admin\Resources\StreetParkings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StreetParkingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('provider.name')
                    ->label(__('Provider'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('road_name')
                    ->label(__('Road'))
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('parking_type')
                    ->label(__('Parking type'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('capacity')
                    ->label(__('Capacity'))
                    ->numeric()
                    ->sortable(),

                TextColumn::make('available_spaces')
                    ->label(__('Available'))
                    ->numeric()
                    ->sortable(),

                TextColumn::make('availability_status')
                    ->label(__('Availability'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('availability_updated_at')
                    ->label(__('Availability updated'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('Updated'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->label(__('Deleted'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(
                fn (Builder $query) => $query->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ])
            );
    }
}
