<?php

use App\Enums\ParkingStatus;
use App\Enums\StreetParkingSide;
use App\Enums\StreetParkingType;
use App\Filament\Resources\StreetParkings\Pages\EditStreetParking;
use App\Models\Location;
use App\Models\ParkingProvider;
use App\Models\StreetParking;
use App\Support\Geo\GeoJson;
use Clickbar\Magellan\Data\Geometries\LineString;
use Clickbar\Magellan\Data\Geometries\Point;
use Livewire\Livewire;

it('fills the form with the existing street parking geometry and location', function () {
    $provider = ParkingProvider::factory()->create();

    $location = Location::factory()->create([
        'address_line1' => 'Police Bazaar',
        'locality' => 'Shillong',
        'administrative_area' => 'Meghalaya',
        'postal_code' => '793001',
        'country_code' => 'IN',
        'coordinates' => Point::makeGeodetic(
            latitude: 25.5788,
            longitude: 91.8933,
        ),
    ]);

    $geometry = LineString::make([
        Point::makeGeodetic(
            latitude: 25.5780,
            longitude: 91.8925,
        ),
        Point::makeGeodetic(
            latitude: 25.5790,
            longitude: 91.8940,
        ),
    ]);

    $parking = StreetParking::factory()->create([
        'parking_provider_id' => $provider->id,
        'location_id' => $location->id,
        'geometry' => $geometry,
    ]);

    Livewire::test(EditStreetParking::class, [
        'record' => $parking->getRouteKey(),
    ])
        ->assertFormSet([
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => [
                    [91.8925, 25.5780],
                    [91.8940, 25.5790],
                ],
            ],
            'location.address_line1' => 'Police Bazaar',
            'location.locality' => 'Shillong',
            'location.administrative_area' => 'Meghalaya',
            'location.postal_code' => '793001',
            'location.country_code' => 'IN',
            'location.latitude' => 25.5788,
            'location.longitude' => 91.8933,
        ]);
});

it('updates street parking through the update action', function () {
    $provider = ParkingProvider::factory()->create();

    $location = Location::factory()->create([
        'address_line1' => 'Old Address',
        'locality' => 'Shillong',
        'country_code' => 'IN',
        'coordinates' => Point::makeGeodetic(
            latitude: 25.5788,
            longitude: 91.8933,
        ),
    ]);

    $parking = StreetParking::factory()->create([
        'parking_provider_id' => $provider->id,
        'location_id' => $location->id,
        'name' => 'Old Street Parking',
        'road_name' => 'Old Road',
        'capacity' => 20,
    ]);

    Livewire::test(EditStreetParking::class, [
        'record' => $parking->getRouteKey(),
    ])
        ->fillForm([
            'name' => 'Updated Street Parking',
            'parking_provider_id' => $provider->id,
            'road_name' => 'Updated Road',
            'side' => StreetParkingSide::LEFT->value,
            'parking_type' => StreetParkingType::CURBSIDE->value,
            'status' => ParkingStatus::ACTIVE->value,
            'capacity' => 30,
            'location' => [
                'address_line1' => 'New Address',
                'address_line2' => 'Building 2',
                'locality' => 'Shillong',
                'administrative_area' => 'Meghalaya',
                'postal_code' => '793001',
                'country_code' => 'IN',
                'latitude' => 25.5800,
                'longitude' => 91.8950,
            ],
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => [
                    [91.8940, 25.5795],
                    [91.8960, 25.5810],
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $parking->refresh();
    $location->refresh();

    expect($parking->name)->toBe('Updated Street Parking')
        ->and($parking->road_name)->toBe('Updated Road')
        ->and($parking->capacity)->toBe(30)
        ->and($parking->location_id)->toBe($location->id)
        ->and($location->address_line1)->toBe('New Address')
        ->and($location->address_line2)->toBe('Building 2')
        ->and($location->locality)->toBe('Shillong')
        ->and($location->administrative_area)->toBe('Meghalaya')
        ->and($location->postal_code)->toBe('793001')
        ->and($location->country_code)->toBe('IN')
        ->and($location->coordinates->getLatitude())->toBe(25.5800)
        ->and($location->coordinates->getLongitude())->toBe(91.8950);

    expect(GeoJson::from($parking->geometry))->toBe([
        'type' => 'LineString',
        'coordinates' => [
            [91.8940, 25.5795],
            [91.8960, 25.5810],
        ],
    ]);
});

it('updates the existing location instead of creating another location', function () {
    $provider = ParkingProvider::factory()->create();

    $location = Location::factory()->create([
        'coordinates' => Point::makeGeodetic(
            latitude: 25.5788,
            longitude: 91.8933,
        ),
    ]);

    $parking = StreetParking::factory()->create([
        'parking_provider_id' => $provider->id,
        'location_id' => $location->id,
    ]);

    $locationCount = Location::query()->count();

    Livewire::test(EditStreetParking::class, [
        'record' => $parking->getRouteKey(),
    ])
        ->fillForm([
            'name' => $parking->name,
            'parking_provider_id' => $provider->id,
            'road_name' => $parking->road_name,
            'side' => $parking->side->value,
            'parking_type' => $parking->parking_type->value,
            'status' => $parking->status->value,
            'capacity' => $parking->capacity,
            'location' => [
                'latitude' => 25.5800,
                'longitude' => 91.8950,
            ],
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => [
                    [91.8940, 25.5795],
                    [91.8960, 25.5810],
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Location::query()->count())
        ->toBe($locationCount)
        ->and($parking->fresh()->location_id)
        ->toBe($location->id);
});