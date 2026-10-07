<?php

use App\Actions\Parking\UpdateStreetParking;
use App\Enums\AvailabilityStatus;
use App\Enums\ParkingSource;
use App\Enums\ParkingStatus;
use App\Enums\StreetParkingSide;
use App\Enums\StreetParkingType;
use App\Models\Location;
use App\Models\OccupancyReport;
use App\Models\StreetParking;
use Clickbar\Magellan\Data\Geometries\Point;

it('updates street parking attributes', function () {
    $parking = StreetParking::factory()->create([
        'name' => 'Old Name',
        'road_name' => 'Old Road',
        'side' => StreetParkingSide::LEFT,
        'parking_type' => StreetParkingType::PARALLEL,
    ]);

    $result = app(UpdateStreetParking::class)->handle(
        $parking,
        [
            'name' => 'New Name',
            'road_name' => 'New Road',
            'side' => StreetParkingSide::RIGHT,
            'parking_type' => StreetParkingType::ANGLED,
        ],
    );

    expect($result->name)->toBe('New Name')
        ->and($result->road_name)->toBe('New Road')
        ->and($result->side)->toBe(StreetParkingSide::RIGHT)
        ->and($result->parking_type)->toBe(StreetParkingType::ANGLED);
});

it('persists street parking status changes', function () {
    $parking = StreetParking::factory()->create([
        'status' => ParkingStatus::ACTIVE,
    ]);

    $result = app(UpdateStreetParking::class)->handle(
        $parking,
        [
            'status' => ParkingStatus::TEMPORARILY_CLOSED,
        ],
    );

    expect($result->status)->toBe(ParkingStatus::TEMPORARILY_CLOSED)
        ->and($parking->fresh()->status)
        ->toBe(ParkingStatus::TEMPORARILY_CLOSED);
});

it('creates a location when location data is supplied and none exists', function () {
    $parking = StreetParking::factory()->create([
        'location_id' => null,
    ]);

    expect($parking->location_id)->toBeNull();

    $result = app(UpdateStreetParking::class)->handle(
        $parking,
        [
            'location' => [
                'address_line1' => 'Police Bazaar',
                'locality' => 'Shillong',
                'administrative_area' => 'Meghalaya',
                'postal_code' => '793001',
                'country_code' => 'IN',
                'latitude' => 25.5788,
                'longitude' => 91.8933,
            ],
        ],
    );

    $location = $result->location()->first();

    expect($location)->not->toBeNull()
        ->and($result->location_id)->toBe($location->id)
        ->and($location->address_line1)->toBe('Police Bazaar')
        ->and($location->locality)->toBe('Shillong')
        ->and($location->country_code)->toBe('IN');
});

it('updates the existing location instead of creating another one', function () {
    $location = Location::factory()->create([
        'address_line1' => 'Old Address',
        'locality' => 'Shillong',
    ]);

    $parking = StreetParking::factory()->create([
        'location_id' => $location->id,
    ]);

    $result = app(UpdateStreetParking::class)->handle(
        $parking,
        [
            'location' => [
                'address_line1' => 'New Address',
                'locality' => 'Shillong',
                'latitude' => 25.5788,
                'longitude' => 91.8933,
            ],
        ],
    );

    expect(Location::query()->count())->toBe(1)
        ->and($result->location_id)->toBe($location->id)
        ->and($location->fresh()->address_line1)->toBe('New Address');
});

it('persists supplied coordinates as a geodetic point', function () {
    $parking = StreetParking::factory()->create([
        'location_id' => null,
    ]);

    $result = app(UpdateStreetParking::class)->handle(
        $parking,
        [
            'location' => [
                'latitude' => 25.5788,
                'longitude' => 91.8933,
            ],
        ],
    );

    $location = $result->location()->firstOrFail();

    $point = $location->coordinates;

    expect($point)->toBeInstanceOf(Point::class)
        ->and($point->getLatitude())->toBe(25.5788)
        ->and($point->getLongitude())->toBe(91.8933);
});

it('preserves the existing location when location data is omitted', function () {
    $location = Location::factory()->create([
        'address_line1' => 'Original Address',
    ]);

    $parking = StreetParking::factory()->create([
        'location_id' => $location->id,
    ]);

    $result = app(UpdateStreetParking::class)->handle(
        $parking,
        [
            'name' => 'Updated Name',
        ],
    );

    expect($result->location_id)->toBe($location->id)
        ->and($result->location->address_line1)->toBe('Original Address')
        ->and(Location::query()->count())->toBe(1);
});

it('does not modify the location when coordinates are incomplete', function () {
    $location = Location::factory()->create([
        'address_line1' => 'Original Address',
    ]);

    $parking = StreetParking::factory()->create([
        'location_id' => $location->id,
    ]);

    app(UpdateStreetParking::class)->handle(
        $parking,
        [
            'location' => [
                'address_line1' => 'Attempted Change',
                'latitude' => 25.5788,
            ],
        ],
    );

    expect($location->fresh()->address_line1)
        ->toBe('Original Address');
});

it('preserves current availability when unrelated fields are updated', function () {
    $parking = StreetParking::factory()->create([
        'capacity' => 10,
        'available_spaces' => 3,
        'availability_status' => AvailabilityStatus::LIMITED,
        'availability_source' => ParkingSource::SENSOR,
    ]);

    $report = OccupancyReport::factory()
        ->forStreetParking($parking)
        ->fromSensor()
        ->create([
            'available_spaces' => 3,
            'occupied_spaces' => 7,
        ]);

    $parking->update([
        'availability_report_id' => $report->id,
        'availability_updated_at' => $report->reported_at,
    ]);

    app(UpdateStreetParking::class)->handle(
        $parking,
        [
            'name' => 'Updated Street Parking',
            'road_name' => 'Updated Road',
        ],
    );

    $current = $parking->fresh();

    expect($current->name)->toBe('Updated Street Parking')
        ->and($current->road_name)->toBe('Updated Road')
        ->and($current->available_spaces)->toBe(3)
        ->and($current->availability_status)->toBe(AvailabilityStatus::LIMITED)
        ->and($current->availability_source)->toBe(ParkingSource::SENSOR)
        ->and($current->availability_report_id)->toBe($report->id);
});
