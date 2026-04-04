<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Customer;
use App\Entity\Dog;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DogTest extends TestCase
{
    #[Test]
    public function constructorGeneratesUuid(): void
    {
        $dog = new Dog();

        self::assertNotEmpty($dog->getId());
        self::assertSame(36, strlen($dog->getId()));
    }

    #[Test]
    public function settersExposeAssignedValues(): void
    {
        $customer = (new Customer())
            ->setName('Customer')
            ->setEmail('customer@example.com')
            ->setPassword('hashed');

        $dog = (new Dog())
            ->setName('Rex')
            ->setColor('Brown')
            ->setGender('male')
            ->setRace('Border Collie')
            ->setCustomer($customer);

        self::assertSame('Rex', $dog->getName());
        self::assertSame('Brown', $dog->getColor());
        self::assertSame('male', $dog->getGender());
        self::assertSame('Border Collie', $dog->getRace());
        self::assertSame($customer, $dog->getCustomer());

        $dog->setCustomer(null);
        self::assertNull($dog->getCustomer());
    }
}
