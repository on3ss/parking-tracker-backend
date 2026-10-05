<?php

use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Models\StreetParking;

it('returns facility availability history', function () {
    $facility = ParkingFacility::factory()->create();

    OccupancyReport::factory()
        ->count(3)
        ->forFacility($facility)
        ->create();

    $this
        ->getJson(
            "/api/v1/parking/facility:{$facility->id}/availability/history",
        )
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'source',
                    'occupied_spaces',
                    'available_spaces',
                    'reported_at',
                ],
            ],
            'meta',
        ])
        ->assertJsonCount(3, 'data');
});

it('returns street parking availability history', function () {
    $street = StreetParking::factory()->create();

    OccupancyReport::factory()
        ->count(3)
        ->forStreetParking($street)
        ->create();

    $this
        ->getJson(
            "/api/v1/parking/street:{$street->id}/availability/history",
        )
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('returns an empty history when no reports exist', function () {
    $facility = ParkingFacility::factory()->create();

    $this
        ->getJson(
            "/api/v1/parking/facility:{$facility->id}/availability/history",
        )
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total', 0);
});

it('does not expose another parking records history', function () {
    $facility = ParkingFacility::factory()->create();
    $otherFacility = ParkingFacility::factory()->create();

    OccupancyReport::factory()
        ->forFacility($facility)
        ->create(['available_spaces' => 10]);

    OccupancyReport::factory()
        ->forFacility($otherFacility)
        ->create(['available_spaces' => 99]);

    $this
        ->getJson(
            "/api/v1/parking/facility:{$facility->id}/availability/history",
        )
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.available_spaces', 10);
});

it('paginates availability history', function () {
    $facility = ParkingFacility::factory()->create();

    OccupancyReport::factory()
        ->count(5)
        ->forFacility($facility)
        ->create();

    $this
        ->getJson(
            "/api/v1/parking/facility:{$facility->id}/availability/history"
            . '?per_page=2&page=2',
        )
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.current_page', 2)
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.total', 5)
        ->assertJsonPath('meta.last_page', 3);
});

it('returns not found for an invalid parking identifier', function (string $identifier, ) {
    $this
        ->getJson(
            "/api/v1/parking/{$identifier}/availability/history",
        )
        ->assertNotFound();
})->with([
            'invalid type' => 'invalid:1',
            'zero facility id' => 'facility:0',
            'non numeric facility id' => 'facility:abc',
            'non numeric street id' => 'street:abc',
            'plain number' => '123',
        ]);

it('validates pagination parameters', function (string $query, string $field, ) {
    $facility = ParkingFacility::factory()->create();

    $this
        ->getJson(
            "/api/v1/parking/facility:{$facility->id}/availability/history"
            . "?{$query}",
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field]);
})->with([
            'page zero' => ['page=0', 'page'],
            'per page zero' => ['per_page=0', 'per_page'],
            'per page above max' => ['per_page=101', 'per_page'],
        ]);