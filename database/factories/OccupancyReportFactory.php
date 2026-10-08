<?php

namespace Database\Factories;

use App\Enums\ParkingSource;
use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OccupancyReportFactory extends Factory
{
    protected $model = OccupancyReport::class;

    public function definition(): array
    {
        // Default source is one that does NOT require a user_id.
        // Use ->fromUser() when you want a USER-sourced report.
        return [
            'parking_facility_id' => null,
            'street_parking_id' => null,
            'user_id' => null,

            'source' => ParkingSource::OPERATOR,

            'occupied_spaces' => 0,
            'available_spaces' => 0,

            'reported_at' => now(),
        ];
    }

    public function forFacility(?ParkingFacility $facility = null): static
    {
        return $this->state(function () use ($facility) {
            $facility ??= ParkingFacility::factory()->create();

            $capacity = $facility->capacity
                ?? fake()->numberBetween(20, 500);

            $available = fake()->numberBetween(0, $capacity);

            return [
                'parking_facility_id' => $facility->id,
                'street_parking_id' => null,
                'occupied_spaces' => $capacity - $available,
                'available_spaces' => $available,
            ];
        });
    }

    public function forStreetParking(?StreetParking $streetParking = null): static
    {
        return $this->state(function () use ($streetParking) {
            $streetParking ??= StreetParking::factory()->create();

            $capacity = $streetParking->capacity
                ?? fake()->numberBetween(10, 50);

            $available = fake()->numberBetween(0, $capacity);

            return [
                'parking_facility_id' => null,
                'street_parking_id' => $streetParking->id,
                'occupied_spaces' => $capacity - $available,
                'available_spaces' => $available,
            ];
        });
    }

    public function fromUser(?User $user = null): static
    {
        return $this->state(function () use ($user) {
            $user ??= User::factory()->create();

            return [
                'source' => ParkingSource::USER,
                'user_id' => $user->id,
            ];
        });
    }

    public function fromOperator(): static
    {
        return $this->state([
            'source' => ParkingSource::OPERATOR,
            'user_id' => null,
        ]);
    }

    public function fromSensor(): static
    {
        return $this->state([
            'source' => ParkingSource::SENSOR,
            'user_id' => null,
        ]);
    }

    public function fromCamera(): static
    {
        return $this->state([
            'source' => ParkingSource::CAMERA,
            'user_id' => null,
        ]);
    }
}
