<?php

namespace App\Filament\Admin\Resources\OccupancyReports\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OccupancyReportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Report'))
                    ->description(
                        __('Immutable availability report recorded by the system.')
                    )
                    ->schema([
                        TextEntry::make('id')
                            ->label(__('Report ID'))
                            ->fontFamily('mono')
                            ->copyable(),

                        TextEntry::make('source')
                            ->label(__('Source'))
                            ->badge(),

                        TextEntry::make('reported_at')
                            ->label(__('Reported At'))
                            ->dateTime(),

                        TextEntry::make('reported_at')
                            ->label(__('Relative Time'))
                            ->since(),
                    ])
                    ->columns(2),

                Section::make(__('Parking'))
                    ->schema([
                        TextEntry::make('parkingFacility.name')
                            ->label(__('Facility'))
                            ->placeholder(__('—')),

                        TextEntry::make('streetParking.name')
                            ->label(__('Street Parking'))
                            ->placeholder(__('—')),
                    ])
                    ->columns(2),

                Section::make(__('Availability'))
                    ->schema([
                        TextEntry::make('available_spaces')
                            ->label(__('Available Spaces'))
                            ->numeric()
                            ->weight('bold'),

                        TextEntry::make('occupied_spaces')
                            ->label(__('Occupied Spaces'))
                            ->numeric()
                            ->placeholder(__('—')),

                        TextEntry::make('reported_confidence')
                            ->label(__('Reported Confidence'))
                            ->formatStateUsing(
                                fn($state): string => $state === null
                                    ? '—'
                                    : number_format(
                                        (float) $state * 100,
                                        2,
                                    ) . '%',
                            ),
                    ])
                    ->columns(3),

                Section::make(__('Reporter'))
                    ->schema([
                        TextEntry::make('user.name')
                            ->label(__('Name'))
                            ->placeholder(__('System')),

                        TextEntry::make('user.email')
                            ->label(__('Email'))
                            ->placeholder(__('—')),
                    ])
                    ->columns(2),

                Section::make(__('Record Information'))
                    ->schema([
                        TextEntry::make('created_at')
                            ->label(__('Created'))
                            ->dateTime(),

                        TextEntry::make('updated_at')
                            ->label(__('Updated'))
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }
}
