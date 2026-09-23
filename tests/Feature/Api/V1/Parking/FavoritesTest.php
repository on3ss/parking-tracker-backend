<?php

use App\Models\Favorite;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Creating favorites
|--------------------------------------------------------------------------
*/

describe('creating favorites', function () {
    it('allows an authenticated user to favorite a facility', function () {
        $user = User::factory()->create();

        $facility = ParkingFacility::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->postJson("/api/v1/favorites/facility:{$facility->id}")
            ->assertCreated()
            ->assertJsonPath('data.parking.id', "facility:{$facility->id}")
            ->assertJsonPath('data.parking.type', 'facility');

        expect(
            Favorite::query()
                ->where('user_id', $user->id)
                ->count(),
        )->toBe(1);
    });

    it('allows an authenticated user to favorite street parking', function () {
        $user = User::factory()->create();

        $street = StreetParking::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->postJson("/api/v1/favorites/street:{$street->id}")
            ->assertCreated()
            ->assertJsonPath('data.parking.id', "street:{$street->id}")
            ->assertJsonPath('data.parking.type', 'street');
    });

    it('does not create duplicate favorites', function () {
        $user = User::factory()->create();

        $facility = ParkingFacility::factory()->create();

        $url = "/api/v1/favorites/facility:{$facility->id}";

        $this
            ->actingAs($user, 'sanctum')
            ->postJson($url)
            ->assertCreated();

        $this
            ->actingAs($user, 'sanctum')
            ->postJson($url)
            ->assertOk();

        expect(
            Favorite::query()
                ->where('user_id', $user->id)
                ->where('favorable_id', $facility->id)
                ->count(),
        )->toBe(1);
    });
});

/*
|--------------------------------------------------------------------------
| Listing favorites
|--------------------------------------------------------------------------
*/

describe('listing favorites', function () {
    it('keeps favorites isolated between users', function () {
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

    it('lists the authenticated user favorites', function () {
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
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);
    });

    it('paginates favorites', function () {
        $user = User::factory()->create();

        ParkingFacility::factory()
            ->count(5)
            ->create()
            ->each(function (ParkingFacility $facility) use ($user) {
                Favorite::query()->create([
                    'user_id' => $user->id,
                    'favorable_type' => 'facility',
                    'favorable_id' => $facility->id,
                ]);
            });

        $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/v1/favorites?per_page=2&page=1')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.last_page', 3);
    });
});

/*
|--------------------------------------------------------------------------
| Removing favorites
|--------------------------------------------------------------------------
*/

describe('removing favorites', function () {
    it('allows a user to remove a favorite', function () {
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

        expect(
            Favorite::query()
                ->where('user_id', $user->id)
                ->exists(),
        )->toBeFalse();
    });

    it('allows removing a parking that is not currently favorited', function () {
        $user = User::factory()->create();

        $facility = ParkingFacility::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/favorites/facility:{$facility->id}")
            ->assertNoContent();
    });
});

/*
|--------------------------------------------------------------------------
| Validation and authorization
|--------------------------------------------------------------------------
*/

describe('validation and authorization', function () {
    it('returns 404 for an invalid favorite target', function () {
        $user = User::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/v1/favorites/facility:abc')
            ->assertNotFound();
    });

    it('returns 404 when favoriting nonexistent parking', function () {
        $user = User::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/v1/favorites/facility:999999')
            ->assertNotFound();
    });

    it('requires authentication', function () {
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
});