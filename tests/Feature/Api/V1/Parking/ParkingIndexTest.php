<?php

use App\Enums\AvailabilityStatus;
use App\Models\Location;
use App\Models\ParkingFacility;
use App\Models\ParkingProvider;
use App\Models\StreetParking;
use Clickbar\Magellan\Data\Geometries\LineString;
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
            '/api/v1/parking?'.
            'filter[availability]=AVAILABLE&'.
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

it('sorts merged parking results by name', function () {
    $facility = ParkingFacility::factory()->create([
        'status' => 'ACTIVE',
        'name' => 'Charlie Parking',
    ]);

    $street = StreetParking::factory()->create([
        'status' => 'ACTIVE',
        'name' => 'Alpha Street Parking',
    ]);

    $this
        ->getJson('/api/v1/parking?sort=name')
        ->assertOk()
        ->assertJsonPath('data.0.id', "street:{$street->id}")
        ->assertJsonPath('data.1.id', "facility:{$facility->id}");
});

it('sorts merged parking results by name descending', function () {
    $facility = ParkingFacility::factory()->create([
        'status' => 'ACTIVE',
        'name' => 'Charlie Parking',
    ]);

    $street = StreetParking::factory()->create([
        'status' => 'ACTIVE',
        'name' => 'Alpha Street Parking',
    ]);

    $this
        ->getJson('/api/v1/parking?sort=-name')
        ->assertOk()
        ->assertJsonPath('data.0.id', "facility:{$facility->id}")
        ->assertJsonPath('data.1.id', "street:{$street->id}");
});

it('sorts merged parking results by capacity', function () {
    $small = ParkingFacility::factory()->create([
        'status' => 'ACTIVE',
        'name' => 'Small Parking',
        'capacity' => 20,
    ]);

    $large = StreetParking::factory()->create([
        'status' => 'ACTIVE',
        'name' => 'Large Parking',
        'capacity' => 100,
    ]);

    $this
        ->getJson('/api/v1/parking?sort=capacity')
        ->assertOk()
        ->assertJsonPath('data.0.id', "facility:{$small->id}")
        ->assertJsonPath('data.1.id', "street:{$large->id}");
});

it('sorts merged parking results by capacity descending', function () {
    $small = ParkingFacility::factory()->create([
        'status' => 'ACTIVE',
        'capacity' => 20,
    ]);

    $large = StreetParking::factory()->create([
        'status' => 'ACTIVE',
        'capacity' => 100,
    ]);

    $this
        ->getJson('/api/v1/parking?sort=-capacity')
        ->assertOk()
        ->assertJsonPath('data.0.id', "street:{$large->id}")
        ->assertJsonPath('data.1.id', "facility:{$small->id}");
});

it('sorts merged parking results by available spaces', function () {
    $facility = ParkingFacility::factory()->create([
        'status' => 'ACTIVE',
        'available_spaces' => 5,
    ]);

    $street = StreetParking::factory()->create([
        'status' => 'ACTIVE',
        'available_spaces' => 20,
    ]);

    $this
        ->getJson('/api/v1/parking?sort=available_spaces')
        ->assertOk()
        ->assertJsonPath('data.0.id', "facility:{$facility->id}")
        ->assertJsonPath('data.1.id', "street:{$street->id}");
});

it('sorts merged parking results by available spaces descending', function () {
    $facility = ParkingFacility::factory()->create([
        'status' => 'ACTIVE',
        'available_spaces' => 5,
    ]);

    $street = StreetParking::factory()->create([
        'status' => 'ACTIVE',
        'available_spaces' => 20,
    ]);

    $this
        ->getJson('/api/v1/parking?sort=-available_spaces')
        ->assertOk()
        ->assertJsonPath('data.0.id', "street:{$street->id}")
        ->assertJsonPath('data.1.id', "facility:{$facility->id}");
});

it('sorts parking by distance when coordinates are provided', function () {
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
            latitude: 25.5900,
            longitude: 91.9000,
        ),
    ]);

    $near = ParkingFacility::factory()->create([
        'status' => 'ACTIVE',
        'location_id' => $nearLocation->id,
    ]);

    $far = ParkingFacility::factory()->create([
        'status' => 'ACTIVE',
        'location_id' => $farLocation->id,
    ]);

    $this
        ->getJson(
            '/api/v1/parking?'.
            'latitude=25.5779&'.
            'longitude=91.8837',
        )
        ->assertOk()
        ->assertJsonPath(
            'data.0.id',
            "facility:{$near->id}",
        )
        ->assertJsonPath(
            'data.1.id',
            "facility:{$far->id}",
        );
});

