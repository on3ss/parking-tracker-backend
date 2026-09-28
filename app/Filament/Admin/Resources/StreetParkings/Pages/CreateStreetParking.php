<?php

namespace App\Filament\Admin\Resources\StreetParkings\Pages;

use App\Filament\Admin\Resources\StreetParkings\StreetParkingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStreetParking extends CreateRecord
{
    protected static string $resource = StreetParkingResource::class;
}
