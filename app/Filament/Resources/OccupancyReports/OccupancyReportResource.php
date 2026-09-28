<?php

namespace App\Filament\Resources\OccupancyReports;

use App\Filament\Resources\OccupancyReports\Pages\ListOccupancyReports;
use App\Filament\Resources\OccupancyReports\Pages\ViewOccupancyReport;
use App\Filament\Resources\OccupancyReports\Schemas\OccupancyReportInfolist;
use App\Filament\Resources\OccupancyReports\Tables\OccupancyReportsTable;
use App\Models\OccupancyReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OccupancyReportResource extends Resource
{
    protected static ?string $model = OccupancyReport::class;

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedChartBarSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Parking';

    protected static ?string $navigationLabel = 'Occupancy Reports';

    protected static ?string $modelLabel = 'Occupancy Report';

    protected static ?string $pluralModelLabel = 'Occupancy Reports';

    protected static ?string $recordTitleAttribute = 'id';

    public static function infolist(Schema $schema): Schema
    {
        return OccupancyReportInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OccupancyReportsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOccupancyReports::route('/'),
            'view' => ViewOccupancyReport::route('/{record}'),
        ];
    }
}