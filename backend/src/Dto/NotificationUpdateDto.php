<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class NotificationUpdateDto
{
    /**
     * @param string[]|null $courseIds null = don't change, empty array = make global
     */
    public function __construct(
        #[Assert\Length(max: 255)]
        public ?string $title = null,
        public ?string $message = null,
        /** @var string[]|null */
        public ?array $courseIds = null,
    ) {
    }
}
