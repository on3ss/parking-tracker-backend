<?php

namespace App\Filament\Admin\Resources\OccupancyReports\Pages;

use App\Filament\Admin\Resources\OccupancyReports\OccupancyReportResource;
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
