<?php

use App\Exceptions\Parking\InvalidParkingAvailability;
use App\Support\Parking\AvailabilityReportRules;

beforeEach(function () {
    $this->rules = new AvailabilityReportRules;
});

it('accepts zero occupied and available spaces', function () {
    $this->rules->validate(10, 0, 0);

    expect(true)->toBeTrue();
});

it('accepts occupied and available counts equal to capacity', function () {
    $this->rules->validate(10, 4, 6);

    expect(true)->toBeTrue();
});

it('accepts an omitted occupied count', function () {
    $this->rules->validate(10, 4, null);

    expect(true)->toBeTrue();
});

it('rejects missing capacity', function () {
    $this->rules->validate(null, 1, null);
})->throws(
    InvalidParkingAvailability::class,
    'Parking capacity must be set',
);

it('rejects negative capacity', function () {
    $this->rules->validate(-1, 0, null);
})->throws(
    InvalidParkingAvailability::class,
    'Parking capacity cannot be negative',
);

it('rejects negative available spaces', function () {
    $this->rules->validate(10, -1, null);
})->throws(
    InvalidParkingAvailability::class,
    'Available spaces must be between zero and capacity',
);

it('rejects available spaces above capacity', function () {
    $this->rules->validate(10, 11, null);
})->throws(
    InvalidParkingAvailability::class,
    'Available spaces must be between zero and capacity',
);

it('rejects negative occupied spaces', function () {
    $this->rules->validate(10, 4, -1);
})->throws(
    InvalidParkingAvailability::class,
    'Occupied spaces must be between zero and capacity',
);

it('rejects occupied spaces above capacity', function () {
    $this->rules->validate(10, 0, 11);
})->throws(
    InvalidParkingAvailability::class,
    'Occupied spaces must be between zero and capacity',
);

it('rejects combined counts above capacity', function () {
    $this->rules->validate(10, 6, 5);
})->throws(
    InvalidParkingAvailability::class,
    'cannot exceed parking capacity',
);
