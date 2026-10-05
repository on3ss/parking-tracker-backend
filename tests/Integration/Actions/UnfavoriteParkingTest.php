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

    $result = app(UnfavoriteParking::class)->execute(
        $user->id,
        "facility:{$facility->id}",
    );

    expect($result)->toBeTrue()
        ->and(Favorite::query()->count())->toBe(0);
});

it('removes an existing street parking favorite', function () {
    $user = User::factory()->create();
    $parking = StreetParking::factory()->create();

    app(FavoriteParking::class)->execute(
        $user->id,
        "street:{$parking->id}",
    );

    $result = app(UnfavoriteParking::class)->execute(
        $user->id,
        "street:{$parking->id}",
    );

    expect($result)->toBeTrue()
        ->and(Favorite::query()->count())->toBe(0);
});

it('returns false when the parking is not favorited', function () {
    $user = User::factory()->create();
    $facility = ParkingFacility::factory()->create();

    $result = app(UnfavoriteParking::class)->execute(
        $user->id,
        "facility:{$facility->id}",
    );

    expect($result)->toBeFalse()
        ->and(Favorite::query()->count())->toBe(0);
});

it('does not remove another users favorite', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $facility = ParkingFacility::factory()->create();

    app(FavoriteParking::class)->execute(
        $owner->id,
        "facility:{$facility->id}",
    );

    $result = app(UnfavoriteParking::class)->execute(
        $otherUser->id,
        "facility:{$facility->id}",
    );

    expect($result)->toBeFalse()
        ->and(
            Favorite::query()
                ->where('user_id', $owner->id)
                ->count()
        )->toBe(1);
});

it('does not remove a different parking favorite', function () {
    $user = User::factory()->create();
    $first = ParkingFacility::factory()->create();
    $second = ParkingFacility::factory()->create();

    app(FavoriteParking::class)->execute(
        $user->id,
        "facility:{$first->id}",
    );

    $result = app(UnfavoriteParking::class)->execute(
        $user->id,
        "facility:{$second->id}",
    );

    expect($result)->toBeFalse()
        ->and(
            Favorite::query()
                ->where('user_id', $user->id)
                ->count()
        )->toBe(1);
});

it('fails when the parking identifier does not exist', function () {
    $user = User::factory()->create();

    expect(fn() => app(UnfavoriteParking::class)->execute(
        $user->id,
        'street:999999',
    ))->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});