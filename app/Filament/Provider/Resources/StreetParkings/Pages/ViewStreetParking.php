<?php

namespace App\Filament\Provider\Resources\StreetParkings\Pages;

use App\Filament\Provider\Resources\StreetParkings\StreetParkingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStreetParking extends ViewRecord
{
    protected static string $resource = StreetParkingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
