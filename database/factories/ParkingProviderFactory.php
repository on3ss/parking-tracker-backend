<?php

namespace Database\Factories;

use App\Enums\ParkingProviderType;
use App\Models\ParkingProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ParkingProviderFactory extends Factory
{
    protected $model = ParkingProvider::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),

            'type' => fake()->randomElement(
                ParkingProviderType::cases()
            ),

            'description' => fake()->optional()->sentence(),

            'is_active' => true,
        ];
    }
}
