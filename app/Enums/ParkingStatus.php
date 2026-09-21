<?php

namespace App\Enums;

enum ParkingStatus: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case TEMPORARILY_CLOSED = 'TEMPORARILY_CLOSED';
}
