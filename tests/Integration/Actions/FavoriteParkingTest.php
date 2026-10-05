<?php

use App\Actions\Parking\FavoriteParking;
use App\Models\Favorite;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use App\Models\User;

it('favorites a parking facility', function () {
    $user = User::factory()->create();
    $facility = ParkingFacility::factory()->create();

    $favorite = app(FavoriteParking::class)->execute(
        userId: $user->id,
        parkingIdentifier: "facility:{$facility->id}",
    );

    expect($favorite)
        ->toBeInstanceOf(Favorite::class)
        ->and($favorite->user_id)->toBe($user->id)
        ->and($favorite->favorable_type)->toBe('facility')
        ->and($favorite->favorable_id)->toBe($facility->id);

    expect($favorite->favorable->is($facility))->toBeTrue();
});

it('favorites street parking', function () {
    $user = User::factory()->create();
    $streetParking = StreetParking::factory()->create();

    $favorite = app(FavoriteParking::class)->execute(
        userId: $user->id,
        parkingIdentifier: "street:{$streetParking->id}",
    );

    expect($favorite->user_id)->toBe($user->id)
        ->and($favorite->favorable_type)->toBe('street')
        ->and($favorite->favorable_id)->toBe($streetParking->id)
        ->and($favorite->favorable->is($streetParking))->toBeTrue();
});

it('does not create duplicate favorites', function () {
    $user = User::factory()->create();
    $facility = ParkingFacility::factory()->create();

    $first = app(FavoriteParking::class)->execute(
        $user->id,
        "facility:{$facility->id}",
    );

    $second = app(FavoriteParking::class)->execute(
        $user->id,
        "facility:{$facility->id}",
    );

    expect($second->id)->toBe($first->id)
        ->and(
            Favorite::query()
                ->where('user_id', $user->id)
                ->where('favorable_type', 'facility')
                ->where('favorable_id', $facility->id)
                ->count()
        )->toBe(1);
});

it('allows different users to favorite the same parking', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    $facility = ParkingFacility::factory()->create();

    app(FavoriteParking::class)->execute(
        $firstUser->id,
        "facility:{$facility->id}",
    );

    app(FavoriteParking::class)->execute(
        $secondUser->id,
        "facility:{$facility->id}",
    );

    expect(
        Favorite::query()
            ->where('favorable_type', 'facility')
            ->where('favorable_id', $facility->id)
            ->count()
    )->toBe(2);
});

it('keeps facility and street favorites distinct', function () {
    $user = User::factory()->create();

    $facility = ParkingFacility::factory()->create();
    $streetParking = StreetParking::factory()->create();

    $facilityFavorite = app(FavoriteParking::class)->execute(
        $user->id,
        "facility:{$facility->id}",
    );

    $streetFavorite = app(FavoriteParking::class)->execute(
        $user->id,
        "street:{$streetParking->id}",
    );

    expect($facilityFavorite->id)->not->toBe($streetFavorite->id)
        ->and(Favorite::query()->where('user_id', $user->id)->count())
        ->toBe(2);
});

it('fails when the parking identifier does not exist', function () {
    $user = User::factory()->create();

    expect(fn() => app(FavoriteParking::class)->execute(
        $user->id,
        'facility:999999',
    ))->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect(Favorite::query()->count())->toBe(0);
});