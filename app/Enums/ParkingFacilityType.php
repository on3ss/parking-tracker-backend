<?php

namespace App\Enums;

enum ParkingFacilityType: string
{
    case PUBLIC = 'PUBLIC';
    case PRIVATE = 'PRIVATE';
    case COMMERCIAL = 'COMMERCIAL';
    case HOTEL = 'HOTEL';
    case HOSPITAL = 'HOSPITAL';
    case MALL = 'MALL';
    case OTHER = 'OTHER';
}
