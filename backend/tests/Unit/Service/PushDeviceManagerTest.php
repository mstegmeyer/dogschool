<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\PushDeviceUpsertDto;
use App\Entity\Customer;
use App\Entity\PushDevice;
use App\Entity\User;
use App\Repository\PushDeviceRepository;
use App\Service\PushDeviceManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PushDeviceManagerTest extends TestCase
{
    private PushDeviceRepository&MockObject $pushDeviceRepository;
    private PushDeviceManager $manager;

    protected function setUp(): void
    {
        $this->pushDeviceRepository = $this->createMock(PushDeviceRepository::class);
        $this->manager = new PushDeviceManager($this->pushDeviceRepository);
    }

    private function makeCustomer(string $email = 'customer@example.com'): Customer
    {
        return (new Customer())
            ->setName('Customer')
            ->setEmail($email)
            ->setPassword('hashed');
    }

    private function makeUser(string $username = 'admin'): User
    {
        return (new User())
            ->setUsername($username)
            ->setPassword('hashed')
            ->setFullName('Admin User');
    }

    private function makeDto(string $token = 'device-token'): PushDeviceUpsertDto
    {
        $dto = new PushDeviceUpsertDto();
        $dto->token = $token;
        $dto->platform = 'web';
        $dto->provider = 'webpush';
        $dto->deviceName = 'Safari on Mac';

        return $dto;
    }

    #[Test]
    public function registerForCustomerCreatesAndPersistsANewDevice(): void
    {
        $customer = $this->makeCustomer();
        $dto = $this->makeDto();

        $this->pushDeviceRepository
            ->expects(self::once())
            ->method('findOneByToken')
            ->with('device-token')
            ->willReturn(null);

        $this->pushDeviceRepository
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(function (PushDevice $device) use ($customer): bool {
                self::assertSame('device-token', $device->getToken());
                self::assertSame('web', $device->getPlatform());
                self::assertSame('webpush', $device->getProvider());
                self::assertSame('Safari on Mac', $device->getDeviceName());
                self::assertSame($customer, $device->getCustomer());
                self::assertNull($device->getUser());

                return true;
            }));

        $device = $this->manager->registerForCustomer($customer, $dto);

        self::assertInstanceOf(PushDevice::class, $device);
        self::assertSame($customer, $device->getCustomer());
    }

    #[Test]
    public function registerForUserReusesTheExistingDeviceAndClearsCustomerOwnership(): void
    {
        $existingCustomer = $this->makeCustomer('existing@example.com');
        $user = $this->makeUser();
        $dto = $this->makeDto('shared-token');
        $existingDevice = (new PushDevice())
            ->setToken('shared-token')
            ->setPlatform('ios')
            ->setProvider('apns')
            ->setCustomer($existingCustomer);

        $this->pushDeviceRepository
            ->expects(self::once())
            ->method('findOneByToken')
            ->with('shared-token')
            ->willReturn($existingDevice);

        $this->pushDeviceRepository
            ->expects(self::once())
            ->method('save')
            ->with($existingDevice);

        $device = $this->manager->registerForUser($user, $dto);

        self::assertSame($existingDevice, $device);
        self::assertSame('web', $device->getPlatform());
        self::assertSame('webpush', $device->getProvider());
        self::assertSame('Safari on Mac', $device->getDeviceName());
        self::assertSame($user, $device->getUser());
        self::assertNull($device->getCustomer());
    }

    #[Test]
    public function unregisterForCustomerRemovesOwnedDevice(): void
    {
        $customer = $this->makeCustomer();
        $device = (new PushDevice())
            ->setToken('device-token')
            ->setPlatform('web')
            ->setProvider('webpush')
            ->setCustomer($customer);

        $this->pushDeviceRepository
            ->expects(self::once())
            ->method('findOwnedByCustomerAndToken')
            ->with($customer, 'device-token')
            ->willReturn($device);

        $this->pushDeviceRepository
            ->expects(self::once())
            ->method('remove')
            ->with($device);

        self::assertTrue($this->manager->unregisterForCustomer($customer, 'device-token'));
    }

    #[Test]
    public function unregisterForCustomerReturnsFalseWhenNoOwnedDeviceExists(): void
    {
        $customer = $this->makeCustomer();

        $this->pushDeviceRepository
            ->expects(self::once())
            ->method('findOwnedByCustomerAndToken')
            ->with($customer, 'missing-token')
            ->willReturn(null);

        $this->pushDeviceRepository
            ->expects(self::never())
            ->method('remove');

        self::assertFalse($this->manager->unregisterForCustomer($customer, 'missing-token'));
    }

    #[Test]
    public function unregisterForUserRemovesOwnedDevice(): void
    {
        $user = $this->makeUser();
        $device = (new PushDevice())
            ->setToken('device-token')
            ->setPlatform('web')
            ->setProvider('webpush')
            ->setUser($user);

        $this->pushDeviceRepository
            ->expects(self::once())
            ->method('findOwnedByUserAndToken')
            ->with($user, 'device-token')
            ->willReturn($device);

        $this->pushDeviceRepository
            ->expects(self::once())
            ->method('remove')
            ->with($device);

        self::assertTrue($this->manager->unregisterForUser($user, 'device-token'));
    }

    #[Test]
    public function unregisterForUserReturnsFalseWhenNoOwnedDeviceExists(): void
    {
        $user = $this->makeUser();

        $this->pushDeviceRepository
            ->expects(self::once())
            ->method('findOwnedByUserAndToken')
            ->with($user, 'missing-token')
            ->willReturn(null);

        $this->pushDeviceRepository
            ->expects(self::never())
            ->method('remove');

        self::assertFalse($this->manager->unregisterForUser($user, 'missing-token'));
    }
}
