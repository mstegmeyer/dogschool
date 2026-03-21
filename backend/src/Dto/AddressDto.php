<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class AddressDto
{
    public function __construct(
        #[Assert\Length(max: 255)]
        public ?string $street = null,
        #[Assert\Length(max: 20)]
        public ?string $postalCode = null,
        #[Assert\Length(max: 100)]
        public ?string $city = null,
        #[Assert\Length(max: 100)]
        public ?string $country = null,
    ) {
    }
}
