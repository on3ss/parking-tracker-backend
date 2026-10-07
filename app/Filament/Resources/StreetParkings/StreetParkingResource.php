<?php

namespace App\Filament\Resources\StreetParkings;

use App\Filament\Components\StreetParkings\Forms\StreetParkingForm;
use App\Filament\Components\StreetParkings\Infolists\StreetParkingInfolist;
use App\Filament\Components\StreetParkings\Tables\StreetParkingColumns;
use App\Filament\Components\StreetParkings\Tables\StreetParkingFilters;
use App\Filament\RelationManagers\OccupancyReportsRelationManager;
use App\Filament\Resources\StreetParkings\Pages\CreateStreetParking;
use App\Filament\Resources\StreetParkings\Pages\EditStreetParking;
use App\Filament\Resources\StreetParkings\Pages\ListStreetParkings;
use App\Filament\Resources\StreetParkings\Pages\ViewStreetParking;
use App\Models\StreetParking;
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
use UnitEnum;

class StreetParkingResource extends Resource
{
    protected static ?string $model = StreetParking::class;

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
            ->toolbarActions(static::toolbarActions());
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
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    private static function isProviderPanel(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'provider';
    }

    private static function providerFormComponents(): array
    {
        return static::isProviderPanel()
            ? []
            : [
                StreetParkingForm::provider(),
            ];
    }

    private static function recordInformationComponents(): array
    {
        return static::isProviderPanel()
            ? []
            : [
                StreetParkingInfolist::recordInformation(),
            ];
    }

    private static function tableColumns(): array
    {
        return [
            StreetParkingColumns::name(),

            ...(!static::isProviderPanel()
                ? [StreetParkingColumns::provider()]
                : []),

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

    private static function tableFilters(): array
    {
        return [
            ...(!static::isProviderPanel()
                ? [StreetParkingFilters::provider()]
                : []),

            StreetParkingFilters::parkingType(),
            StreetParkingFilters::status(),
            StreetParkingFilters::availability(),

            ...(!static::isProviderPanel()
                ? [TrashedFilter::make()]
                : []),
        ];
    }

    private static function toolbarActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),

                ...(!static::isProviderPanel()
                    ? [RestoreBulkAction::make()]
                    : []),
            ]),
        ];
    }
}