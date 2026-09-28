<?php

namespace App\Filament\Admin\Resources\ParkingProviders\Pages;

use App\Filament\Admin\Resources\ParkingProviders\ParkingProviderResource;
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
