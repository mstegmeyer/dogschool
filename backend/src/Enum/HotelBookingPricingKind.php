<?php

declare(strict_types=1);

namespace App\Enum;

enum HotelBookingPricingKind: string
{
    case DAYCARE = 'DAYCARE';
    case HOTEL = 'HOTEL';
}
