<?php

namespace App\Enums;

enum AvailabilityStatus: string
{
    case UNKNOWN = 'UNKNOWN';
    case AVAILABLE = 'AVAILABLE';
    case LIMITED = 'LIMITED';
    case FULL = 'FULL';
}