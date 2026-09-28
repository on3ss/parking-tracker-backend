<?php

namespace App\Filament\Admin\Resources\OccupancyReports\Pages;

use App\Filament\Admin\Resources\OccupancyReports\OccupancyReportResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditOccupancyReport extends EditRecord
{
    protected static string $resource = OccupancyReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
