<?php

use App\Models\ParkingFacility;
use App\Models\StreetParking;
use App\Models\User;

it('favorites a facility', function () {
    $user = User::factory()->create();
    $facility = ParkingFacility::factory()->create();

    $this
        ->actingAs($user, 'sanctum')
        ->postJson("/api/v1/favorites/facility:{$facility->id}")
        ->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'parking' => [
                    'id',
                    'type',
                ],
            ],
        ])
        ->assertJsonPath(
            'data.parking.id',
            "facility:{$facility->id}",
        )
        ->assertJsonPath('data.parking.type', 'facility');
});

it('favorites street parking', function () {
    $user = User::factory()->create();
    $street = StreetParking::factory()->create();

    $this
        ->actingAs($user, 'sanctum')
        ->postJson("/api/v1/favorites/street:{$street->id}")
        ->assertCreated()
        ->assertJsonPath(
            'data.parking.id',
            "street:{$street->id}",
        )
        ->assertJsonPath('data.parking.type', 'street');
});

it('returns the existing favorite when favorited twice', function () {
    $user = User::factory()->create();
    $facility = ParkingFacility::factory()->create();

    $url = "/api/v1/favorites/facility:{$facility->id}";

    $first = $this
        ->actingAs($user, 'sanctum')
        ->postJson($url)
        ->assertCreated();

    $second = $this
        ->actingAs($user, 'sanctum')
        ->postJson($url)
        ->assertOk();

    expect($second->json('data.id'))
        ->toBe($first->json('data.id'));
});

it('lists the authenticated users favorites', function () {
    $user = User::factory()->create();

    $facility = ParkingFacility::factory()->create();
    $street = StreetParking::factory()->create();

    $this
        ->actingAs($user, 'sanctum')
        ->postJson("/api/v1/favorites/facility:{$facility->id}")
        ->assertCreated();

    $this
        ->actingAs($user, 'sanctum')
        ->postJson("/api/v1/favorites/street:{$street->id}")
        ->assertCreated();

    $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/v1/favorites')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'parking',
                ],
            ],
            'meta',
        ])
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 2);
});

it('does not expose another users favorites', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $facility = ParkingFacility::factory()->create();

    $this
        ->actingAs($user, 'sanctum')
        ->postJson("/api/v1/favorites/facility:{$facility->id}")
        ->assertCreated();

    $this
        ->actingAs($otherUser, 'sanctum')
        ->getJson('/api/v1/favorites')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('paginates favorites', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $_) {
        $facility = ParkingFacility::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->postJson(
                "/api/v1/favorites/facility:{$facility->id}",
            )
            ->assertCreated();
    }

    $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/v1/favorites?per_page=2&page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.current_page', 2)
        ->assertJsonPath('meta.total', 5);
});

it('removes a favorite', function () {
    $user = User::factory()->create();
    $facility = ParkingFacility::factory()->create();

    $this
        ->actingAs($user, 'sanctum')
        ->postJson("/api/v1/favorites/facility:{$facility->id}")
        ->assertCreated();

    $this
        ->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/favorites/facility:{$facility->id}")
        ->assertNoContent();
});

it('is idempotent when removing a non-existent favorite', function () {
    $user = User::factory()->create();
    $facility = ParkingFacility::factory()->create();

    $this
        ->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/favorites/facility:{$facility->id}")
        ->assertNoContent();
});

it('requires authentication for favorites', function () {
    $facility = ParkingFacility::factory()->create();

    $this
        ->postJson("/api/v1/favorites/facility:{$facility->id}")
        ->assertUnauthorized();

    $this
        ->getJson('/api/v1/favorites')
        ->assertUnauthorized();

    $this
        ->deleteJson("/api/v1/favorites/facility:{$facility->id}")
        ->assertUnauthorized();
});

it('returns not found for an invalid favorite target', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/favorites/facility:999999')
        ->assertNotFound();
});