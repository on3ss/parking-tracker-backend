<?php

namespace App\Filament\Admin\Resources\StreetParkings;

use App\Filament\Admin\Resources\StreetParkings\Schemas\StreetParkingForm;
use App\Filament\Admin\Resources\StreetParkings\Schemas\StreetParkingInfolist;
use App\Filament\Admin\Resources\StreetParkings\Tables\StreetParkingsTable;
use App\Models\StreetParking;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use BackedEnum;
use UnitEnum;

class StreetParkingResource extends Resource
{
    protected static ?string $model = StreetParking::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static string|UnitEnum|null $navigationGroup = 'Parking';

    protected static ?string $navigationLabel = 'Street Parking';

    protected static ?string $modelLabel = 'Street Parking';

    protected static ?string $pluralModelLabel = 'Street Parking';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return StreetParkingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StreetParkingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StreetParkingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStreetParkings::route('/'),
            'create' => Pages\CreateStreetParking::route('/create'),
            'view' => Pages\ViewStreetParking::route('/{record}'),
            'edit' => Pages\EditStreetParking::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}