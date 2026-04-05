<?php

declare(strict_types=1);

namespace App\Enum;

enum ContractState: string
{
    case REQUESTED = 'REQUESTED';
    case PENDING_CUSTOMER_APPROVAL = 'PENDING_CUSTOMER_APPROVAL';
    case ACTIVE = 'ACTIVE';
    case DECLINED = 'DECLINED';
    case CANCELLED = 'CANCELLED';
}
