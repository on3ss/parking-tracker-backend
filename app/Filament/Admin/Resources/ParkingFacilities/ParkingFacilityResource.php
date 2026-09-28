<?php

namespace App\Filament\Admin\Resources\ParkingFacilities;

use App\Filament\Admin\Resources\ParkingFacilities\Pages\CreateParkingFacility;
use App\Filament\Admin\Resources\ParkingFacilities\Pages\EditParkingFacility;
use App\Filament\Admin\Resources\ParkingFacilities\Pages\ListParkingFacilities;
use App\Filament\Admin\Resources\ParkingFacilities\Pages\ViewParkingFacility;
use App\Filament\Components\ParkingFacilities\Forms\ParkingFacilityForm;
use App\Filament\Components\ParkingFacilities\Infolists\ParkingFacilityInfolist;
use App\Filament\Components\ParkingFacilities\Tables\ParkingFacilityColumns;
use App\Filament\Components\ParkingFacilities\Tables\ParkingFacilityFilters;
use App\Models\ParkingFacility;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
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

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedSquare3Stack3d;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            ParkingFacilityForm::information(),

            ParkingFacilityForm::provider(),

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
            ParkingFacilityInfolist::recordInformation(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                ParkingFacilityColumns::name(),
                ParkingFacilityColumns::provider(),
                ParkingFacilityColumns::type(),
                ParkingFacilityColumns::status(),
                ParkingFacilityColumns::capacity(),
                ...ParkingFacilityColumns::availability(),
                ParkingFacilityColumns::locality(),
                ParkingFacilityColumns::slug(),
                ...ParkingFacilityColumns::operatingHours(),
                ...ParkingFacilityColumns::timestamps(),
            ])
            ->filters([
                ParkingFacilityFilters::provider(),
                ParkingFacilityFilters::type(),
                ParkingFacilityFilters::status(),
                ParkingFacilityFilters::availability(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AreasRelationManager::class,
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
