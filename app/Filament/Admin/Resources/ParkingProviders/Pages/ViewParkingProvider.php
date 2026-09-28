<?php

namespace App\Filament\Admin\Resources\ParkingProviders\Pages;

use App\Filament\Admin\Resources\ParkingProviders\ParkingProviderResource;
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
