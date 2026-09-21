<?php

namespace Database\Seeders;

use App\Models\ParkingProvider;
use Illuminate\Database\Seeder;

class ParkingProviderSeeder extends Seeder
{
    public function run(): void
    {
        ParkingProvider::updateOrCreate(
            ['slug' => 'shillong-municipal-corporation'],
            [
                'name' => 'Shillong Municipal Corporation',
                'type' => 'MUNICIPAL',
                'description' => 'Municipal parking provider.',
                'is_active' => true,
            ],
        );

        ParkingProvider::updateOrCreate(
            ['slug' => 'private-parking-operators'],
            [
                'name' => 'Private Parking Operators',
                'type' => 'PRIVATE',
                'description' => 'Private and commercial parking providers.',
                'is_active' => true,
            ],
        );
    }
}