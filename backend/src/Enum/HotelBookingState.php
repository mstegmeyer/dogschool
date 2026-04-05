<?php

declare(strict_types=1);

namespace App\Enum;

enum HotelBookingState: string
{
    case REQUESTED = 'REQUESTED';
    case CONFIRMED = 'CONFIRMED';
    case DECLINED = 'DECLINED';
}
