<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class NotificationCreateDto
{
    /**
     * @param string[]    $courseIds   empty array = global notification visible to all customers
     * @param string|null $pinnedUntil ISO 8601 datetime — pin notification until this deadline
     */
    public function __construct(
        /** @var string[] */
        #[Assert\All([new Assert\NotBlank(), new Assert\Uuid()])]
        public array $courseIds = [],
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $title = '',
        #[Assert\NotBlank]
        public string $message = '',
        public ?string $pinnedUntil = null,
    ) {
    }
}
