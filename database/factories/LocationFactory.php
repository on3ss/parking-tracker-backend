<?php

namespace Database\Factories;

use App\Models\Location;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return [
            'address_line1' => fake()->streetAddress(),
            'address_line2' => null,
            'locality' => fake()->city(),
            'administrative_area' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country_code' => 'IN',
            'coordinates' => Point::makeGeodetic(
                latitude: fake()->latitude(8, 35),
                longitude: fake()->longitude(68, 97),
            ),
        ];
    }
}
