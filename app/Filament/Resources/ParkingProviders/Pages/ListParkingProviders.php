<?php

namespace App\Filament\Resources\ParkingProviders\Pages;

use App\Filament\Resources\ParkingProviders\ParkingProviderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListParkingProviders extends ListRecords
{
    protected static string $resource = ParkingProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
