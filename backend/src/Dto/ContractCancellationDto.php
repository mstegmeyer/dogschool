<?php

declare(strict_types=1);

namespace App\Dto;

final class ContractCancellationDto
{
    public function __construct(
        public ?string $endDate = null,
    ) {
    }
}
