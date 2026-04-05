<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class HotelBookingRoomAssignDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'roomId is required.')]
        #[Assert\Uuid(message: 'roomId must be a valid UUID.')]
        public ?string $roomId = null,
    ) {
    }
}
