<?php

namespace App\Filament\Admin\Resources\ParkingFacilities\Pages;

use App\Filament\Admin\Resources\ParkingFacilities\ParkingFacilityResource;
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
