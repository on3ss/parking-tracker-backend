<?php

namespace Database\Seeders;

use App\Enums\AvailabilityStatus;
use App\Enums\ParkingFacilityType;
use App\Enums\ParkingProviderType;
use App\Enums\ParkingSource;
use App\Enums\ParkingStatus;
use App\Enums\StreetParkingType;
use App\Models\Location;
use App\Models\OccupancyReport;
use App\Models\ParkingArea;
use App\Models\ParkingFacility;
use App\Models\ParkingProvider;
use App\Models\StreetParking;
use Clickbar\Magellan\Data\Geometries\LineString;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Database\Seeder;

class ShillongParkingSeeder extends Seeder
{
    public function run(): void
    {
        $municipal = ParkingProvider::updateOrCreate(
            ['slug' => 'shillong-municipal-parking'],
            [
                'name' => 'Shillong Municipal Parking',
                'type' => ParkingProviderType::MUNICIPAL,
                'description' => 'Development/demo municipal parking provider.',
                'is_active' => true,
            ],
        );

        $private = ParkingProvider::updateOrCreate(
            ['slug' => 'shillong-private-parking'],
            [
                'name' => 'Shillong Private Parking',
                'type' => ParkingProviderType::PRIVATE,
                'description' => 'Development/demo private parking provider.',
                'is_active' => true,
            ],
        );

        /*
         * ---------------------------------------------------------
         * Police Bazar facility
         * ---------------------------------------------------------
         */

        $policeBazarLocation = $this->location(
            addressLine1: 'Police Bazar',
            locality: 'Police Bazar',
            postalCode: '793001',
            latitude: 25.5779199,
            longitude: 91.8837004,
        );

        $policeBazar = ParkingFacility::updateOrCreate(
            ['slug' => 'demo-police-bazar-parking'],
            [
                'parking_provider_id' => $municipal->id,
                'location_id' => $policeBazarLocation->id,

                'name' => 'Police Bazar Parking',
                'type' => ParkingFacilityType::PUBLIC,
                'status' => ParkingStatus::ACTIVE,

                'capacity' => 180,

                'opening_time' => '06:00',
                'closing_time' => '22:00',

                'available_spaces' => 42,
                'availability_status' => AvailabilityStatus::AVAILABLE,
                'availability_updated_at' => now(),

                'description' => 'Demo parking facility for local development.',
            ],
        );

        $this->area(
            facility: $policeBazar,
            name: 'Ground Floor',
            code: 'PB-GF',
            vehicleType: 'CAR',
            capacity: 100,
        );

        $this->area(
            facility: $policeBazar,
            name: 'Two Wheeler Area',
            code: 'PB-2W',
            vehicleType: 'MOTORCYCLE',
            capacity: 80,
        );

        $this->reportFacility(
            facility: $policeBazar,
            source: ParkingSource::SYSTEM,
            occupied: 138,
            available: 42,
        );

        /*
         * ---------------------------------------------------------
         * Laitumkhrah facility
         * ---------------------------------------------------------
         */

        $laitumkhrahLocation = $this->location(
            addressLine1: 'Laitumkhrah',
            locality: 'Laitumkhrah',
            postalCode: '793003',
            latitude: 25.5702,
            longitude: 91.89829,
        );

        $laitumkhrah = ParkingFacility::updateOrCreate(
            ['slug' => 'demo-laitumkhrah-parking'],
            [
                'parking_provider_id' => $private->id,
                'location_id' => $laitumkhrahLocation->id,

                'name' => 'Laitumkhrah Parking',
                'type' => ParkingFacilityType::COMMERCIAL,
                'status' => ParkingStatus::ACTIVE,

                'capacity' => 90,

                'opening_time' => '07:00',
                'closing_time' => '21:00',

                'available_spaces' => 8,
                'availability_status' => AvailabilityStatus::LIMITED,
                'availability_updated_at' => now(),

                'description' => 'Demo parking facility for local development.',
            ],
        );

        $this->area(
            facility: $laitumkhrah,
            name: 'Main Parking',
            code: 'LM-MAIN',
            vehicleType: 'CAR',
            capacity: 60,
        );

        $this->area(
            facility: $laitumkhrah,
            name: 'Two Wheeler Area',
            code: 'LM-2W',
            vehicleType: 'MOTORCYCLE',
            capacity: 30,
        );

        $this->reportFacility(
            facility: $laitumkhrah,
            source: ParkingSource::SYSTEM,
            occupied: 82,
            available: 8,
        );

        /*
         * ---------------------------------------------------------
         * Police Bazar street parking
         * ---------------------------------------------------------
         */

        $this->createStreetParking(
            provider: $municipal,
            slug: 'demo-police-bazar-road-west',
            name: 'Police Bazar Road — West Side',
            roadName: 'Police Bazar Road',
            side: 'LEFT',
            latitude: 25.5768,
            longitude: 91.8826,
            capacity: 24,
            available: 7,
            availability: AvailabilityStatus::LIMITED,
        );

        $this->createStreetParking(
            provider: null,
            slug: 'demo-police-bazar-road-east',
            name: 'Police Bazar Road — East Side',
            roadName: 'Police Bazar Road',
            side: 'RIGHT',
            latitude: 25.5765,
            longitude: 91.8832,
            capacity: 18,
            available: 3,
            availability: AvailabilityStatus::LIMITED,
        );

        /*
         * ---------------------------------------------------------
         * Laitumkhrah street parking
         * ---------------------------------------------------------
         */

        $this->createStreetParking(
            provider: null,
            slug: 'demo-laitumkhrah-road',
            name: 'Laitumkhrah Road Parking',
            roadName: 'Laitumkhrah Road',
            side: 'LEFT',
            latitude: 25.5707,
            longitude: 91.8977,
            capacity: 15,
            available: 0,
            availability: AvailabilityStatus::FULL,
        );
    }

