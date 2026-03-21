<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ContractRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        public string $dogId = '',
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?string $price = null,
        public ?int $coursesPerWeek = null,
    ) {
    }
}
