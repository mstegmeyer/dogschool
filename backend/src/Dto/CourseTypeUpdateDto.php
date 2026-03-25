<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CourseTypeUpdateDto
{
    public function __construct(
        #[Assert\Length(max: 20)]
        public ?string $code = null,
        #[Assert\Length(max: 255)]
        public ?string $name = null,
        #[Assert\Choice(choices: ['RECURRING', 'ONE_TIME', 'DROP_IN'])]
        public ?string $recurrenceKind = null,
    ) {
    }
}
