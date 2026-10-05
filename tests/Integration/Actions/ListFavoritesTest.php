<?php

use App\Actions\Parking\FavoriteParking;
use App\Actions\Parking\ListFavorites;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use App\Models\User;
use Carbon\Carbon;

function listFavorites(
    int $userId,
    int $perPage = 20,
    int $page = 1,
) {
    return app(ListFavorites::class)->execute(
        userId: $userId,
        perPage: $perPage,
        page: $page,
    );
}

it('lists only the users favorites', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $facility = ParkingFacility::factory()->create();
    $otherFacility = ParkingFacility::factory()->create();

    app(FavoriteParking::class)->execute(
        $user->id,
        "facility:{$facility->id}",
    );

    app(FavoriteParking::class)->execute(
        $otherUser->id,
        "facility:{$otherFacility->id}",
    );

    $favorites = listFavorites($user->id);

    expect($favorites->items())
        ->toHaveCount(1)
        ->and($favorites->first()->favorable_id)
        ->toBe($facility->id);
});

it('hydrates the favorited parking', function () {
    $user = User::factory()->create();
    $facility = ParkingFacility::factory()->create();

    app(FavoriteParking::class)->execute(
        $user->id,
        "facility:{$facility->id}",
    );

    $favorite = listFavorites($user->id)->first();

    expect($favorite->relationLoaded('favorable'))->toBeTrue()
        ->and($favorite->favorable)->toBeInstanceOf(ParkingFacility::class)
        ->and($favorite->favorable->is($facility))->toBeTrue();
});

it('hydrates street parking favorites', function () {
    $user = User::factory()->create();
    $streetParking = StreetParking::factory()->create();

    app(FavoriteParking::class)->execute(
        $user->id,
        "street:{$streetParking->id}",
    );

    $favorite = listFavorites($user->id)->first();

    expect($favorite->favorable)->toBeInstanceOf(StreetParking::class)
        ->and($favorite->favorable->is($streetParking))->toBeTrue();
});

it('lists newest favorites first', function () {
    $user = User::factory()->create();

    $older = ParkingFacility::factory()->create();
    $newer = ParkingFacility::factory()->create();

    Carbon::setTestNow('2026-10-01 10:00:00');

    app(FavoriteParking::class)->execute(
        $user->id,
        "facility:{$older->id}",
    );

    Carbon::setTestNow('2026-10-01 11:00:00');

    app(FavoriteParking::class)->execute(
        $user->id,
        "facility:{$newer->id}",
    );

    Carbon::setTestNow();

    $favorites = listFavorites($user->id);

    expect($favorites->items())
        ->toHaveCount(2)
        ->and($favorites->first()->favorable_id)->toBe($newer->id)
        ->and($favorites->last()->favorable_id)->toBe($older->id);
});

it('paginates favorites', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $index) {
        $facility = ParkingFacility::factory()->create();

        app(FavoriteParking::class)->execute(
            $user->id,
            "facility:{$facility->id}",
        );
    }

    $favorites = listFavorites(
        userId: $user->id,
        perPage: 2,
        page: 2,
    );

    expect($favorites->perPage())->toBe(2)
        ->and($favorites->currentPage())->toBe(2)
        ->and($favorites->total())->toBe(5)
        ->and($favorites->items())->toHaveCount(2);
});

it('returns an empty paginator when the user has no favorites', function () {
    $user = User::factory()->create();

    $favorites = listFavorites($user->id);

    expect($favorites->total())->toBe(0)
        ->and($favorites->items())->toBeEmpty();
});