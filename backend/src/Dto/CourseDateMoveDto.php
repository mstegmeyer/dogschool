<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CourseDateMoveDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Date]
        public string $date = '',
        public ?string $startTime = null,
        public ?string $endTime = null,
    ) {
    }
}
