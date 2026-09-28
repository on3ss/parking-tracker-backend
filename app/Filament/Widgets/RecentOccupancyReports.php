<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\OccupancyReports\Tables\OccupancyReportsTable;
use App\Models\OccupancyReport;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentOccupancyReports extends TableWidget
{
    protected static ?string $heading = 'Recent Activity';

    protected static ?string $description =
        'Latest parking availability reports.';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
        return OccupancyReportsTable::configure($table)
            ->query(
                OccupancyReport::query()
                    ->with([
                        'parkingFacility',
                        'streetParking',
                        'user',
                    ])
                    ->latest('reported_at'),
            )
            ->paginated([5])
            ->defaultPaginationPageOption(5);
    }
}