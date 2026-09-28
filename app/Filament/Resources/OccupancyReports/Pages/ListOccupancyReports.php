<?php

namespace App\Filament\Resources\OccupancyReports\Pages;

use App\Filament\Resources\OccupancyReports\OccupancyReportResource;
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