it('sorts facilities and street parking by distance', function () {
    $nearLocation = Location::factory()->create([
        'coordinates' => Point::makeGeodetic(
            latitude: 25.5780,
            longitude: 91.8838,
        ),
    ]);

    $farLocation = Location::factory()->create([
        'coordinates' => Point::makeGeodetic(
            latitude: 25.5900,
            longitude: 91.9000,
        ),
    ]);

    $near = ParkingFacility::factory()->create([
        'status' => 'ACTIVE',
        'location_id' => $nearLocation->id,
    ]);

    $far = StreetParking::factory()->create([
        'status' => 'ACTIVE',
        'location_id' => $farLocation->id,
    ]);

    $this
        ->getJson(
            '/api/v1/parking?'.
            'latitude=25.5779&'.
            'longitude=91.8837&'.
            'sort=distance',
        )
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath(
            'data.0.id',
            "facility:{$near->id}",
        )
        ->assertJsonPath(
            'data.1.id',
            "street:{$far->id}",
        );
});

it('sorts facilities and street parking by distance descending', function () {
    $nearLocation = Location::factory()->create([
        'coordinates' => Point::makeGeodetic(
            latitude: 25.5780,
            longitude: 91.8838,
        ),
    ]);

    $farLocation = Location::factory()->create([
        'coordinates' => Point::makeGeodetic(
            latitude: 25.5900,
            longitude: 91.9000,
        ),
    ]);

    $near = ParkingFacility::factory()->create([
        'status' => 'ACTIVE',
        'location_id' => $nearLocation->id,
    ]);

    $far = StreetParking::factory()->create([
        'status' => 'ACTIVE',
        'location_id' => $farLocation->id,
    ]);

    $this
        ->getJson(
            '/api/v1/parking?'.
            'latitude=25.5779&'.
            'longitude=91.8837&'.
            'sort=-distance',
        )
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath(
            'data.0.id',
            "street:{$far->id}",
        )
        ->assertJsonPath(
            'data.1.id',
            "facility:{$near->id}",
        );
});

it('filters facilities and street parking by radius', function () {
    $nearFacilityLocation = Location::factory()->create([
        'coordinates' => Point::makeGeodetic(
            latitude: 25.5780,
            longitude: 91.8838,
        ),
    ]);

    $farFacilityLocation = Location::factory()->create([
        'coordinates' => Point::makeGeodetic(
            latitude: 25.6000,
            longitude: 91.9200,
        ),
    ]);

    $nearStreetLocation = Location::factory()->create([
        'coordinates' => Point::makeGeodetic(
            latitude: 25.5790,
            longitude: 91.8845,
        ),
    ]);

    $nearFacility = ParkingFacility::factory()->create([
        'status' => 'ACTIVE',
        'location_id' => $nearFacilityLocation->id,
    ]);

    $nearStreet = StreetParking::factory()->create([
        'status' => 'ACTIVE',
        'location_id' => $nearStreetLocation->id,
        'geometry' => LineString::make([
            Point::makeGeodetic(
                latitude: 25.5788,
                longitude: 91.8843,
            ),
            Point::makeGeodetic(
                latitude: 25.5792,
                longitude: 91.8847,
            ),
        ]),
    ]);

    $farFacility = ParkingFacility::factory()->create([
        'status' => 'ACTIVE',
        'location_id' => $farFacilityLocation->id,
    ]);

    $this
        ->getJson(
            '/api/v1/parking?'.
            'latitude=25.5779&'.
            'longitude=91.8837&'.
            'radius=2000',
        )
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'id' => "facility:{$nearFacility->id}",
        ])
        ->assertJsonFragment([
            'id' => "street:{$nearStreet->id}",
        ])
        ->assertJsonMissing([
            'id' => "facility:{$farFacility->id}",
        ]);
});

it('searches normally without coordinates', function () {
    $facility = ParkingFacility::factory()->create([
        'status' => 'ACTIVE',
    ]);

    $street = StreetParking::factory()->create([
        'status' => 'ACTIVE',
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

it('rejects a radius without coordinates', function () {
    $this
        ->getJson('/api/v1/parking?radius=2000')
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'radius',
        ]);
});

it('rejects a radius with only latitude', function () {
    $this
        ->getJson(
            '/api/v1/parking?latitude=25.5779&radius=2000',
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'radius',
        ]);
});

it('rejects a radius with only longitude', function () {
    $this
        ->getJson(
            '/api/v1/parking?longitude=91.8837&radius=2000',
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'radius',
        ]);
});

