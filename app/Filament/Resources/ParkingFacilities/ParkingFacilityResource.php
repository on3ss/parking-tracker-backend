<?php

namespace App\Filament\Resources\ParkingFacilities;

use App\Filament\RelationManagers\OccupancyReportsRelationManager;
use App\Filament\Resources\BaseResource;
use App\Filament\Resources\ParkingFacilities\Pages\CreateParkingFacility;
use App\Filament\Resources\ParkingFacilities\Pages\EditParkingFacility;
use App\Filament\Resources\ParkingFacilities\Pages\ListParkingFacilities;
use App\Filament\Resources\ParkingFacilities\Pages\ViewParkingFacility;
use App\Filament\Resources\ParkingFacilities\RelationManagers\AreasRelationManager;
use App\Filament\Resources\ParkingFacilities\Schemas\Forms\ParkingFacilityForm;
use App\Filament\Resources\ParkingFacilities\Schemas\Infolists\ParkingFacilityInfolist;
use App\Filament\Resources\ParkingFacilities\Schemas\Tables\ParkingFacilityColumns;
use App\Filament\Resources\ParkingFacilities\Schemas\Tables\ParkingFacilityFilters;
use App\Models\ParkingFacility;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ParkingFacilityResource extends BaseResource
{
    protected static ?string $model = ParkingFacility::class;

    protected static ?string $tenantOwnershipRelationshipName = 'provider';

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedSquare3Stack3d;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            ParkingFacilityForm::information(),

            ...static::providerFormComponents(),

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

            ...static::recordInformationComponents(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns(static::tableColumns())
            ->filters(static::tableFilters())
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make(static::bulkActions()),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AreasRelationManager::class,
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

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $query = parent::getRecordRouteBindingEloquentQuery();

        if (static::isGlobalContext()) {
            $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        }

        return $query;
    }

    private static function providerFormComponents(): array
    {
        return static::isGlobalContext()
            ? [ParkingFacilityForm::provider()]
            : [];
    }

    private static function recordInformationComponents(): array
    {
        return static::isGlobalContext()
            ? [ParkingFacilityInfolist::recordInformation()]
            : [];
    }

    private static function tableColumns(): array
    {
        return [
            ParkingFacilityColumns::name(),

            ...static::globalColumns(),

            ParkingFacilityColumns::type(),
            ParkingFacilityColumns::status(),
            ParkingFacilityColumns::capacity(),
            ...ParkingFacilityColumns::availability(),
            ParkingFacilityColumns::locality(),
        ];
    }

    private static function globalColumns(): array
    {
        return static::isGlobalContext()
            ? [
                ParkingFacilityColumns::provider(),
                ParkingFacilityColumns::slug(),
            ]
            : [];
    }

    private static function tableFilters(): array
    {
        return [
            ...static::globalFilters(),

            ParkingFacilityFilters::type(),
            ParkingFacilityFilters::status(),
            ParkingFacilityFilters::availability(),
        ];
    }

    private static function globalFilters(): array
    {
        return static::isGlobalContext()
            ? [
                ParkingFacilityFilters::provider(),
                TrashedFilter::make(),
            ]
            : [];
    }

    private static function bulkActions(): array
    {
        return [
            DeleteBulkAction::make(),

            ...static::globalBulkActions(),
        ];
    }

    private static function globalBulkActions(): array
    {
        return static::isGlobalContext()
            ? [RestoreBulkAction::make()]
            : [];
    }
}