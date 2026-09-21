<?php

namespace App\Enums;

enum StreetParkingType: string
{
    case CURBSIDE = 'CURBSIDE';
    case PARALLEL = 'PARALLEL';
    case ANGLED = 'ANGLED';
    case PERPENDICULAR = 'PERPENDICULAR';
    case OTHER = 'OTHER';
}