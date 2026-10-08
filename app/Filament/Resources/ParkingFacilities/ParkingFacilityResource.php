<?php

namespace App\Filament\Resources\ParkingFacilities;

use App\Filament\RelationManagers\OccupancyReportsRelationManager;
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
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ParkingFacilityResource extends Resource
{
    protected static ?string $model = ParkingFacility::class;

    protected static ?string $tenantOwnershipRelationshipName = 'provider';

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedSquare3Stack3d;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        $components = [
            ParkingFacilityForm::information(),
        ];

        if (! static::isProviderPanel()) {
            $components[] = ParkingFacilityForm::provider();
        }

        $components[] = ParkingFacilityForm::operatingHours();
        $components[] = ParkingFacilityForm::location();
        $components[] = ParkingFacilityForm::description();

        return $schema->components($components);
    }

    public static function infolist(Schema $schema): Schema
    {
        $components = [
            ParkingFacilityInfolist::information(),
            ParkingFacilityInfolist::location(),
            ParkingFacilityInfolist::operatingHours(),
            ParkingFacilityInfolist::availability(),
            ParkingFacilityInfolist::description(),
        ];

        if (! static::isProviderPanel()) {
            $components[] = ParkingFacilityInfolist::recordInformation();
        }

        return $schema->components($components);
    }

    public static function table(Table $table): Table
    {
        $columns = [
            ParkingFacilityColumns::name(),
        ];

        if (! static::isProviderPanel()) {
            $columns[] = ParkingFacilityColumns::provider();
        }

        $columns = [
            ...$columns,
            ParkingFacilityColumns::type(),
            ParkingFacilityColumns::status(),
            ParkingFacilityColumns::capacity(),
            ...ParkingFacilityColumns::availability(),
            ParkingFacilityColumns::locality(),
        ];

        if (! static::isProviderPanel()) {
            $columns[] = ParkingFacilityColumns::slug();
        }

        $filters = [
            ParkingFacilityFilters::type(),
            ParkingFacilityFilters::status(),
            ParkingFacilityFilters::availability(),
        ];

        if (! static::isProviderPanel()) {
            array_unshift(
                $filters,
                ParkingFacilityFilters::provider(),
            );
        }

        $actions = [
            ViewAction::make(),
            EditAction::make(),
            DeleteAction::make(),
        ];

        $bulkActions = [
            DeleteBulkAction::make(),
        ];

        if (! static::isProviderPanel()) {
            $filters[] = TrashedFilter::make();
            $bulkActions[] = RestoreBulkAction::make();
        }

        return $table
            ->defaultSort('name')
            ->columns($columns)
            ->filters($filters)
            ->recordActions($actions)
            ->toolbarActions([
                BulkActionGroup::make($bulkActions),
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
        if (static::isProviderPanel()) {
            return parent::getRecordRouteBindingEloquentQuery();
        }

        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    private static function isProviderPanel(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'provider';
    }
}
