<?php

declare(strict_types=1);

namespace App\Enum;

enum HotelBookingState: string
{
    case REQUESTED = 'REQUESTED';
    case PENDING_CUSTOMER_APPROVAL = 'PENDING_CUSTOMER_APPROVAL';
    case CONFIRMED = 'CONFIRMED';
    case DECLINED = 'DECLINED';
}
