<?php

use App\Enums\AvailabilityStatus;
use App\Models\ParkingFacility;
use App\Models\StreetParking;

it('returns a parking facility by public identifier', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 50,
        'available_spaces' => 40,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    $this
        ->getJson("/api/v1/parking/facility:{$facility->id}")
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'name',
                'provider',
                'location',
                'capacity',
                'availability' => [
                    'status',
                    'available_spaces',
                    'updated_at',
                ],
            ],
        ])
        ->assertJsonPath(
            'data.id',
            "facility:{$facility->id}",
        )
        ->assertJsonPath(
            'data.type',
            'facility',
        )
        ->assertJsonPath(
            'data.name',
            $facility->name,
        )
        ->assertJsonPath(
            'data.capacity',
            50,
        )
        ->assertJsonPath(
            'data.availability.available_spaces',
            40,
        );
});

it('returns street parking by public identifier', function () {
    $streetParking = StreetParking::factory()->create([
        'capacity' => 30,
        'available_spaces' => 10,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    $this
        ->getJson("/api/v1/parking/street:{$streetParking->id}")
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'name',
                'provider',
                'location',
                'capacity',
                'availability' => [
                    'status',
                    'available_spaces',
                    'updated_at',
                ],
            ],
        ])
        ->assertJsonPath(
            'data.id',
            "street:{$streetParking->id}",
        )
        ->assertJsonPath(
            'data.type',
            'street',
        )
        ->assertJsonPath(
            'data.name',
            $streetParking->name,
        )
        ->assertJsonPath(
            'data.capacity',
            30,
        )
        ->assertJsonPath(
            'data.availability.available_spaces',
            10,
        );
});

it('returns not found for a nonexistent facility', function () {
    $this
        ->getJson('/api/v1/parking/facility:999999')
        ->assertNotFound();
});

it('returns not found for a nonexistent street parking record', function () {
    $this
        ->getJson('/api/v1/parking/street:999999')
        ->assertNotFound();
});

it('returns not found for an invalid parking identifier', function () {
    $this
        ->getJson('/api/v1/parking/invalid:123')
        ->assertNotFound();
});
