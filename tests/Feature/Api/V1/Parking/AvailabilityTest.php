<?php

use App\Models\ParkingFacility;
use App\Models\StreetParking;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('reports facility availability through the API', function () {
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
        ->assertJsonPath('data.id', "facility:{$facility->id}")
        ->assertJsonPath('data.type', 'facility')
        ->assertJsonPath('data.availability.available_spaces', 8);
});

it('reports street parking availability through the API', function () {
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
        ->assertOk()
        ->assertJsonPath('data.id', "street:{$street->id}")
        ->assertJsonPath('data.type', 'street')
        ->assertJsonPath('data.availability.available_spaces', 12);
});

it('requires authentication', function () {
    $facility = ParkingFacility::factory()->create();

    $this
        ->postJson(
            "/api/v1/parking/facility:{$facility->id}/availability",
            ['available_spaces' => 8],
        )
        ->assertUnauthorized();
});

it('requires available spaces', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 50,
    ]);

    $this
        ->actingAs($this->user, 'sanctum')
        ->postJson(
            "/api/v1/parking/facility:{$facility->id}/availability",
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['available_spaces']);
});

it('rejects negative available spaces', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 50,
    ]);

    $this
        ->actingAs($this->user, 'sanctum')
        ->postJson(
            "/api/v1/parking/facility:{$facility->id}/availability",
            ['available_spaces' => -1],
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['available_spaces']);
});

it('returns not found for a nonexistent parking target', function () {
    $this
        ->actingAs($this->user, 'sanctum')
        ->postJson(
            '/api/v1/parking/facility:999999/availability',
            ['available_spaces' => 5],
        )
        ->assertNotFound();
});

it('returns not found for an invalid parking identifier', function () {
    $this
        ->actingAs($this->user, 'sanctum')
        ->postJson(
            '/api/v1/parking/not-a-parking/availability',
            ['available_spaces' => 5],
        )
        ->assertNotFound();
});
