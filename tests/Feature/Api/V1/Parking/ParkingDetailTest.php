<?php

use App\Models\ParkingFacility;
use App\Models\StreetParking;

it('returns a parking facility by public identifier', function () {
    $facility = ParkingFacility::factory()->create();

    $this
        ->getJson("/api/v1/parking/facility:{$facility->id}")
        ->assertOk()
        ->assertJsonPath('data.id', "facility:{$facility->id}")
        ->assertJsonPath('data.type', 'facility')
        ->assertJsonPath('data.name', $facility->name);
});

it('returns street parking by public identifier', function () {
    $streetParking = StreetParking::factory()->create();

    $this
        ->getJson("/api/v1/parking/street:{$streetParking->id}")
        ->assertOk()
        ->assertJsonPath('data.id', "street:{$streetParking->id}")
        ->assertJsonPath('data.type', 'street')
        ->assertJsonPath('data.name', $streetParking->name);
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

it('rejects an invalid parking identifier', function () {
    $this
        ->getJson('/api/v1/parking/invalid:123')
        ->assertNotFound();
});