<?php

namespace App\Filament\Admin\Resources\StreetParkings;

use App\Filament\Admin\Resources\StreetParkings\Pages\CreateStreetParking;
use App\Filament\Admin\Resources\StreetParkings\Pages\EditStreetParking;
use App\Filament\Admin\Resources\StreetParkings\Pages\ListStreetParkings;
use App\Filament\Admin\Resources\StreetParkings\Pages\ViewStreetParking;
use App\Filament\Components\StreetParkings\Forms\StreetParkingForm;
use App\Filament\Components\StreetParkings\Infolists\StreetParkingInfolist;
use App\Filament\Components\StreetParkings\Tables\StreetParkingColumns;
use App\Filament\Components\StreetParkings\Tables\StreetParkingFilters;
use App\Models\StreetParking;
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
            StreetParkingForm::provider(),
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
            StreetParkingInfolist::recordInformation(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                StreetParkingColumns::name(),
                StreetParkingColumns::provider(),
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
                StreetParkingFilters::provider(),
                StreetParkingFilters::parkingType(),
                StreetParkingFilters::status(),
                StreetParkingFilters::availability(),
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

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
