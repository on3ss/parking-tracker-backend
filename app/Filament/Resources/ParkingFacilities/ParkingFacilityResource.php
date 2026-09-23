<?php

namespace App\Filament\Resources\ParkingFacilities;

use App\Filament\Resources\ParkingFacilities\Pages\CreateParkingFacility;
use App\Filament\Resources\ParkingFacilities\Pages\EditParkingFacility;
use App\Filament\Resources\ParkingFacilities\Pages\ListParkingFacilities;
use App\Filament\Resources\ParkingFacilities\Pages\ViewParkingFacility;
use App\Filament\Resources\ParkingFacilities\Schemas\ParkingFacilityForm;
use App\Filament\Resources\ParkingFacilities\Schemas\ParkingFacilityInfolist;
use App\Filament\Resources\ParkingFacilities\Tables\ParkingFacilitiesTable;
use App\Models\ParkingFacility;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ParkingFacilityResource extends Resource
{
    protected static ?string $model = ParkingFacility::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ParkingFacilityForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ParkingFacilityInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ParkingFacilitiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListParkingFacilities::route('/'),
            'create' => CreateParkingFacility::route('/create'),
            'view' => ViewParkingFacility::route('/{record}'),
            'edit' => EditParkingFacility::route('/{record}/edit'),
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
