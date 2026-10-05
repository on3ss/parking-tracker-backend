<?php

use App\Enums\AvailabilityStatus;

it('calculates availability status', function (int $capacity, int $availableSpaces, AvailabilityStatus $expected) {
    expect(AvailabilityStatus::for($capacity, $availableSpaces))
        ->toBe($expected);
})->with([
            'zero capacity' => [0, 0, AvailabilityStatus::UNKNOWN],
            'full' => [10, 0, AvailabilityStatus::FULL],
            'exactly 20 percent remaining' => [10, 2, AvailabilityStatus::LIMITED],
            'below 20 percent remaining' => [10, 1, AvailabilityStatus::LIMITED],
            'above 20 percent remaining' => [10, 3, AvailabilityStatus::AVAILABLE],
            'exactly 80 percent available' => [10, 8, AvailabilityStatus::AVAILABLE],
            'fully available' => [10, 10, AvailabilityStatus::AVAILABLE],
        ]);