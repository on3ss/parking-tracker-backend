<?php

namespace App\Filament\Provider\Resources\ParkingFacilities;

use App\Filament\Components\ParkingFacilities\Forms\ParkingFacilityForm;
use App\Filament\Components\ParkingFacilities\Infolists\ParkingFacilityInfolist;
use App\Filament\Components\ParkingFacilities\Tables\ParkingFacilityColumns;
use App\Filament\Provider\Resources\ParkingFacilities\Pages\CreateParkingFacility;
use App\Filament\Provider\Resources\ParkingFacilities\Pages\EditParkingFacility;
use App\Filament\Provider\Resources\ParkingFacilities\Pages\ListParkingFacilities;
use App\Filament\Provider\Resources\ParkingFacilities\Pages\ViewParkingFacility;
use App\Filament\Provider\Resources\ParkingFacilities\RelationManagers\AreasRelationManager;
use App\Filament\RelationManagers\OccupancyReportsRelationManager;
use App\Models\ParkingFacility;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ParkingFacilityResource extends Resource
{
    protected static ?string $model = ParkingFacility::class;

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            ParkingFacilityForm::information(),
            ParkingFacilityForm::operatingHours(),
            ParkingFacilityForm::location(),
            ParkingFacilityForm::description(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            ParkingFacilityInfolist::information(),
            ParkingFacilityInfolist::location(),
            ParkingFacilityInfolist::operatingHours(),
            ParkingFacilityInfolist::availability(),
            ParkingFacilityInfolist::description(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                ParkingFacilityColumns::name(),
                ParkingFacilityColumns::type(),
                ParkingFacilityColumns::status(),
                ParkingFacilityColumns::capacity(),
                ...ParkingFacilityColumns::availability(),
                ParkingFacilityColumns::locality(),
                ...ParkingFacilityColumns::operatingHours(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AreasRelationManager::class,
            OccupancyReportsRelationManager::class,
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
}
