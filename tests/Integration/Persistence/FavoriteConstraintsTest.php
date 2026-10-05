<?php

use App\Models\Favorite;
use App\Models\ParkingFacility;
use App\Models\User;
use Illuminate\Database\QueryException;

it('prevents a user from favoriting the same parking twice', function () {
    $user = User::factory()->create();
    $facility = ParkingFacility::factory()->create();

    Favorite::query()->create([
        'user_id' => $user->id,
        'favorable_type' => ParkingFacility::class,
        'favorable_id' => $facility->id,
    ]);

    Favorite::query()->create([
        'user_id' => $user->id,
        'favorable_type' => ParkingFacility::class,
        'favorable_id' => $facility->id,
    ]);
})->throws(QueryException::class);

it('allows different users to favorite the same parking', function () {
    $facility = ParkingFacility::factory()->create();

    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    Favorite::query()->create([
        'user_id' => $firstUser->id,
        'favorable_type' => ParkingFacility::class,
        'favorable_id' => $facility->id,
    ]);

    Favorite::query()->create([
        'user_id' => $secondUser->id,
        'favorable_type' => ParkingFacility::class,
        'favorable_id' => $facility->id,
    ]);

    expect(Favorite::query()->count())->toBe(2);
});

it('cascades favorites when the user is deleted', function () {
    $user = User::factory()->create();
    $facility = ParkingFacility::factory()->create();

    Favorite::query()->create([
        'user_id' => $user->id,
        'favorable_type' => ParkingFacility::class,
        'favorable_id' => $facility->id,
    ]);

    $user->delete();

    expect(Favorite::query()->count())->toBe(0);
});