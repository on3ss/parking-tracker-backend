<?php

namespace App\Filament\Actions\Parking;

use App\Actions\Parking\ReportParkingAvailability;
use App\Data\Parking\ReportParkingAvailabilityData;
use App\Enums\ParkingSource;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use App\Support\Parking\ParkingIdentifier;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

final class ReportAvailabilityAction
{
    public static function make(bool $tenantScoped = false): Action
    {
        return Action::make('reportAvailability')
            ->label(__('Report availability'))
            ->icon('heroicon-o-clipboard-document-check')
            ->color('primary')

            ->authorize(
                fn (ParkingFacility|StreetParking $record): bool => ! $tenantScoped
                    || (
                        Filament::getTenant() !== null
                        && $record->parking_provider_id === Filament::getTenant()->getKey()
                    ),
            )

            ->schema([
                TextInput::make('available_spaces')
                    ->label(__('Available spaces'))
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->maxValue(
                        fn (ParkingFacility|StreetParking $record): ?int => $record->capacity
                    )
                    ->helperText(
                        fn (ParkingFacility|StreetParking $record): ?string => $record->capacity === null
                            ? __('Set parking capacity before reporting availability.')
                            : null
                    )
                    ->required(),
            ])

            ->disabled(
                fn (ParkingFacility|StreetParking $record): bool => $record->capacity === null
            )

            ->fillForm(
                fn (ParkingFacility|StreetParking $record): array => [
                    'available_spaces' => $record->available_spaces,
                ],
            )

            ->modalHeading(__('Report parking availability'))
            ->modalSubmitActionLabel(__('Report availability'))

            ->action(function (ParkingFacility|StreetParking $record, array $data): void {
                $result = app(ReportParkingAvailability::class)->execute(
                    new ReportParkingAvailabilityData(
                        parkingIdentifier: ParkingIdentifier::for($record),
                        availableSpaces: (int) $data['available_spaces'],
                    ),
                    source: ParkingSource::OPERATOR,
                    userId: auth()->id(),
                );

                ($result->accepted
                ? Notification::make()->success()
                    ->title(__('Availability reported'))
                    ->body(__('The current parking availability has been updated.'))
                : Notification::make()->warning()
                    ->title(__('Availability recorded, but not applied'))
                    ->body(__('A fresher or higher-trust observation is currently authoritative. Your report has been logged.'))
                )->send();
            });
    }
}
