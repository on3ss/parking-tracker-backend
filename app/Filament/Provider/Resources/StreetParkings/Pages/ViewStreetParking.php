<?php

namespace App\Filament\Provider\Resources\StreetParkings\Pages;

use App\Filament\Actions\Parking\ReportAvailabilityAction;
use App\Filament\Provider\Resources\StreetParkings\StreetParkingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStreetParking extends ViewRecord
{
    protected static string $resource = StreetParkingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ReportAvailabilityAction::make(
                tenantScoped: true,
            ),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
