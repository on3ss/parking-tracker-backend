<?php

use App\Enums\AvailabilityStatus;
use App\Enums\ParkingSource;
use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('reports parking availability through the API', function () {
    $user = User::factory()->create();

    $facility = ParkingFacility::factory()->create([
        'capacity' => 50,
        'available_spaces' => 40,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    $this
        ->actingAs($user, 'sanctum')
        ->postJson(
            "/api/v1/parking/facility:{$facility->id}/availability",
            [
                'available_spaces' => 8,
            ],
        )
        ->assertOk()
        ->assertJsonPath(
            'data.id',
            "facility:{$facility->id}",
        )
        ->assertJsonPath(
            'data.availability.available_spaces',
            8,
        );

    $facility->refresh();

    expect($facility->available_spaces)->toBe(8);

    expect(
        OccupancyReport::query()
            ->where('parking_facility_id', $facility->id)
            ->where('user_id', $user->id)
            ->count()
    )->toBe(1);
});

it('requires authentication to report availability', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 50,
        'available_spaces' => 40,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    $this
        ->postJson(
            "/api/v1/parking/facility:{$facility->id}/availability",
            [
                'available_spaces' => 8,
            ],
        )
        ->assertUnauthorized();

    expect(
        OccupancyReport::query()
            ->where('parking_facility_id', $facility->id)
            ->exists()
    )->toBeFalse();
});

it('requires available spaces', function () {
    $user = User::factory()->create();

    $facility = ParkingFacility::factory()->create([
        'capacity' => 50,
    ]);

    $this
        ->actingAs($user, 'sanctum')
        ->postJson(
            "/api/v1/parking/facility:{$facility->id}/availability",
            [],
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'available_spaces',
        ]);
});

it('rejects negative available spaces', function () {
    $user = User::factory()->create();

    $facility = ParkingFacility::factory()->create([
        'capacity' => 50,
    ]);

    $this
        ->actingAs($user, 'sanctum')
        ->postJson(
            "/api/v1/parking/facility:{$facility->id}/availability",
            [
                'available_spaces' => -1,
            ],
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'available_spaces',
        ]);
});

it('stores occupied spaces and confidence when supplied', function () {
    $user = User::factory()->create();

    $facility = ParkingFacility::factory()->create([
        'capacity' => 50,
        'available_spaces' => 40,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    $this
        ->actingAs($user, 'sanctum')
        ->postJson(
            "/api/v1/parking/facility:{$facility->id}/availability",
            [
                'available_spaces' => 12,
                'occupied_spaces' => 38,
                'confidence' => 0.95,
            ],
        )
        ->assertOk();

    $report = OccupancyReport::query()
        ->where('parking_facility_id', $facility->id)
        ->latest('id')
        ->firstOrFail();

    expect($report->user_id)->toBe($user->id);
    expect($report->available_spaces)->toBe(12);
    expect($report->occupied_spaces)->toBe(38);
    expect((float) $report->confidence)->toBe(0.95);
    expect($report->source)->toBe(ParkingSource::USER);
});

it('sets availability to FULL when no spaces are available', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 50,
        'available_spaces' => 20,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    $this
        ->actingAs($this->user)
        ->postJson(
            "/api/v1/parking/facility:{$facility->id}/availability",
            [
                'available_spaces' => 0,
            ],
        )
        ->assertOk();

    expect($facility->refresh())
        ->available_spaces->toBe(0)
        ->availability_status->toBe(AvailabilityStatus::FULL);
});

it('sets availability to LIMITED when availability is at or below 20 percent', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 50,
        'available_spaces' => 20,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    $this
        ->actingAs($this->user)
        ->postJson(
            "/api/v1/parking/facility:{$facility->id}/availability",
            [
                'available_spaces' => 10,
            ],
        )
        ->assertOk();

    expect($facility->refresh())
        ->available_spaces->toBe(10)
        ->availability_status->toBe(AvailabilityStatus::LIMITED);
});

it('sets availability to AVAILABLE when more than 20 percent is available', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 50,
        'available_spaces' => 10,
        'availability_status' => AvailabilityStatus::LIMITED,
    ]);

    $this
        ->actingAs($this->user)
        ->postJson(
            "/api/v1/parking/facility:{$facility->id}/availability",
            [
                'available_spaces' => 11,
            ],
        )
        ->assertOk();

    expect($facility->refresh())
        ->available_spaces->toBe(11)
        ->availability_status->toBe(AvailabilityStatus::AVAILABLE);
});

