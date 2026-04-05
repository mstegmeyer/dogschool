<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class RoomUpsertDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $name = '',
        #[Assert\NotNull(message: 'Bitte eine gültige Raumgröße angeben.')]
        #[Assert\Positive]
        public ?int $squareMeters = null,
    ) {
    }
}
