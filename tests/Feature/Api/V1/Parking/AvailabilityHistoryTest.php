<?php

use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Models\StreetParking;

/*
|--------------------------------------------------------------------------
| Retrieval
|--------------------------------------------------------------------------
*/

describe('retrieval', function () {
    it('returns facility availability history', function () {
        $facility = ParkingFacility::factory()->create();

        OccupancyReport::factory()->count(3)->create([
            'parking_facility_id' => $facility->id,
            'street_parking_id' => null,
        ]);

        $this
            ->getJson("/api/v1/parking/facility:{$facility->id}/availability/history")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('returns street parking availability history', function () {
        $street = StreetParking::factory()->create();

        OccupancyReport::factory()->count(3)->create([
            'parking_facility_id' => null,
            'street_parking_id' => $street->id,
        ]);

        $this
            ->getJson("/api/v1/parking/street:{$street->id}/availability/history")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('returns an empty history when no reports exist', function () {
        $facility = ParkingFacility::factory()->create();

        $this
            ->getJson("/api/v1/parking/facility:{$facility->id}/availability/history")
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    });
});

/*
|--------------------------------------------------------------------------
| Isolation
|--------------------------------------------------------------------------
*/

describe('isolation', function () {
    it('does not return reports belonging to another parking facility', function () {
        $facility = ParkingFacility::factory()->create();
        $otherFacility = ParkingFacility::factory()->create();

        OccupancyReport::factory()->create([
            'parking_facility_id' => $facility->id,
            'street_parking_id' => null,
            'available_spaces' => 10,
        ]);

        OccupancyReport::factory()->create([
            'parking_facility_id' => $otherFacility->id,
            'street_parking_id' => null,
            'available_spaces' => 99,
        ]);

        $this
            ->getJson("/api/v1/parking/facility:{$facility->id}/availability/history")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.available_spaces', 10);
    });
});

/*
|--------------------------------------------------------------------------
| Ordering
|--------------------------------------------------------------------------
*/

describe('ordering', function () {
    it('returns availability history newest first', function () {
        $facility = ParkingFacility::factory()->create();

        OccupancyReport::factory()->create([
            'parking_facility_id' => $facility->id,
            'street_parking_id' => null,
            'available_spaces' => 5,
            'reported_at' => now()->subMinutes(10),
        ]);

        OccupancyReport::factory()->create([
            'parking_facility_id' => $facility->id,
            'street_parking_id' => null,
            'available_spaces' => 12,
            'reported_at' => now(),
        ]);

        $this
            ->getJson("/api/v1/parking/facility:{$facility->id}/availability/history")
            ->assertOk()
            ->assertJsonPath('data.0.available_spaces', 12)
            ->assertJsonPath('data.1.available_spaces', 5);
    });
});

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

describe('pagination', function () {
    it('paginates availability history', function () {
        $facility = ParkingFacility::factory()->create();

        OccupancyReport::factory()
            ->count(5)
            ->create([
                'parking_facility_id' => $facility->id,
                'street_parking_id' => null,
            ]);

        $this
            ->getJson("/api/v1/parking/facility:{$facility->id}/availability/history?per_page=2&page=1")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.last_page', 3);
    });

    it('returns the requested availability history page', function () {
        $facility = ParkingFacility::factory()->create();

        OccupancyReport::factory()
            ->count(5)
            ->create([
                'parking_facility_id' => $facility->id,
                'street_parking_id' => null,
            ]);

        $this
            ->getJson("/api/v1/parking/facility:{$facility->id}/availability/history?per_page=2&page=2")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 2);
    });
});

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

describe('validation', function () {
    it('returns 404 for an invalid parking identifier', function (string $identifier) {
        $this
            ->getJson("/api/v1/parking/{$identifier}/availability/history")
            ->assertNotFound();
    })->with([
                'invalid:1',
                'facility:0',
                'facility:abc',
                'street:abc',
                '123',
            ]);

    it('returns 404 when the parking entity does not exist', function () {
        $this
            ->getJson('/api/v1/parking/facility:999999/availability/history')
            ->assertNotFound();

        $this
            ->getJson('/api/v1/parking/street:999999/availability/history')
            ->assertNotFound();
    });

    it('validates pagination parameters', function (string $query) {
        $facility = ParkingFacility::factory()->create();

        $this
            ->getJson("/api/v1/parking/facility:{$facility->id}/availability/history?{$query}")
            ->assertUnprocessable();
    })->with([
                'per page zero' => ['per_page=0'],
                'page zero' => ['page=0'],
                'per page above max' => ['per_page=101'],
            ]);
});