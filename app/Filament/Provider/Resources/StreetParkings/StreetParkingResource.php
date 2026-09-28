<?php

namespace App\Filament\Provider\Resources\StreetParkings;

use App\Filament\Components\StreetParkings\Forms\StreetParkingForm;
use App\Filament\Components\StreetParkings\Infolists\StreetParkingInfolist;
use App\Filament\Components\StreetParkings\Tables\StreetParkingColumns;
use App\Filament\Components\StreetParkings\Tables\StreetParkingFilters;
use App\Filament\Provider\Resources\StreetParkings\Pages\CreateStreetParking;
use App\Filament\Provider\Resources\StreetParkings\Pages\EditStreetParking;
use App\Filament\Provider\Resources\StreetParkings\Pages\ListStreetParkings;
use App\Filament\Provider\Resources\StreetParkings\Pages\ViewStreetParking;
use App\Models\StreetParking;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StreetParkingResource extends Resource
{
    protected static ?string $model = StreetParking::class;

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            StreetParkingForm::information(),
            StreetParkingForm::location(),
            StreetParkingForm::geometry(),
            StreetParkingForm::description(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            StreetParkingInfolist::information(),
            StreetParkingInfolist::location(),
            StreetParkingInfolist::geometry(),
            StreetParkingInfolist::availability(),
            StreetParkingInfolist::description(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                StreetParkingColumns::name(),
                StreetParkingColumns::roadName(),
                StreetParkingColumns::side(),
                StreetParkingColumns::parkingType(),
                StreetParkingColumns::status(),
                StreetParkingColumns::capacity(),
                ...StreetParkingColumns::availability(),
                StreetParkingColumns::locality(),
                StreetParkingColumns::slug(),
                ...StreetParkingColumns::timestamps(),
            ])
            ->filters([
                StreetParkingFilters::parkingType(),
                StreetParkingFilters::status(),
                StreetParkingFilters::availability(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStreetParkings::route('/'),
            'create' => CreateStreetParking::route('/create'),
            'view' => ViewStreetParking::route('/{record}'),
            'edit' => EditStreetParking::route('/{record}/edit'),
        ];
    }
}
