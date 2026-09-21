<?php

namespace Database\Factories;

use App\Enums\AvailabilityStatus;
use App\Enums\ParkingStatus;
use App\Enums\StreetParkingType;
use App\Models\Location;
use App\Models\ParkingProvider;
use App\Models\StreetParking;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class StreetParkingFactory extends Factory
{
    protected $model = StreetParking::class;

    public function definition(): array
    {
        $name = fake()->streetName().' Parking';

        return [
            'parking_provider_id' => ParkingProvider::factory(),
            'location_id' => Location::factory(),

            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'road_name' => fake()->streetName(),

            'side' => fake()->randomElement([
                'LEFT',
                'RIGHT',
            ]),

            'parking_type' => StreetParkingType::CURBSIDE,
            'status' => ParkingStatus::ACTIVE,

            'capacity' => fake()->numberBetween(5, 50),

            'available_spaces' => null,
            'availability_status' => AvailabilityStatus::UNKNOWN,
            'availability_updated_at' => null,

            'description' => null,
        ];
    }

    public function unmanaged(): static
    {
        return $this->state(fn () => [
            'parking_provider_id' => null,
        ]);
    }

    public function available(int $spaces = 5): static
    {
        return $this->state(fn () => [
            'available_spaces' => $spaces,
            'availability_status' => AvailabilityStatus::AVAILABLE,
            'availability_updated_at' => now(),
        ]);
    }

    public function limited(int $spaces = 2): static
    {
        return $this->state(fn () => [
            'available_spaces' => $spaces,
            'availability_status' => AvailabilityStatus::LIMITED,
            'availability_updated_at' => now(),
        ]);
    }

    public function full(): static
    {
        return $this->state(fn () => [
            'available_spaces' => 0,
            'availability_status' => AvailabilityStatus::FULL,
            'availability_updated_at' => now(),
        ]);
    }
}
