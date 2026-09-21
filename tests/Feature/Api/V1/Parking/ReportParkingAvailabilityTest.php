<?php

use App\Models\OccupancyReport;
use App\Models\ParkingFacility;

it('reports parking availability through the API', function () {
    $facility = ParkingFacility::factory()
        ->available(50)
        ->create();

    $this
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
            ->count(),
    )->toBe(1);
});