<?php

namespace App\Filament\Resources\ParkingProviders\Pages;

use App\Filament\Resources\ParkingProviders\ParkingProviderResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewParkingProvider extends ViewRecord
{
    protected static string $resource = ParkingProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
