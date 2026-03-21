<?php

declare(strict_types=1);

namespace App\Enum;

enum CreditTransactionType: string
{
    case WEEKLY_GRANT = 'WEEKLY_GRANT';
    case BOOKING = 'BOOKING';
    case CANCELLATION = 'CANCELLATION';
    case MANUAL_ADJUSTMENT = 'MANUAL_ADJUSTMENT';
}
