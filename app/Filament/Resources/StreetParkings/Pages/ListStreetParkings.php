<?php

namespace App\Filament\Resources\StreetParkings\Pages;

use App\Filament\Resources\StreetParkings\StreetParkingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStreetParkings extends ListRecords
{
    protected static string $resource = StreetParkingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
