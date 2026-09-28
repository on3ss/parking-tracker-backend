<?php

namespace App\Filament\Admin\Resources\OccupancyReports\Pages;

use App\Filament\Admin\Resources\OccupancyReports\OccupancyReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOccupancyReports extends ListRecords
{
    protected static string $resource = OccupancyReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
