<?php

declare(strict_types=1);

namespace App\Dto;

final class CustomerReviewDecisionDto
{
    public function __construct(
        public ?string $customerComment = null,
    ) {
    }
}
