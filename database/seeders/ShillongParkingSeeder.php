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
use App\Support\Rbac\Concerns\InteractsWithTeams;
use App\Support\Rbac\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShillongParkingSeeder extends Seeder
{
    use InteractsWithTeams;

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->call(RolesAndPermissionsSeeder::class);

            $providers = ParkingProvider::factory()->count(8)->create();

            // Roles are per-team: create them for every provider up-front.
            foreach ($providers as $provider) {
                RolesAndPermissionsSeeder::syncTeamRoles($provider->id);
            }

            $this->seedTestOwner($providers->first());
            $this->seedUsersAndMemberships($providers);

            foreach ($providers as $provider) {
                $this->seedFacilities($provider);
                $this->seedStreetParkings($provider);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Users & memberships
    |--------------------------------------------------------------------------
    */

    private function seedTestOwner(ParkingProvider $provider): void
    {
        $owner = User::query()->firstOrCreate(
            ['email' => 'owner@example.com'],
            ['name' => 'Test Owner', 'password' => bcrypt('password')],
        );

        $this->attachUserToProvider($owner, $provider, RoleEnum::Owner);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ParkingProvider>  $providers
     */
    private function seedUsersAndMemberships($providers): void
    {
        $users = User::factory()->count(25)->create();
        $roles = RoleEnum::cases();

        // Group (user, role) pairs by provider so we only flip the team
        // context once per provider instead of once per assignment.
        $byProvider = [];

        foreach ($users as $user) {
            foreach ($providers->random(rand(1, 3)) as $provider) {
                $byProvider[$provider->id][] = [
                    'user' => $user,
                    'role' => $roles[array_rand($roles)],
                ];
            }
        }

        foreach ($byProvider as $providerId => $assignments) {
            $this->withTeam($providerId, function () use ($assignments): void {
                foreach ($assignments as ['user' => $user, 'role' => $role]) {
                    $user->syncRoles([$role->value]);
                }
            });
        }
    }

    /**
     * Create the domain membership row and assign the spatie role
     * within the provider's team context.
     */
    private function attachUserToProvider(
        User $user,
        ParkingProvider $provider,
        RoleEnum $role,
    ): void {
        ProviderMembership::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'parking_provider_id' => $provider->id,
            ],
            ['role' => $role->value],
        );

        $this->withTeam(
            $provider->id,
            fn() => $user->syncRoles([$role->value]),
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Facilities, areas, reports
    |--------------------------------------------------------------------------
    */

    private function seedFacilities(ParkingProvider $provider): void
    {
        for ($i = 0, $n = rand(2, 4); $i < $n; $i++) {
            $location = Location::factory()->create();

            $factory = match ($i % 4) {
                1 => ParkingFacility::factory()->available(rand(5, 40)),
                2 => ParkingFacility::factory()->limited(rand(1, 5)),
                3 => ParkingFacility::factory()->full(),
                default => ParkingFacility::factory(),
            };

            /** @var ParkingFacility $facility */
            $facility = $factory->create([
                'parking_provider_id' => $provider->id,
                'location_id' => $location->id,
            ]);

            ParkingArea::factory()
                ->count(rand(1, 4))
                ->create(['parking_facility_id' => $facility->id]);

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

            $factory = match ($i % 3) {
                1 => StreetParking::factory()->available(rand(1, 10)),
                2 => StreetParking::factory()->full(),
                default => StreetParking::factory(),
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