<?php

namespace App\Filament\Resources\ParkingProviders;

use App\Filament\Resources\ParkingProviders\Pages\CreateParkingProvider;
use App\Filament\Resources\ParkingProviders\Pages\EditParkingProvider;
use App\Filament\Resources\ParkingProviders\Pages\ListParkingProviders;
use App\Filament\Resources\ParkingProviders\Pages\ViewParkingProvider;
use App\Filament\Resources\ParkingProviders\Schemas\ParkingProviderForm;
use App\Filament\Resources\ParkingProviders\Schemas\ParkingProviderInfolist;
use App\Filament\Resources\ParkingProviders\Tables\ParkingProvidersTable;
use App\Models\ParkingProvider;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ParkingProviderResource extends Resource
{
    protected static ?string $model = ParkingProvider::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ParkingProviderForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ParkingProviderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ParkingProvidersTable::configure($table);
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
            'index' => ListParkingProviders::route('/'),
            'create' => CreateParkingProvider::route('/create'),
            'view' => ViewParkingProvider::route('/{record}'),
            'edit' => EditParkingProvider::route('/{record}/edit'),
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
