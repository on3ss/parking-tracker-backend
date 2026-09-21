<?php

namespace App\Enums;

enum ParkingSource: string
{
    case USER = 'USER';
    case OPERATOR = 'OPERATOR';
    case SENSOR = 'SENSOR';
    case CAMERA = 'CAMERA';
    case SYSTEM = 'SYSTEM';
}
