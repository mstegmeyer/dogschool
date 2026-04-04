<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Customer;
use App\Entity\PushDevice;
use App\Entity\User;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PushDeviceTest extends TestCase
{
    #[Test]
    public function constructorInitializesIdAndTimestamps(): void
    {
        $before = new \DateTimeImmutable();
        $device = new PushDevice();
        $after = new \DateTimeImmutable();

        self::assertSame(36, strlen($device->getId()));
        self::assertGreaterThanOrEqual($before, $device->getCreatedAt());
        self::assertLessThanOrEqual($after, $device->getCreatedAt());
        self::assertEquals($device->getCreatedAt(), $device->getUpdatedAt());
        self::assertEquals($device->getCreatedAt(), $device->getLastSeenAt());
    }

    #[Test]
    public function settersExposeAssignedValues(): void
    {
        $device = (new PushDevice())
            ->setToken('device-token')
            ->setPlatform('web')
            ->setProvider('webpush')
            ->setDeviceName('Safari on Mac');

        self::assertSame('device-token', $device->getToken());
        self::assertSame('web', $device->getPlatform());
        self::assertSame('webpush', $device->getProvider());
        self::assertSame('Safari on Mac', $device->getDeviceName());

        $device->setDeviceName(null);
        self::assertNull($device->getDeviceName());
    }

    #[Test]
    public function assigningACustomerClearsTheUserAndViceVersa(): void
    {
        $customer = (new Customer())
            ->setName('Customer')
            ->setEmail('customer@example.com')
            ->setPassword('hashed');
        $user = (new User())
            ->setUsername('admin')
            ->setFullName('Admin User')
            ->setPassword('hashed');

        $device = (new PushDevice())->setUser($user);
        self::assertSame($user, $device->getUser());
        self::assertNull($device->getCustomer());

        $device->setCustomer($customer);
        self::assertSame($customer, $device->getCustomer());
        self::assertNull($device->getUser());

        $device->setUser($user);
        self::assertSame($user, $device->getUser());
        self::assertNull($device->getCustomer());
    }

    #[Test]
    public function touchRefreshesUpdatedAndLastSeenTimestamps(): void
    {
        $device = new PushDevice();
        $oldUpdatedAt = new \DateTimeImmutable('-2 days');
        $oldLastSeenAt = new \DateTimeImmutable('-1 day');

        $updatedAt = new \ReflectionProperty(PushDevice::class, 'updatedAt');
        $lastSeenAt = new \ReflectionProperty(PushDevice::class, 'lastSeenAt');
        $updatedAt->setValue($device, $oldUpdatedAt);
        $lastSeenAt->setValue($device, $oldLastSeenAt);

        self::assertSame($device, $device->touch());
        self::assertGreaterThan($oldUpdatedAt, $device->getUpdatedAt());
        self::assertGreaterThan($oldLastSeenAt, $device->getLastSeenAt());
    }
}