it('rejects latitude below the valid range', function () {
    $this
        ->getJson('/api/v1/parking?latitude=-91')
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'latitude',
        ]);
});

it('rejects latitude above the valid range', function () {
    $this
        ->getJson('/api/v1/parking?latitude=91')
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'latitude',
        ]);
});

it('rejects longitude below the valid range', function () {
    $this
        ->getJson('/api/v1/parking?longitude=-181')
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'longitude',
        ]);
});

it('rejects longitude above the valid range', function () {
    $this
        ->getJson('/api/v1/parking?longitude=181')
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'longitude',
        ]);
});

it('accepts latitude and longitude boundary values', function () {
    $this
        ->getJson(
            '/api/v1/parking?'.
            'latitude=90&'.
            'longitude=180',
        )
        ->assertOk();

    $this
        ->getJson(
            '/api/v1/parking?'.
            'latitude=-90&'.
            'longitude=-180',
        )
        ->assertOk();
});

it('rejects page zero', function () {
    $this
        ->getJson('/api/v1/parking?page=0')
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'page',
        ]);
});

it('rejects a negative page', function () {
    $this
        ->getJson('/api/v1/parking?page=-1')
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'page',
        ]);
});

it('rejects per page zero', function () {
    $this
        ->getJson('/api/v1/parking?per_page=0')
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'per_page',
        ]);
});

it('rejects per page above the maximum', function () {
    $this
        ->getJson('/api/v1/parking?per_page=101')
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'per_page',
        ]);
});

it('accepts the maximum per page', function () {
    $this
        ->getJson('/api/v1/parking?per_page=100')
        ->assertOk();
});

it('rejects a zero radius', function () {
    $this
        ->getJson(
            '/api/v1/parking?'.
            'latitude=25.5779&'.
            'longitude=91.8837&'.
            'radius=0',
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'radius',
        ]);
});

it('rejects a radius above the maximum', function () {
    $this
        ->getJson(
            '/api/v1/parking?'.
            'latitude=25.5779&'.
            'longitude=91.8837&'.
            'radius=50001',
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'radius',
        ]);
});

it('accepts the maximum radius', function () {
    $this
        ->getJson(
            '/api/v1/parking?'.
            'latitude=25.5779&'.
            'longitude=91.8837&'.
            'radius=50000',
        )
        ->assertOk();
});

it('rejects an invalid availability filter', function () {
    $this
        ->getJson(
            '/api/v1/parking?filter[availability]=INVALID',
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'filter.availability',
        ]);
});

it('rejects a nonexistent provider', function () {
    $this
        ->getJson(
            '/api/v1/parking?filter[provider_id]=999999',
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'filter.provider_id',
        ]);
});

it('rejects a non-numeric provider id', function () {
    $this
        ->getJson(
            '/api/v1/parking?filter[provider_id]=abc',
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'filter.provider_id',
        ]);
});

it('rejects a non-numeric page', function () {
    $this
        ->getJson('/api/v1/parking?page=abc')
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'page',
        ]);
});

it('rejects a non-numeric per page', function () {
    $this
        ->getJson('/api/v1/parking?per_page=abc')
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'per_page',
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
            '/api/v1/parking?'.
            'latitude=25.5779&'.
            'longitude=91.8837&'.
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

it('returns the parking resource structure', function () {
    $provider = ParkingProvider::factory()->create();

    $facility = ParkingFacility::factory()->create([
        'status' => 'ACTIVE',
        'parking_provider_id' => $provider->id,
        'capacity' => 100,
        'available_spaces' => 25,
        'availability_status' => AvailabilityStatus::LIMITED,
    ]);

    $this
        ->getJson('/api/v1/parking')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'name',
                    'distance',
                    'provider' => [
                        'id',
                        'name',
                        'type',
                    ],
                    'location' => [
                        'address_line1',
                        'locality',
                        'administrative_area',
                        'postal_code',
                        'country_code',
                        'coordinates',
                    ],
                    'capacity',
                    'availability' => [
                        'status',
                        'available_spaces',
                        'updated_at',
                    ],
                ],
            ],
            'meta',
        ])
        ->assertJsonPath(
            'data.0.id',
            "facility:{$facility->id}",
        );
});

it('returns null provider when parking has no provider', function () {
    $street = StreetParking::factory()->create([
        'status' => 'ACTIVE',
        'parking_provider_id' => null,
    ]);

    $this
        ->getJson('/api/v1/parking?type=street')
        ->assertOk()
        ->assertJsonPath('data.0.id', "street:{$street->id}")
        ->assertJsonPath('data.0.provider', null);
});
