<?php

use App\Actions\Parking\FavoriteParking;
use App\Actions\Parking\ListFavorites;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use App\Models\User;

it('lists only the current users favorites', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $ownFacility = ParkingFacility::factory()->create();
    $otherFacility = ParkingFacility::factory()->create();

    app(FavoriteParking::class)->execute(
        $user->id,
        "facility:{$ownFacility->id}",
    );

    app(FavoriteParking::class)->execute(
        $otherUser->id,
        "facility:{$otherFacility->id}",
    );

    $results = app(ListFavorites::class)->execute(
        userId: $user->id,
    );

    expect($results->total())->toBe(1)
        ->and($results->first()->user_id)->toBe($user->id)
        ->and($results->first()->favorable->is($ownFacility))->toBeTrue();
});

it('hydrates facility favorites', function () {
    $user = User::factory()->create();
    $facility = ParkingFacility::factory()->create();

    app(FavoriteParking::class)->execute(
        $user->id,
        "facility:{$facility->id}",
    );

    $result = app(ListFavorites::class)->execute($user->id);

    expect($result->first()->favorable)
        ->toBeInstanceOf(ParkingFacility::class)
        ->and($result->first()->favorable->is($facility))
        ->toBeTrue();
});

it('hydrates street parking favorites', function () {
    $user = User::factory()->create();
    $parking = StreetParking::factory()->create();

    app(FavoriteParking::class)->execute(
        $user->id,
        "street:{$parking->id}",
    );

    $result = app(ListFavorites::class)->execute($user->id);

    expect($result->first()->favorable)
        ->toBeInstanceOf(StreetParking::class)
        ->and($result->first()->favorable->is($parking))
        ->toBeTrue();
});

it('orders favorites newest first', function () {
    $user = User::factory()->create();

    $first = ParkingFacility::factory()->create();
    $second = ParkingFacility::factory()->create();

    $firstFavorite = app(FavoriteParking::class)->execute(
        $user->id,
        "facility:{$first->id}",
    );

    $secondFavorite = app(FavoriteParking::class)->execute(
        $user->id,
        "facility:{$second->id}",
    );

    $secondFavorite->update([
        'created_at' => now()->addMinute(),
    ]);

    $results = app(ListFavorites::class)->execute($user->id);

    expect($results->getCollection()->pluck('id')->all())
        ->toBe([
            $secondFavorite->id,
            $firstFavorite->id,
        ]);
});

it('paginates favorites', function () {
    $user = User::factory()->create();

    $facilities = ParkingFacility::factory()->count(5)->create();

    foreach ($facilities as $facility) {
        app(FavoriteParking::class)->execute(
            $user->id,
            "facility:{$facility->id}",
        );
    }

    $page = app(ListFavorites::class)->execute(
        userId: $user->id,
        perPage: 2,
        page: 2,
    );

    expect($page->total())->toBe(5)
        ->and($page->perPage())->toBe(2)
        ->and($page->currentPage())->toBe(2)
        ->and($page->count())->toBe(2);
});

it('returns an empty paginator when the user has no favorites', function () {
    $user = User::factory()->create();

    $results = app(ListFavorites::class)->execute($user->id);

    expect($results->total())->toBe(0)
        ->and($results->count())->toBe(0);
});

it('does not expose favorites belonging to another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $facility = ParkingFacility::factory()->create();

    app(FavoriteParking::class)->execute(
        $otherUser->id,
        "facility:{$facility->id}",
    );

    $results = app(ListFavorites::class)->execute($user->id);

    expect($results->total())->toBe(0);
});