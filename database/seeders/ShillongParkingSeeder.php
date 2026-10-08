<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\OccupancyReport;
use App\Models\ParkingArea;
use App\Models\ParkingFacility;
use App\Models\ParkingProvider;
use App\Models\ProviderMembership;
use App\Models\StreetParking;
use App\Models\User;
use Illuminate\Database\Seeder;

class ShillongParkingSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |------------------------------------------------------------------
        | 1. Known "owner" test account (optional but handy)
        |------------------------------------------------------------------
        */
        $owner = User::factory()->create([
            'name' => 'Test Owner',
            'email' => 'owner@example.com',
        ]);

        /*
        |------------------------------------------------------------------
        | 2. Providers
        |------------------------------------------------------------------
        */
        $providers = ParkingProvider::factory()->count(8)->create();

        // Attach the test owner to the first provider as owner
        ProviderMembership::create([
            'user_id' => $owner->id,
            'parking_provider_id' => $providers->first()->id,
            'role' => 'owner',
        ]);

        /*
        |------------------------------------------------------------------
        | 3. Users (25 total) + provider memberships
        |------------------------------------------------------------------
        */
        $users = User::factory()->count(25)->create();
        $roles = ['owner', 'admin', 'manager', 'viewer'];

        foreach ($users as $user) {
            // Every user belongs to 1–3 providers
            $providers->random(rand(1, 3))->each(
                function (ParkingProvider $provider) use ($user, $roles) {
                    ProviderMembership::create([
                        'user_id' => $user->id,
                        'parking_provider_id' => $provider->id,
                        'role' => $roles[array_rand($roles)],
                    ]);
                }
            );
        }

        /*
        |------------------------------------------------------------------
        | 4. For each provider: facilities, areas, street parkings, reports
        |------------------------------------------------------------------
        */
        foreach ($providers as $provider) {
            $this->seedFacilities($provider);
            $this->seedStreetParkings($provider);
        }
    }

    private function seedFacilities(ParkingProvider $provider): void
    {
        for ($i = 0, $n = rand(2, 4); $i < $n; $i++) {
            $location = Location::factory()->create();

            $factory = ParkingFacility::factory();

            $factory = match ($i % 4) {
                1 => $factory->available(rand(5, 40)),
                2 => $factory->limited(rand(1, 5)),
                3 => $factory->full(),
                default => $factory,
            };

            /** @var ParkingFacility $facility */
            $facility = $factory->create([
                'parking_provider_id' => $provider->id,
                'location_id' => $location->id,
            ]);

            // Parking areas
            ParkingArea::factory()
                ->count(rand(1, 4))
                ->create(['parking_facility_id' => $facility->id]);

            // Occupancy reports
            OccupancyReport::factory()
                ->count(rand(3, 10))
                ->forFacility($facility)
                ->create();
        }
    }

    private function seedStreetParkings(ParkingProvider $provider): void
    {
        for ($i = 0, $n = rand(2, 5); $i < $n; $i++) {
            $location = Location::factory()->create();

            $factory = StreetParking::factory();

            $factory = match ($i % 3) {
                1 => $factory->available(rand(1, 10)),
                2 => $factory->full(),
                default => $factory,
            };

            /** @var StreetParking $street */
            $street = $factory->create([
                'parking_provider_id' => $provider->id,
                'location_id' => $location->id,
            ]);

            OccupancyReport::factory()
                ->count(rand(2, 8))
                ->forStreetParking($street)
                ->create();
        }
    }
}
