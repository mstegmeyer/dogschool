<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CoursePayloadDto
{
    public function __construct(
        #[Assert\Range(min: 1, max: 7)]
        public int $dayOfWeek = 1,
        #[Assert\NotBlank]
        #[Assert\Length(max: 5)]
        public string $startTime = '',
        #[Assert\NotBlank]
        #[Assert\Length(max: 5)]
        public string $endTime = '',
        /** Course type code (required on create; optional on update). See Kursübersicht e.g. JUHU, MH, AGI, TK. */
        #[Assert\Length(max: 20)]
        public string $typeCode = '',
        #[Assert\Range(min: 0, max: 4)]
        public int $level = 0,
        #[Assert\Uuid]
        public ?string $trainerId = null,
        /** Customer special wishes / comment. */
        public ?string $comment = null,
        public ?bool $archived = null,
    ) {
    }
}
