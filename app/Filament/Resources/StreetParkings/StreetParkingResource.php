<?php

namespace App\Filament\Resources\StreetParkings;

use App\Filament\RelationManagers\OccupancyReportsRelationManager;
use App\Filament\Resources\BaseResource;
use App\Filament\Resources\StreetParkings\Pages\CreateStreetParking;
use App\Filament\Resources\StreetParkings\Pages\EditStreetParking;
use App\Filament\Resources\StreetParkings\Pages\ListStreetParkings;
use App\Filament\Resources\StreetParkings\Pages\ViewStreetParking;
use App\Filament\Resources\StreetParkings\Schemas\Forms\StreetParkingForm;
use App\Filament\Resources\StreetParkings\Schemas\Infolists\StreetParkingInfolist;
use App\Filament\Resources\StreetParkings\Schemas\Tables\StreetParkingColumns;
use App\Filament\Resources\StreetParkings\Schemas\Tables\StreetParkingFilters;
use App\Models\StreetParking;
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
use UnitEnum;

class StreetParkingResource extends BaseResource
{
    protected static ?string $model = StreetParking::class;

    protected static ?string $tenantOwnershipRelationshipName = 'provider';

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Parking';

    protected static ?string $navigationLabel = 'Street Parking';

    protected static ?string $modelLabel = 'Street Parking';

    protected static ?string $pluralModelLabel = 'Street Parking';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            StreetParkingForm::information(),

            ...static::providerFormComponents(),

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
            OccupancyReportsRelationManager::class,
        ];
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
            ? [StreetParkingForm::provider()]
            : [];
    }

    private static function recordInformationComponents(): array
    {
        return static::isGlobalContext()
            ? [StreetParkingInfolist::recordInformation()]
            : [];
    }

    private static function tableColumns(): array
    {
        return [
            StreetParkingColumns::name(),

            ...static::globalColumns(),

            StreetParkingColumns::roadName(),
            StreetParkingColumns::side(),
            StreetParkingColumns::parkingType(),
            StreetParkingColumns::status(),
            StreetParkingColumns::capacity(),
            ...StreetParkingColumns::availability(),
            StreetParkingColumns::locality(),
            StreetParkingColumns::slug(),
            ...StreetParkingColumns::timestamps(),
        ];
    }

    private static function globalColumns(): array
    {
        return static::isGlobalContext()
            ? [StreetParkingColumns::provider()]
            : [];
    }

    private static function tableFilters(): array
    {
        return [
            ...static::globalFilters(),

            StreetParkingFilters::parkingType(),
            StreetParkingFilters::status(),
            StreetParkingFilters::availability(),
        ];
    }

    private static function globalFilters(): array
    {
        return static::isGlobalContext()
            ? [
                StreetParkingFilters::provider(),
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
