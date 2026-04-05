<?php

declare(strict_types=1);

namespace App\Dto;

use App\Support\LocalDateTime;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class HotelBookingRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Bitte einen Hund auswählen.')]
        #[Assert\Uuid(message: 'Ungültiger Hund.')]
        public ?string $dogId = null,
        #[Assert\NotBlank(message: 'Bitte einen Beginn auswählen.')]
        public ?string $startAt = null,
        #[Assert\NotBlank(message: 'Bitte ein Ende auswählen.')]
        public ?string $endAt = null,
        #[Assert\Range(
            min: 1,
            max: 200,
            notInRangeMessage: 'Schulterhöhe muss zwischen {{ min }} und {{ max }} cm liegen.',
        )]
        public ?int $currentShoulderHeightCm = null,
    ) {
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        $startAt = LocalDateTime::parseWallTime($this->startAt);
        if ($this->startAt !== null && $this->startAt !== '' && $startAt === null) {
            $context
                ->buildViolation('Ungültiger Startzeitpunkt.')
                ->atPath('startAt')
                ->addViolation();
        }

        $endAt = LocalDateTime::parseWallTime($this->endAt);
        if ($this->endAt !== null && $this->endAt !== '' && $endAt === null) {
            $context
                ->buildViolation('Ungültiger Endzeitpunkt.')
                ->atPath('endAt')
                ->addViolation();
        }

        $this->validateHandoverWindow($startAt, 'startAt', 'Beginn muss zwischen 06:00 und 22:00 Uhr liegen.', $context);
        $this->validateHandoverWindow($endAt, 'endAt', 'Ende muss zwischen 06:00 und 22:00 Uhr liegen.', $context);

        if ($startAt !== null && $endAt !== null && $endAt <= $startAt) {
            $context
                ->buildViolation('Ende muss nach dem Beginn liegen.')
                ->atPath('endAt')
                ->addViolation();
        }
    }

    private function validateHandoverWindow(
        ?\DateTimeImmutable $value,
        string $path,
        string $message,
        ExecutionContextInterface $context,
    ): void {
        if ($value === null) {
            return;
        }

        $minutes = ((int) $value->format('H') * 60) + (int) $value->format('i');
        if ($minutes < 360 || $minutes > 1320) {
            $context
                ->buildViolation($message)
                ->atPath($path)
                ->addViolation();
        }
    }
}
