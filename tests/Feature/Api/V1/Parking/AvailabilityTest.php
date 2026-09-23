<?php

use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('reports parking availability through the API', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 50,
        'available_spaces' => 40,
    ]);

    $this
        ->actingAs($this->user, 'sanctum')
        ->postJson(
            "/api/v1/parking/facility:{$facility->id}/availability",
            ['available_spaces' => 8],
        )
        ->assertOk()
        ->assertJsonPath('data.id', "facility:{$facility->id}")
        ->assertJsonPath('data.availability.available_spaces', 8);

    $facility->refresh();

    expect($facility->available_spaces)->toBe(8);

    expect(
        OccupancyReport::query()
            ->where('parking_facility_id', $facility->id)
            ->where('user_id', $this->user->id)
            ->count()
    )->toBe(1);
});

it('stores occupied spaces and confidence when supplied', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 50,
        'available_spaces' => 40,
    ]);

    $this
        ->actingAs($this->user, 'sanctum')
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

    expect($report->user_id)->toBe($this->user->id);
    expect($report->available_spaces)->toBe(12);
    expect($report->occupied_spaces)->toBe(38);
    expect((float) $report->confidence)->toBe(0.95);
});

it('reports availability for street parking', function () {
    $street = StreetParking::factory()->create([
        'capacity' => 20,
        'available_spaces' => 5,
    ]);

    $this
        ->actingAs($this->user, 'sanctum')
        ->postJson(
            "/api/v1/parking/street:{$street->id}/availability",
            ['available_spaces' => 12],
        )
        ->assertOk();

    $street->refresh();

    expect($street->available_spaces)->toBe(12);
});

it('requires authentication to report availability', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 50,
        'available_spaces' => 40,
    ]);

    $this
        ->postJson(
            "/api/v1/parking/facility:{$facility->id}/availability",
            ['available_spaces' => 8],
        )
        ->assertUnauthorized();

    expect(
        OccupancyReport::query()
            ->where('parking_facility_id', $facility->id)
            ->exists()
    )->toBeFalse();
});

it('requires available spaces', function () {
    $facility = ParkingFacility::factory()->create(['capacity' => 50]);

    $this
        ->actingAs($this->user, 'sanctum')
        ->postJson(
            "/api/v1/parking/facility:{$facility->id}/availability",
            [],
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['available_spaces']);
});

it('rejects negative available spaces', function () {
    $facility = ParkingFacility::factory()->create(['capacity' => 50]);

    $this
        ->actingAs($this->user, 'sanctum')
        ->postJson(
            "/api/v1/parking/facility:{$facility->id}/availability",
            ['available_spaces' => -1],
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['available_spaces']);
});

it('returns 404 for an invalid parking identifier', function () {
    $this
        ->actingAs($this->user, 'sanctum')
        ->postJson(
            '/api/v1/parking/facility:999999/availability',
            ['available_spaces' => 5],
        )
        ->assertNotFound();
});

it('returns 404 for an invalid parking identifier format', function () {
    $this
        ->actingAs($this->user, 'sanctum')
        ->postJson(
            '/api/v1/parking/not-a-parking/availability',
            ['available_spaces' => 5],
        )
        ->assertNotFound();
});