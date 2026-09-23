<?php

namespace App\Filament\Resources\ParkingProviders\Pages;

use App\Filament\Resources\ParkingProviders\ParkingProviderResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditParkingProvider extends EditRecord
{
    protected static string $resource = ParkingProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
