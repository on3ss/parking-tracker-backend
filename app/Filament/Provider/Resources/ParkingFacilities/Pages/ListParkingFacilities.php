<?php

namespace App\Filament\Provider\Resources\ParkingFacilities\Pages;

use App\Filament\Provider\Resources\ParkingFacilities\ParkingFacilityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListParkingFacilities extends ListRecords
{
    protected static string $resource = ParkingFacilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
