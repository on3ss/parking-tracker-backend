<?php

namespace Database\Factories;

use App\Enums\AvailabilityStatus;
use App\Enums\ParkingFacilityType;
use App\Enums\ParkingStatus;
use App\Models\Location;
use App\Models\ParkingFacility;
use App\Models\ParkingProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ParkingFacilityFactory extends Factory
{
    protected $model = ParkingFacility::class;

    public function definition(): array
    {
        $name = fake()->company() . ' Parking';

        return [
            'parking_provider_id' => ParkingProvider::factory(),
            'location_id' => Location::factory(),

            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(1, 999999),

            'type' => ParkingFacilityType::PUBLIC ,
            'status' => ParkingStatus::ACTIVE,

            'capacity' => fake()->numberBetween(20, 500),

            'opening_time' => '06:00',
            'closing_time' => '22:00',

            'available_spaces' => null,
            'availability_status' => AvailabilityStatus::UNKNOWN,
            'availability_updated_at' => null,

            'description' => fake()->optional()->sentence(),
        ];
    }

    public function available(int $spaces = 20): static
    {
        return $this->state(fn() => [
            'available_spaces' => $spaces,
            'availability_status' => AvailabilityStatus::AVAILABLE,
            'availability_updated_at' => now(),
        ]);
    }

    public function limited(int $spaces = 5): static
    {
        return $this->state(fn() => [
            'available_spaces' => $spaces,
            'availability_status' => AvailabilityStatus::LIMITED,
            'availability_updated_at' => now(),
        ]);
    }

    public function full(): static
    {
        return $this->state(fn() => [
            'available_spaces' => 0,
            'availability_status' => AvailabilityStatus::FULL,
            'availability_updated_at' => now(),
        ]);
    }

    public function unavailable(): static
    {
        return $this->state(fn() => [
            'status' => ParkingStatus::TEMPORARILY_CLOSED,
            'available_spaces' => null,
            'availability_status' => AvailabilityStatus::UNKNOWN,
            'availability_updated_at' => now(),
        ]);
    }
}