<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CustomerUpdateDto
{
    public function __construct(
        #[Assert\Length(max: 255)]
        public ?string $name = null,
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public ?string $email = null,
        #[Assert\Length(min: 1)]
        public ?string $password = null,
        public ?AddressDto $address = null,
        public ?BankAccountDto $bankAccount = null,
    ) {
    }
}
