<?php

use App\Actions\Parking\ResolveParkingIdentifier;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Illuminate\Database\Eloquent\ModelNotFoundException;

it('resolves a parking facility identifier', function () {
    $facility = ParkingFacility::factory()->create();

    $result = app(ResolveParkingIdentifier::class)->execute(
        "facility:{$facility->id}",
    );

    expect($result)
        ->toBeInstanceOf(ParkingFacility::class)
        ->and($result->is($facility))->toBeTrue();
});

it('resolves a street parking identifier', function () {
    $streetParking = StreetParking::factory()->create();

    $result = app(ResolveParkingIdentifier::class)->execute(
        "street:{$streetParking->id}",
    );

    expect($result)
        ->toBeInstanceOf(StreetParking::class)
        ->and($result->is($streetParking))->toBeTrue();
});

it('does not resolve a facility identifier as street parking', function () {
    $facility = ParkingFacility::factory()->create();

    expect(fn() => app(ResolveParkingIdentifier::class)->execute(
        "street:{$facility->id}",
    ))->toThrow(ModelNotFoundException::class);
});

it('does not resolve a street identifier as a parking facility', function () {
    $streetParking = StreetParking::factory()->create();

    expect(fn() => app(ResolveParkingIdentifier::class)->execute(
        "facility:{$streetParking->id}",
    ))->toThrow(ModelNotFoundException::class);
});

it('rejects an identifier without a parking type', function () {
    expect(fn() => app(ResolveParkingIdentifier::class)->execute(
        '1',
    ))->toThrow(ModelNotFoundException::class);
});

it('rejects an unknown parking type', function () {
    expect(fn() => app(ResolveParkingIdentifier::class)->execute(
        'garage:1',
    ))->toThrow(ModelNotFoundException::class);
});

it('rejects an identifier with a non-positive id', function (string $identifier) {
    expect(fn() => app(ResolveParkingIdentifier::class)->execute(
        $identifier,
    ))->toThrow(ModelNotFoundException::class);
})->with([
            'facility:0',
            'facility:-1',
            'street:0',
            'street:-1',
        ]);

it('rejects an identifier with a non-numeric id', function (string $identifier) {
    expect(fn() => app(ResolveParkingIdentifier::class)->execute(
        $identifier,
    ))->toThrow(ModelNotFoundException::class);
})->with([
            'facility:abc',
            'street:abc',
            'facility:1.5',
            'street:1.5',
        ]);

it('rejects malformed identifiers', function (string $identifier) {
    expect(fn() => app(ResolveParkingIdentifier::class)->execute(
        $identifier,
    ))->toThrow(ModelNotFoundException::class);
})->with([
            '',
            ':1',
            'facility',
            'street',
            'facility:',
            'street:',
            'facility:1:',
            ' facility:1',
            'facility:1 ',
        ]);

it('throws when the referenced facility does not exist', function () {
    expect(fn() => app(ResolveParkingIdentifier::class)->execute(
        'facility:999999',
    ))->toThrow(ModelNotFoundException::class);
});

it('throws when the referenced street parking does not exist', function () {
    expect(fn() => app(ResolveParkingIdentifier::class)->execute(
        'street:999999',
    ))->toThrow(ModelNotFoundException::class);
});