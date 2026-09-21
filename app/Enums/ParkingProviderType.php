<?php

namespace App\Enums;

enum ParkingProviderType: string
{
    case MUNICIPAL = 'MUNICIPAL';
    case PRIVATE = 'PRIVATE';
    case COMMERCIAL = 'COMMERCIAL';
    case HOTEL = 'HOTEL';
    case HOSPITAL = 'HOSPITAL';
    case MALL = 'MALL';
    case OTHER = 'OTHER';
}
