<?php

namespace Database\Factories;

use App\Enums\ParkingProviderType;
use App\Models\ParkingProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParkingProviderFactory extends Factory
{
    protected $model = ParkingProvider::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => fake()->unique()->slug(),

            'type' => fake()->randomElement(
                ParkingProviderType::cases()
            ),

            'description' => fake()->optional()->sentence(),

            'is_active' => true,
        ];
    }
}