<?php

use App\Enums\ParkingFacilityType;
use App\Enums\ParkingStatus;
use App\Filament\Resources\ParkingFacilities\Pages\EditParkingFacility;
use App\Models\Location;
use App\Models\ParkingFacility;
use Clickbar\Magellan\Data\Geometries\Point;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\Support\ProviderTestContext;

beforeEach(function (): void {
    Filament::setCurrentPanel('provider');
});

it('fills the location form from the existing parking facility location', function () {
    $context = ProviderTestContext::create()->actingAsUser();

    $location = Location::factory()->create([
        'address_line1' => 'Police Bazaar',
        'address_line2' => 'Main Road',
        'locality' => 'Shillong',
        'administrative_area' => 'Meghalaya',
        'postal_code' => '793001',
        'country_code' => 'IN',
        'coordinates' => Point::makeGeodetic(
            latitude: 25.5788,
            longitude: 91.8933,
        ),
    ]);

    $facility = ParkingFacility::factory()->create([
        'parking_provider_id' => $context->provider->getKey(),
        'location_id' => $location->getKey(),
    ]);

    Livewire::test(EditParkingFacility::class, [
        'record' => $facility->getRouteKey(),
    ])
        ->assertSuccessful()
        ->assertSchemaStateSet([
            'location.address_line1' => 'Police Bazaar',
            'location.address_line2' => 'Main Road',
            'location.locality' => 'Shillong',
            'location.administrative_area' => 'Meghalaya',
            'location.postal_code' => '793001',
            'location.country_code' => 'IN',
            'location.latitude' => 25.5788,
            'location.longitude' => 91.8933,
        ]);
});

it('updates the parking facility and its existing location', function () {
    $context = ProviderTestContext::create()->actingAsUser();

    $location = Location::factory()->create([
        'address_line1' => 'Old Address',
        'locality' => 'Old Locality',
        'country_code' => 'IN',
        'coordinates' => Point::makeGeodetic(
            latitude: 25.5788,
            longitude: 91.8933,
        ),
    ]);

    $facility = ParkingFacility::factory()->create([
        'parking_provider_id' => $context->provider->getKey(),
        'location_id' => $location->getKey(),
        'name' => 'Old Facility Name',
        'capacity' => 20,
    ]);

    Livewire::test(EditParkingFacility::class, [
        'record' => $facility->getRouteKey(),
    ])
        ->fillForm([
            'name' => 'Updated Facility Name',
            'type' => ParkingFacilityType::PUBLIC->value,
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
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $facility->refresh();
    $location->refresh();

    expect($facility->name)->toBe('Updated Facility Name')
        ->and($facility->capacity)->toBe(30)
        ->and($facility->location_id)->toBe($location->getKey())
        ->and($location->address_line1)->toBe('New Address')
        ->and($location->address_line2)->toBe('Building 2')
        ->and($location->locality)->toBe('Shillong')
        ->and($location->administrative_area)->toBe('Meghalaya')
        ->and($location->postal_code)->toBe('793001')
        ->and($location->country_code)->toBe('IN')
        ->and($location->coordinates->getLatitude())->toBe(25.5800)
        ->and($location->coordinates->getLongitude())->toBe(91.8950);
});

it('defaults the location country to India when updating without a country code', function () {
    $context = ProviderTestContext::create()->actingAsUser();

    $location = Location::factory()->create([
        'country_code' => 'IN',
        'coordinates' => Point::makeGeodetic(
            latitude: 25.5788,
            longitude: 91.8933,
        ),
    ]);

    $facility = ParkingFacility::factory()->create([
        'parking_provider_id' => $context->provider->getKey(),
        'location_id' => $location->getKey(),
    ]);

    Livewire::test(EditParkingFacility::class, [
        'record' => $facility->getRouteKey(),
    ])
        ->fillForm([
            'name' => $facility->name,
            'type' => $facility->type->value,
            'status' => $facility->status->value,
            'capacity' => $facility->capacity,
            'location' => [
                'address_line1' => 'Updated Address',
                'latitude' => 25.5800,
                'longitude' => 91.8950,
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($location->fresh()->country_code)->toBe('IN');
});
