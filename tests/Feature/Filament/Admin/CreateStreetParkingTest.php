<?php

use App\Enums\ParkingStatus;
use App\Enums\StreetParkingSide;
use App\Enums\StreetParkingType;
use App\Filament\Admin\Resources\StreetParkings\Pages\CreateStreetParking;
use App\Models\ParkingProvider;
use App\Models\StreetParking;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('creates street parking with a location from the nested location data', function () {
    $provider = ParkingProvider::factory()->create();

    Livewire::test(CreateStreetParking::class)
        ->fillForm([
            'name' => 'Police Bazaar Street Parking',
            'parking_provider_id' => $provider->id,
            'road_name' => 'Police Bazaar Road',
            'side' => StreetParkingSide::LEFT->value,
            'parking_type' => StreetParkingType::CURBSIDE->value,
            'status' => ParkingStatus::ACTIVE->value,
            'capacity' => 20,
            'location' => [
                'address_line1' => 'Police Bazaar',
                'locality' => 'Shillong',
                'administrative_area' => 'Meghalaya',
                'postal_code' => '793001',
                'country_code' => 'IN',
                'latitude' => 25.5788,
                'longitude' => 91.8933,
            ],
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => [
                    [91.8930, 25.5785],
                    [91.8936, 25.5790],
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $parking = StreetParking::query()
        ->where('name', 'Police Bazaar Street Parking')
        ->firstOrFail();

    expect($parking->parking_provider_id)->toBe($provider->id)
        ->and($parking->location_id)->not->toBeNull();

    $location = $parking->location;

    expect($location->address_line1)->toBe('Police Bazaar')
        ->and($location->locality)->toBe('Shillong')
        ->and($location->administrative_area)->toBe('Meghalaya')
        ->and($location->postal_code)->toBe('793001')
        ->and($location->country_code)->toBe('IN')
        ->and($location->coordinates->getLatitude())->toBe(25.5788)
        ->and($location->coordinates->getLongitude())->toBe(91.8933);
});

it('defaults the location country to India when country code is omitted', function () {
    $provider = ParkingProvider::factory()->create();

    Livewire::test(CreateStreetParking::class)
        ->fillForm([
            'name' => 'Shillong Street Parking',
            'parking_provider_id' => $provider->id,
            'road_name' => 'Shillong Road',
            'side' => StreetParkingSide::RIGHT->value,
            'parking_type' => StreetParkingType::CURBSIDE->value,
            'status' => ParkingStatus::ACTIVE->value,
            'capacity' => 10,
            'location' => [
                'latitude' => 25.5788,
                'longitude' => 91.8933,
            ],
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => [
                    [91.8930, 25.5785],
                    [91.8936, 25.5790],
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $parking = StreetParking::query()
        ->where('name', 'Shillong Street Parking')
        ->firstOrFail();

    expect($parking->location->country_code)->toBe('IN');
});