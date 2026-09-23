<?php

namespace App\Filament\Resources\ParkingFacilities\Pages;

use App\Filament\Resources\ParkingFacilities\ParkingFacilityResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewParkingFacility extends ViewRecord
{
    protected static string $resource = ParkingFacilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
