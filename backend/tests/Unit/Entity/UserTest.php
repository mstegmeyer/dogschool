<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    #[Test]
    public function getUserIdentifierReturnsUsername(): void
    {
        $user = new User();
        $user->setUsername('admin1');
        $user->setFullName('Admin');

        self::assertSame('admin1', $user->getUserIdentifier());
    }

    #[Test]
    public function getRolesContainsRoleAdmin(): void
    {
        $user = new User();
        self::assertContains('ROLE_ADMIN', $user->getRoles());
    }

    #[Test]
    public function phoneDefaultsToNull(): void
    {
        $user = new User();
        self::assertNull($user->getPhone());
    }

    #[Test]
    public function setPhoneStoresValue(): void
    {
        $user = new User();
        $user->setPhone('+49 171 1234567');
        self::assertSame('+49 171 1234567', $user->getPhone());
    }

    #[Test]
    public function idIsUuidOnConstruction(): void
    {
        $user = new User();
        self::assertNotEmpty($user->getId());
        self::assertSame(36, strlen($user->getId()));
    }
}
