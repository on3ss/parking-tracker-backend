<?php

use App\Enums\AvailabilityStatus;
use App\Models\Location;
use App\Models\ParkingFacility;
use App\Models\ParkingProvider;
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

it('excludes inactive parking', function () {
    $activeFacility = ParkingFacility::factory()->create([
        'status' => 'ACTIVE',
    ]);

    ParkingFacility::factory()->create([
        'status' => 'INACTIVE',
    ]);

    $activeStreet = StreetParking::factory()->create([
        'status' => 'ACTIVE',
    ]);

    StreetParking::factory()->create([
        'status' => 'INACTIVE',
    ]);

    $this
        ->getJson('/api/v1/parking')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'id' => "facility:{$activeFacility->id}",
        ])
        ->assertJsonFragment([
            'id' => "street:{$activeStreet->id}",
        ]);
});

it('returns an empty result when no parking matches', function () {
    $this
        ->getJson('/api/v1/parking')
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total', 0);
});

it('filters by availability and provider', function () {
    $provider = ParkingProvider::factory()->create();

    $matching = ParkingFacility::factory()->create([
        'status' => 'ACTIVE',
        'parking_provider_id' => $provider->id,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    // Same provider, wrong availability.
    ParkingFacility::factory()->create([
        'status' => 'ACTIVE',
        'parking_provider_id' => $provider->id,
        'availability_status' => AvailabilityStatus::FULL,
    ]);

    // Same availability, wrong provider.
    ParkingFacility::factory()->create([
        'status' => 'ACTIVE',
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    $this
        ->getJson(
            "/api/v1/parking?" .
            "filter[availability]=AVAILABLE&" .
            "filter[provider_id]={$provider->id}",
        )
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath(
            'data.0.id',
            "facility:{$matching->id}",
        );
});

it('paginates across facilities and street parking globally', function () {
    ParkingFacility::factory()->count(3)->create([
        'status' => 'ACTIVE',
    ]);

    StreetParking::factory()->count(3)->create([
        'status' => 'ACTIVE',
    ]);

    $this
        ->getJson('/api/v1/parking?per_page=4&page=1')
        ->assertOk()
        ->assertJsonCount(4, 'data')
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.per_page', 4)
        ->assertJsonPath('meta.total', 6)
        ->assertJsonPath('meta.last_page', 2);

    $this
        ->getJson('/api/v1/parking?per_page=4&page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.current_page', 2);
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
    $provider = ParkingProvider::factory()->create();

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

it('paginates parking results', function () {
    ParkingFacility::factory()
        ->count(5)
        ->create([
            'status' => 'ACTIVE',
        ]);

    $this
        ->getJson('/api/v1/parking?per_page=2&page=1')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.total', 5)
        ->assertJsonPath('meta.last_page', 3);
});

it('returns the requested parking page', function () {
    ParkingFacility::factory()
        ->count(5)
        ->create([
            'status' => 'ACTIVE',
        ]);

    $this
        ->getJson('/api/v1/parking?per_page=2&page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.current_page', 2);
});

it('returns the remaining records on the last page', function () {
    ParkingFacility::factory()
        ->count(5)
        ->create([
            'status' => 'ACTIVE',
        ]);

    $this
        ->getJson('/api/v1/parking?per_page=2&page=3')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.current_page', 3);
});

it('returns an empty page beyond the available results', function () {
    ParkingFacility::factory()
        ->count(2)
        ->create([
            'status' => 'ACTIVE',
        ]);

    $this
        ->getJson('/api/v1/parking?per_page=2&page=2')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});