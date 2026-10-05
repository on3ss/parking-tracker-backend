<?php

use App\Enums\ParkingSource;

it('assigns the expected trust level to each source', function (ParkingSource $source, int $expected) {
    expect($source->trustLevel())
        ->toBe($expected);
})->with([
            'system' => [ParkingSource::SYSTEM, 100],
            'operator' => [ParkingSource::OPERATOR, 80],
            'sensor' => [ParkingSource::SENSOR, 60],
            'camera' => [ParkingSource::CAMERA, 50],
            'user' => [ParkingSource::USER, 20],
        ]);

it('assigns the expected freshness ttl to each source', function (ParkingSource $source, int $expected) {
    expect($source->freshnessTtlMinutes())
        ->toBe($expected);
})->with([
            'system' => [ParkingSource::SYSTEM, 1],
            'sensor' => [ParkingSource::SENSOR, 2],
            'camera' => [ParkingSource::CAMERA, 5],
            'user' => [ParkingSource::USER, 15],
            'operator' => [ParkingSource::OPERATOR, 30],
        ]);

it('assigns the expected confidence ceiling to each source', function (ParkingSource $source, float $expected) {
    expect($source->confidenceCeiling())
        ->toBe($expected);
})->with([
            'system' => [ParkingSource::SYSTEM, 1.00],
            'operator' => [ParkingSource::OPERATOR, 0.95],
            'sensor' => [ParkingSource::SENSOR, 0.90],
            'camera' => [ParkingSource::CAMERA, 0.85],
            'user' => [ParkingSource::USER, 0.60],
        ]);