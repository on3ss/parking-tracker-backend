<?php

use App\Support\Parking\ParkingIdentifierParser;
use InvalidArgumentException;

it('parses facility identifiers', function () {
    expect(
        (new ParkingIdentifierParser)->parse('facility:12'),
    )->toBe([
        'type' => 'facility',
        'id' => 12,
    ]);
});

it('parses street parking identifiers', function () {
    expect(
        (new ParkingIdentifierParser)->parse('street:34'),
    )->toBe([
        'type' => 'street',
        'id' => 34,
    ]);
});

it('rejects malformed identifiers', function (string $identifier) {
    expect(
        fn () => (new ParkingIdentifierParser)->parse($identifier),
    )->toThrow(InvalidArgumentException::class);
})->with([
    'missing type' => '12',
    'unknown type' => 'garage:12',
    'zero ID' => 'facility:0',
    'leading zero' => 'street:01',
    'negative ID' => 'facility:-1',
    'missing ID' => 'street:',
    'extra segment' => 'facility:12:34',
    'leading whitespace' => ' facility:12',
]);
