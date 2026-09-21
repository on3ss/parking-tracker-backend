<?php

namespace App\Enums;

enum ParkingProviderType: string
{
    case PRIVATE = 'private';

    case MUNICIPAL = 'municipal';

    case COMMERCIAL = 'commercial';

    case HOTEL = 'hotel';

    case HOSPITAL = 'hospital';

    case MALL = 'mall';

    case OTHER = 'other';
}