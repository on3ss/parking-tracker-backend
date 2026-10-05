<?php

use App\Actions\Parking\FavoriteParking;
use App\Actions\Parking\UnfavoriteParking;
use App\Models\Favorite;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use App\Models\User;

it('removes an existing facility favorite', function () {
    $user = User::factory()->create();
    $facility = ParkingFacility::factory()->create();

    app(FavoriteParking::class)->execute(
        $user->id,
        "facility:{$facility->id}",
    );

    $removed = app(UnfavoriteParking::class)->execute(
        $user->id,
        "facility:{$facility->id}",
    );

    expect($removed)->toBeTrue()
        ->and(Favorite::query()->count())->toBe(0);
});

it('removes an existing street parking favorite', function () {
    $user = User::factory()->create();
    $streetParking = StreetParking::factory()->create();

    app(FavoriteParking::class)->execute(
        $user->id,
        "street:{$streetParking->id}",
    );

    $removed = app(UnfavoriteParking::class)->execute(
        $user->id,
        "street:{$streetParking->id}",
    );

    expect($removed)->toBeTrue()
        ->and(Favorite::query()->count())->toBe(0);
});

it('returns false when the parking is not favorited', function () {
    $user = User::factory()->create();
    $facility = ParkingFacility::factory()->create();

    $removed = app(UnfavoriteParking::class)->execute(
        $user->id,
        "facility:{$facility->id}",
    );

    expect($removed)->toBeFalse();
});

it('does not remove another users favorite', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    $facility = ParkingFacility::factory()->create();

    app(FavoriteParking::class)->execute(
        $secondUser->id,
        "facility:{$facility->id}",
    );

    $removed = app(UnfavoriteParking::class)->execute(
        $firstUser->id,
        "facility:{$facility->id}",
    );

    expect($removed)->toBeFalse()
        ->and(Favorite::query()->count())->toBe(1);
});

it('keeps a different parking favorite untouched', function () {
    $user = User::factory()->create();

    $first = ParkingFacility::factory()->create();
    $second = ParkingFacility::factory()->create();

    app(FavoriteParking::class)->execute(
        $user->id,
        "facility:{$first->id}",
    );

    app(FavoriteParking::class)->execute(
        $user->id,
        "facility:{$second->id}",
    );

    app(UnfavoriteParking::class)->execute(
        $user->id,
        "facility:{$first->id}",
    );

    expect(Favorite::query()->count())->toBe(1)
        ->and(
            Favorite::query()
                ->where('favorable_id', $second->id)
                ->exists(),
        )->toBeTrue();
});