<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class HotelBookingRoomAssignDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Bitte einen Raum auswählen.')]
        #[Assert\Uuid(message: 'Ungültiger Raum.')]
        public ?string $roomId = null,
    ) {
    }
}
