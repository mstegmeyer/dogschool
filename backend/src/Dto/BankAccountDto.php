<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class BankAccountDto
{
    public function __construct(
        #[Assert\Length(max: 34)]
        public ?string $iban = null,
        #[Assert\Length(max: 11)]
        public ?string $bic = null,
        #[Assert\Length(max: 255)]
        public ?string $accountHolder = null,
    ) {
    }
}
