<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity\Embeddable;

use App\Entity\Embeddable\Address;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AddressTest extends TestCase
{
    #[Test]
    public function settersExposeAssignedValues(): void
    {
        $address = (new Address())
            ->setStreet('Musterstrasse 1')
            ->setPostalCode('12345')
            ->setCity('Berlin')
            ->setCountry('DE');

        self::assertSame('Musterstrasse 1', $address->getStreet());
        self::assertSame('12345', $address->getPostalCode());
        self::assertSame('Berlin', $address->getCity());
        self::assertSame('DE', $address->getCountry());
    }

    #[Test]
    public function fieldsCanBeCleared(): void
    {
        $address = (new Address())
            ->setStreet('Musterstrasse 1')
            ->setPostalCode('12345')
            ->setCity('Berlin')
            ->setCountry('DE');

        $address
            ->setStreet(null)
            ->setPostalCode(null)
            ->setCity(null)
            ->setCountry(null);

        self::assertNull($address->getStreet());
        self::assertNull($address->getPostalCode());
        self::assertNull($address->getCity());
        self::assertNull($address->getCountry());
    }
}
