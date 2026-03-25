<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CourseTypeCreateDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 20)]
        public string $code = '',
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $name = '',
        #[Assert\Choice(choices: ['RECURRING', 'ONE_TIME', 'DROP_IN'])]
        public string $recurrenceKind = 'RECURRING',
    ) {
    }
}
