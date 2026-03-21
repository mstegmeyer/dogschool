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
    ) {
    }
}
