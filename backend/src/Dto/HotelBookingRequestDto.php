<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class HotelBookingRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'dogId is required.')]
        #[Assert\Uuid(message: 'dogId must be a valid UUID.')]
        public ?string $dogId = null,
        #[Assert\NotBlank(message: 'startAt is required.')]
        public ?string $startAt = null,
        #[Assert\NotBlank(message: 'endAt is required.')]
        public ?string $endAt = null,
        #[Assert\Positive]
        #[Assert\LessThanOrEqual(200)]
        public ?int $currentShoulderHeightCm = null,
    ) {
    }
}