it('sets availability to AVAILABLE when all spaces are available', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 50,
        'available_spaces' => 0,
        'availability_status' => AvailabilityStatus::FULL,
    ]);

    $this
        ->actingAs($this->user)
        ->postJson(
            "/api/v1/parking/facility:{$facility->id}/availability",
            [
                'available_spaces' => 50,
            ],
        )
        ->assertOk();

    expect($facility->refresh())
        ->available_spaces->toBe(50)
        ->availability_status->toBe(AvailabilityStatus::AVAILABLE);
});

it('derives occupied spaces when they are not supplied', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 50,
        'available_spaces' => 20,
    ]);

    $this
        ->actingAs($this->user)
        ->postJson(
            "/api/v1/parking/facility:{$facility->id}/availability",
            [
                'available_spaces' => 15,
            ],
        )
        ->assertOk();

    $this->assertDatabaseHas('occupancy_reports', [
        'parking_facility_id' => $facility->id,
        'available_spaces' => 15,
        'occupied_spaces' => 35,
    ]);
});

it('rejects available spaces above capacity', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 50,
    ]);

    $this
        ->actingAs($this->user)
        ->postJson(
            "/api/v1/parking/facility:{$facility->id}/availability",
            [
                'available_spaces' => 51,
            ],
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'available_spaces',
        ]);
});

it('rejects occupied spaces above capacity', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 50,
    ]);

    $this
        ->actingAs($this->user)
        ->postJson(
            "/api/v1/parking/facility:{$facility->id}/availability",
            [
                'available_spaces' => 10,
                'occupied_spaces' => 51,
            ],
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'occupied_spaces',
        ]);
});

it('rejects occupied and available spaces exceeding capacity together', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 50,
    ]);

    $this
        ->actingAs($this->user)
        ->postJson(
            "/api/v1/parking/facility:{$facility->id}/availability",
            [
                'available_spaces' => 30,
                'occupied_spaces' => 25,
            ],
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'available_spaces',
        ]);
});

it('reports availability for street parking', function () {
    $street = StreetParking::factory()->create([
        'capacity' => 20,
        'available_spaces' => 5,
        'availability_status' => AvailabilityStatus::LIMITED,
    ]);

    $this
        ->actingAs($this->user)
        ->postJson(
            "/api/v1/parking/street:{$street->id}/availability",
            [
                'available_spaces' => 12,
            ],
        )
        ->assertOk();

    $street->refresh();

    expect($street)
        ->available_spaces->toBe(12)
        ->availability_status->toBe(AvailabilityStatus::AVAILABLE);

    $this->assertDatabaseHas('occupancy_reports', [
        'street_parking_id' => $street->id,
        'parking_facility_id' => null,
        'available_spaces' => 12,
        'occupied_spaces' => 8,
        'source' => ParkingSource::USER->value,
        'user_id' => $this->user->id,
    ]);
});

it('creates a new occupancy report for each availability report', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 50,
        'available_spaces' => 20,
    ]);

    $this
        ->actingAs($this->user)
        ->postJson(
            "/api/v1/parking/facility:{$facility->id}/availability",
            ['available_spaces' => 15],
        )
        ->assertOk();

    $this
        ->actingAs($this->user)
        ->postJson(
            "/api/v1/parking/facility:{$facility->id}/availability",
            ['available_spaces' => 10],
        )
        ->assertOk();

    $this->assertDatabaseCount('occupancy_reports', 2);

    $this->assertDatabaseHas('occupancy_reports', [
        'parking_facility_id' => $facility->id,
        'available_spaces' => 15,
        'occupied_spaces' => 35,
    ]);

    $this->assertDatabaseHas('occupancy_reports', [
        'parking_facility_id' => $facility->id,
        'available_spaces' => 10,
        'occupied_spaces' => 40,
    ]);

    expect($facility->refresh()->available_spaces)
        ->toBe(10);
});

it('records when availability was reported', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 50,
    ]);

    $this
        ->actingAs($this->user)
        ->postJson(
            "/api/v1/parking/facility:{$facility->id}/availability",
            [
                'available_spaces' => 15,
            ],
        )
        ->assertOk();

    $report = OccupancyReport::query()->latest('id')->first();

    expect($report)
        ->reported_at->not->toBeNull();
});

it('returns 404 for an invalid parking identifier', function () {
    $this
        ->actingAs($this->user)
        ->postJson(
            '/api/v1/parking/facility:999999/availability',
            [
                'available_spaces' => 5,
            ],
        )
        ->assertNotFound();
});

it('returns 404 for an invalid parking identifier format', function () {
    $this
        ->actingAs($this->user)
        ->postJson(
            '/api/v1/parking/not-a-parking/availability',
            [
                'available_spaces' => 5,
            ],
        )
        ->assertNotFound();
});
