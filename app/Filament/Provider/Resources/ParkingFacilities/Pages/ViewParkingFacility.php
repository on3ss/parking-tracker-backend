<?php

namespace App\Filament\Provider\Resources\ParkingFacilities\Pages;

use App\Filament\Actions\Parking\ReportAvailabilityAction;
use App\Filament\Provider\Resources\ParkingFacilities\ParkingFacilityResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewParkingFacility extends ViewRecord
{
    protected static string $resource = ParkingFacilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ReportAvailabilityAction::make(
                tenantScoped: true,
            ),
            EditAction::make(),
        ];
    }
}
