<?php

use App\Enums\AvailabilityStatus;
use App\Models\Location;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Clickbar\Magellan\Data\Geometries\Point;

it('returns facilities and street parking', function () {
    $facility = ParkingFacility::factory()->create([
        'status' => 'ACTIVE',
        'capacity' => 50,
        'available_spaces' => 20,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    $street = StreetParking::factory()->create([
        'status' => 'ACTIVE',
        'capacity' => 20,
        'available_spaces' => 5,
        'availability_status' => AvailabilityStatus::LIMITED,
    ]);

    $this
        ->getJson('/api/v1/parking')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'id' => "facility:{$facility->id}",
        ])
        ->assertJsonFragment([
            'id' => "street:{$street->id}",
        ]);
});

it('filters by parking type', function () {
    $facility = ParkingFacility::factory()->create([
        'status' => 'ACTIVE',
    ]);

    StreetParking::factory()->create([
        'status' => 'ACTIVE',
    ]);

    $this
        ->getJson('/api/v1/parking?type=facility')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath(
            'data.0.id',
            "facility:{$facility->id}",
        );
});

it('filters street parking by type', function () {
    $facility = ParkingFacility::factory()->create([
        'status' => 'ACTIVE',
    ]);

    $street = StreetParking::factory()->create([
        'status' => 'ACTIVE',
    ]);

    $this
        ->getJson('/api/v1/parking?type=street')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath(
            'data.0.id',
            "street:{$street->id}",
        );
});

it('filters by availability', function () {
    $available = ParkingFacility::factory()->create([
        'status' => 'ACTIVE',
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    ParkingFacility::factory()->create([
        'status' => 'ACTIVE',
        'availability_status' => AvailabilityStatus::FULL,
    ]);

    $this
        ->getJson(
            '/api/v1/parking?filter[availability]=AVAILABLE',
        )
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath(
            'data.0.id',
            "facility:{$available->id}",
        );
});

it('filters by provider', function () {
    $provider = \App\Models\ParkingProvider::factory()->create();

    $parking = ParkingFacility::factory()->create([
        'status' => 'ACTIVE',
        'parking_provider_id' => $provider->id,
    ]);

    ParkingFacility::factory()->create([
        'status' => 'ACTIVE',
    ]);

    $this
        ->getJson(
            "/api/v1/parking?filter[provider_id]={$provider->id}",
        )
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath(
            'data.0.id',
            "facility:{$parking->id}",
        );
});

it('rejects an invalid sort', function () {
    $this
        ->getJson('/api/v1/parking?sort=secret_column')
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'sort',
        ]);
});

it('rejects an invalid parking type', function () {
    $this
        ->getJson('/api/v1/parking?type=invalid')
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'type',
        ]);
});

it('filters parking by radius', function () {
    $origin = Point::makeGeodetic(
        latitude: 25.5779,
        longitude: 91.8837,
    );

    $nearLocation = Location::factory()->create([
        'coordinates' => Point::makeGeodetic(
            latitude: 25.5780,
            longitude: 91.8838,
        ),
    ]);

    $farLocation = Location::factory()->create([
        'coordinates' => Point::makeGeodetic(
            latitude: 25.6000,
            longitude: 91.9200,
        ),
    ]);

    $near = ParkingFacility::factory()->create([
        'status' => 'ACTIVE',
        'location_id' => $nearLocation->id,
    ]);

    ParkingFacility::factory()->create([
        'status' => 'ACTIVE',
        'location_id' => $farLocation->id,
    ]);

    $this
        ->getJson(
            '/api/v1/parking?' .
            'latitude=25.5779&' .
            'longitude=91.8837&' .
            'radius=2000',
        )
        ->assertOk()
        ->assertJsonFragment([
            'id' => "facility:{$near->id}",
        ]);
});