    private function location(
        string $addressLine1,
        string $locality,
        string $postalCode,
        float $latitude,
        float $longitude,
    ): Location {
        return Location::updateOrCreate(
            [
                'address_line1' => $addressLine1,
                'locality' => $locality,
            ],
            [
                'address_line2' => null,
                'administrative_area' => 'Meghalaya',
                'postal_code' => $postalCode,
                'country_code' => 'IN',

                'coordinates' => Point::makeGeodetic(
                    latitude: $latitude,
                    longitude: $longitude,
                ),
            ],
        );
    }

    private function area(
        ParkingFacility $facility,
        string $name,
        string $code,
        string $vehicleType,
        int $capacity,
    ): ParkingArea {
        return ParkingArea::updateOrCreate(
            [
                'parking_facility_id' => $facility->id,
                'name' => $name,
            ],
            [
                'code' => $code,
                'vehicle_type' => $vehicleType,
                'capacity' => $capacity,
                'is_active' => true,
            ],
        );
    }

    private function reportFacility(
        ParkingFacility $facility,
        ParkingSource $source,
        int $occupied,
        int $available,
    ): void {
        OccupancyReport::create([
            'parking_facility_id' => $facility->id,
            'street_parking_id' => null,

            'user_id' => null,

            'source' => $source,

            'occupied_spaces' => $occupied,
            'available_spaces' => $available,

            'confidence' => 1.0000,

            'reported_at' => now(),
        ]);
    }

    private function createStreetParking(
        ?ParkingProvider $provider,
        string $slug,
        string $name,
        string $roadName,
        string $side,
        float $latitude,
        float $longitude,
        int $capacity,
        int $available,
        AvailabilityStatus $availability,
    ): StreetParking {
        $location = $this->location(
            addressLine1: $roadName,
            locality: str_contains($roadName, 'Police')
            ? 'Police Bazar'
            : 'Laitumkhrah',
            postalCode: str_contains($roadName, 'Police')
            ? '793001'
            : '793003',
            latitude: $latitude,
            longitude: $longitude,
        );

        $parking = StreetParking::updateOrCreate(
            ['slug' => $slug],
            [
                'parking_provider_id' => $provider?->id,
                'location_id' => $location->id,

                'name' => $name,
                'road_name' => $roadName,
                'side' => $side,

                'parking_type' => StreetParkingType::CURBSIDE,
                'status' => ParkingStatus::ACTIVE,

                'capacity' => $capacity,
                'available_spaces' => $available,

                'availability_status' => $availability,
                'availability_updated_at' => now(),

                'description' => 'Demo street parking for local development.',

                'geometry' => $this->streetGeometry(
                    latitude: $latitude,
                    longitude: $longitude,
                ),
            ],
        );

        OccupancyReport::updateOrCreate(
            [
                'street_parking_id' => $parking->id,
                'source' => ParkingSource::SYSTEM,
            ],
            [
                'parking_facility_id' => null,
                'user_id' => null,

                'occupied_spaces' => $capacity - $available,
                'available_spaces' => $available,

                'confidence' => 0.9000,

                'reported_at' => now(),
            ],
        );

        return $parking;
    }

    private function streetGeometry(
        float $latitude,
        float $longitude,
    ): LineString {
        /*
         * Rough 40–60m development/demo segment.
         *
         * This is deliberately approximate and is not a
         * surveyed or legal parking boundary.
         */
        $delta = 0.00025;

        return LineString::make([
            Point::makeGeodetic(
                latitude: $latitude - ($delta / 2),
                longitude: $longitude - $delta,
            ),
            Point::makeGeodetic(
                latitude: $latitude + ($delta / 2),
                longitude: $longitude + $delta,
            ),
        ]);
    }
}
