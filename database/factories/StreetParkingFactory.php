<?php

namespace Database\Factories;

use App\Enums\AvailabilityStatus;
use App\Enums\ParkingStatus;
use App\Enums\StreetParkingType;
use App\Models\Location;
use App\Models\ParkingProvider;
use App\Models\StreetParking;
use Clickbar\Magellan\Data\Geometries\LineString;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class StreetParkingFactory extends Factory
{
    protected $model = StreetParking::class;

    public function definition(): array
    {
        $name = fake()->streetName().' Parking';

        $latitude = fake()->latitude(8, 35);
        $longitude = fake()->longitude(68, 97);

        $delta = 0.00025;

        return [
            'parking_provider_id' => ParkingProvider::factory(),
            'location_id' => Location::factory(),

            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),

            'road_name' => fake()->streetName(),
            'side' => 'RIGHT',
            'parking_type' => StreetParkingType::CURBSIDE,
            'status' => ParkingStatus::ACTIVE,

            'capacity' => fake()->numberBetween(10, 50),
            'available_spaces' => null,
            'availability_status' => AvailabilityStatus::UNKNOWN,
            'availability_updated_at' => null,

            'description' => fake()->optional()->sentence(),

            'geometry' => LineString::make([
                Point::makeGeodetic(
                    latitude: $latitude - ($delta / 2),
                    longitude: $longitude - $delta,
                ),
                Point::makeGeodetic(
                    latitude: $latitude + ($delta / 2),
                    longitude: $longitude + $delta,
                ),
            ]),
        ];
    }
}
