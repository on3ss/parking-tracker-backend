<?php

use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow(
        Carbon::parse('2026-10-22 12:00:00'),
    );
});

afterEach(function () {
    Carbon::setTestNow();
});

it('includes never-reported records only in the broader scope', function () {
    $neverReported = ParkingFacility::factory()->create([
        'availability_updated_at' => null,
    ]);

    expect(
        ParkingFacility::query()
            ->staleAvailability()
            ->whereKey($neverReported->getKey())
            ->exists(),
    )->toBeFalse();

    expect(
        ParkingFacility::query()
            ->staleOrNeverReportedAvailability()
            ->whereKey($neverReported->getKey())
            ->exists(),
    )->toBeTrue();
});

it('includes records older than the freshness cutoff in both scopes', function () {
    $oldTimestamp = now()->subHour()->subSecond();

    $facility = ParkingFacility::factory()->create([
        'availability_updated_at' => $oldTimestamp,
    ]);

    $streetParking = StreetParking::factory()->create([
        'availability_updated_at' => $oldTimestamp,
    ]);

    expect(
        ParkingFacility::query()
            ->staleAvailability()
            ->whereKey($facility->getKey())
            ->exists(),
    )->toBeTrue();

    expect(
        StreetParking::query()
            ->staleOrNeverReportedAvailability()
            ->whereKey($streetParking->getKey())
            ->exists(),
    )->toBeTrue();
});

it('does not classify a record exactly at the cutoff as stale', function () {
    $facility = ParkingFacility::factory()->create([
        'availability_updated_at' => now()->subHour(),
    ]);

    expect(
        ParkingFacility::query()
            ->staleAvailability()
            ->whereKey($facility->getKey())
            ->exists(),
    )->toBeFalse();
});
