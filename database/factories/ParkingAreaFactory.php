<?php

namespace Database\Factories;

use App\Models\ParkingArea;
use App\Models\ParkingFacility;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParkingAreaFactory extends Factory
{
    protected $model = ParkingArea::class;

    public function definition(): array
    {
        return [
            'parking_facility_id' => ParkingFacility::factory(),

            'name' => fake()->randomElement([
                'Ground Floor',
                'Basement',
                'First Floor',
                'Two Wheeler Area',
            ]),

            'code' => strtoupper(fake()->unique()->lexify('AREA-???')),

            'vehicle_type' => fake()->randomElement([
                'ALL',
                'CAR',
                'MOTORCYCLE',
            ]),

            'capacity' => fake()->numberBetween(10, 200),

            'is_active' => true,
        ];
    }
}
