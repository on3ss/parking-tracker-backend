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

            'occupied_spaces' => 0,
            'available_spaces' => 0,

            'confidence' => 1.0000,

            'reported_at' => now(),
        ];
    }

    public function forFacility(): static
    {
        return $this->state(fn() => [
            'parking_facility_id' => ParkingFacility::factory(),
            'street_parking_id' => null,
        ]);
    }

    public function forStreetParking(): static
    {
        return $this->state(fn() => [
            'parking_facility_id' => null,
            'street_parking_id' => StreetParking::factory(),
        ]);
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