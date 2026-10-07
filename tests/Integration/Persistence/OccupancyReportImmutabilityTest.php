<?php

use App\Enums\ParkingSource;
use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Models\User;
use LogicException;

function occupancyReport(): OccupancyReport
{
    return OccupancyReport::factory()
        ->forFacility(ParkingFacility::factory()->create())
        ->create([
            'available_spaces' => 5,
            'occupied_spaces' => 5,
        ]);
}

it('cannot modify any observation field', function (string $field, mixed $value) {
    $report = occupancyReport();

    $report->update([
        $field => $value,
    ]);
})->with([
    ['available_spaces', 4],
    ['occupied_spaces', 6],
    ['source', ParkingSource::SENSOR],
    ['reported_at', now()->addMinute()],
    ['user_id', User::factory()],
])->throws(LogicException::class);
