<?php

use App\Models\ParkingFacility;
use App\Models\StreetParking;
use App\Models\User;

return [
    'user' => User::class,
    'facility' => ParkingFacility::class,
    'street' => StreetParking::class,
];
