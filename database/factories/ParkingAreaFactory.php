<?php

namespace Database\Factories;

use App\Models\ParkingArea;
use App\Models\ParkingFacility;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParkingAreaFactory extends Factory
{
    protected $model = ParkingArea::class;

    /**
     * Monotonic counter shared across all factory calls in a process.
     * Combined with a random 3-char tag, this guarantees:
     *   - `name` is unique per facility (DB constraint).
     *   - `code` is unique without relying on fake()->unique()'s pool.
     */
    private static int $sequence = 0;

    public function definition(): array
    {
        $base = fake()->randomElement([
            'Ground Floor',
            'Basement',
            'First Floor',
            'Two Wheeler Area',
        ]);

        $n = ++self::$sequence;

        return [
            'parking_facility_id' => ParkingFacility::factory(),

            'name' => "{$base} #{$n}",

            'code' => 'AREA-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT),

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
