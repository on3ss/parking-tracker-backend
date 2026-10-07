<?php

use App\Actions\Parking\FavoriteParking;
use App\Models\Favorite;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

function favoriteParking(
    int $userId,
    string $parkingIdentifier,
): Favorite {
    return app(FavoriteParking::class)->execute(
        userId: $userId,
        parkingIdentifier: $parkingIdentifier,
    );
}

it('favorites a parking facility', function () {
    $user = User::factory()->create();
    $facility = ParkingFacility::factory()->create();

    $favorite = favoriteParking(
        $user->id,
        "facility:{$facility->id}",
    );

    expect($favorite)
        ->user_id->toBe($user->id)
        ->favorable_type->toBe('facility')
        ->favorable_id->toBe($facility->id);
});

it('favorites street parking', function () {
    $user = User::factory()->create();
    $streetParking = StreetParking::factory()->create();

    $favorite = favoriteParking(
        $user->id,
        "street:{$streetParking->id}",
    );

    expect($favorite)
        ->user_id->toBe($user->id)
        ->favorable_type->toBe('street')
        ->favorable_id->toBe($streetParking->id);
});

it('is idempotent for the same user and parking', function () {
    $user = User::factory()->create();
    $facility = ParkingFacility::factory()->create();

    $first = favoriteParking(
        $user->id,
        "facility:{$facility->id}",
    );

    $second = favoriteParking(
        $user->id,
        "facility:{$facility->id}",
    );

    expect($second->id)->toBe($first->id)
        ->and(Favorite::query()->count())->toBe(1);
});

it('allows different users to favorite the same parking', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    $facility = ParkingFacility::factory()->create();

    favoriteParking($firstUser->id, "facility:{$facility->id}");
    favoriteParking($secondUser->id, "facility:{$facility->id}");

    expect(Favorite::query()->count())->toBe(2);
});

it('keeps facility and street favorites distinct', function () {
    $user = User::factory()->create();

    $facility = ParkingFacility::factory()->create([
        'id' => 1,
    ]);

    $streetParking = StreetParking::factory()->create([
        'id' => 1,
    ]);

    favoriteParking($user->id, 'facility:1');
    favoriteParking($user->id, 'street:1');

    expect(Favorite::query()->count())->toBe(2);
});

it('fails when the parking does not exist', function () {
    $user = User::factory()->create();

    expect(fn () => favoriteParking(
        $user->id,
        'facility:999999',
    ))->toThrow(
        ModelNotFoundException::class,
    );
});
