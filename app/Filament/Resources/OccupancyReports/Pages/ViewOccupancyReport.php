<?php

namespace App\Filament\Resources\OccupancyReports\Pages;

use App\Filament\Resources\OccupancyReports\OccupancyReportResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOccupancyReport extends ViewRecord
{
    protected static string $resource = OccupancyReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
