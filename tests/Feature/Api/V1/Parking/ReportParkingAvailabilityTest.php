<?php

use App\Enums\AvailabilityStatus;
use App\Enums\ParkingSource;
use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Models\User;

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