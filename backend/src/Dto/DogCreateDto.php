<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class DogCreateDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $name = '',
        #[Assert\Length(max: 100)]
        public ?string $color = null,
        #[Assert\Length(max: 20)]
        public ?string $gender = null,
        #[Assert\Length(max: 100)]
        public ?string $race = null,
        #[Assert\NotNull(message: 'Die Schulterhöhe ist erforderlich.')]
        #[Assert\Range(
            min: 1,
            max: 200,
            notInRangeMessage: 'Die Schulterhöhe muss zwischen {{ min }} und {{ max }} cm liegen.',
        )]
        public ?int $shoulderHeightCm = null,
    ) {
    }
}
