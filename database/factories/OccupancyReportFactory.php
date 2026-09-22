<?php

namespace Database\Factories;

use App\Enums\ParkingSource;
use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Illuminate\Database\Eloquent\Factories\Factory;

class OccupancyReportFactory extends Factory
{
    protected $model = OccupancyReport::class;

    public function definition(): array
    {
        return [
            'parking_facility_id' => null,
            'street_parking_id' => null,
            'user_id' => null,

            'source' => ParkingSource::USER,

            'occupied_spaces' => null,

            'available_spaces' => fake()->numberBetween(
                0,
                20,
            ),

            'confidence' => fake()->randomFloat(
                4,
                0.5,
                1,
            ),

            'reported_at' => now(),
        ];
    }

    public function forFacility(
        ?ParkingFacility $facility = null,
    ): static {
        return $this->state(function () use ($facility) {
            $facility ??= ParkingFacility::factory()->create();

            return [
                'parking_facility_id' => $facility->id,
                'street_parking_id' => null,
            ];
        });
    }

    public function forStreetParking(
        ?StreetParking $streetParking = null,
    ): static {
        return $this->state(function () use ($streetParking) {
            $streetParking ??= StreetParking::factory()->create();

            return [
                'parking_facility_id' => null,
                'street_parking_id' => $streetParking->id,
            ];
        });
    }

    public function fromOperator(): static
    {
        return $this->state([
            'source' => ParkingSource::OPERATOR,
        ]);
    }

    public function fromSensor(): static
    {
        return $this->state([
            'source' => ParkingSource::SENSOR,
        ]);
    }

    public function fromCamera(): static
    {
        return $this->state([
            'source' => ParkingSource::CAMERA,
        ]);
    }
}
