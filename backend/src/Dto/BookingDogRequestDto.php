<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class BookingDogRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'dogId is required.')]
        #[Assert\Uuid(message: 'dogId must be a valid UUID.')]
        public ?string $dogId = null,
    ) {
    }
}
