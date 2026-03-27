<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\PushDeviceUpsertDto;
use App\Entity\Customer;
use App\Entity\PushDevice;
use App\Entity\User;
use App\Repository\PushDeviceRepository;

final class PushDeviceManager
{
    public function __construct(
        private readonly PushDeviceRepository $pushDeviceRepository,
    ) {
    }

    public function registerForCustomer(Customer $customer, PushDeviceUpsertDto $dto): PushDevice
    {
        $pushDevice = $this->pushDeviceRepository->findOneByToken($dto->token) ?? new PushDevice();
        $pushDevice
            ->setToken($dto->token)
            ->setPlatform($dto->platform)
            ->setProvider($dto->provider)
            ->setDeviceName($dto->deviceName)
            ->setCustomer($customer)
            ->touch();

        $this->pushDeviceRepository->save($pushDevice);

        return $pushDevice;
    }

    public function registerForUser(User $user, PushDeviceUpsertDto $dto): PushDevice
    {
        $pushDevice = $this->pushDeviceRepository->findOneByToken($dto->token) ?? new PushDevice();
        $pushDevice
            ->setToken($dto->token)
            ->setPlatform($dto->platform)
            ->setProvider($dto->provider)
            ->setDeviceName($dto->deviceName)
            ->setUser($user)
            ->touch();

        $this->pushDeviceRepository->save($pushDevice);

        return $pushDevice;
    }

    public function unregisterForCustomer(Customer $customer, string $token): bool
    {
        $pushDevice = $this->pushDeviceRepository->findOwnedByCustomerAndToken($customer, $token);
        if ($pushDevice === null) {
            return false;
        }

        $this->pushDeviceRepository->remove($pushDevice);

        return true;
    }

    public function unregisterForUser(User $user, string $token): bool
    {
        $pushDevice = $this->pushDeviceRepository->findOwnedByUserAndToken($user, $token);
        if ($pushDevice === null) {
            return false;
        }

        $this->pushDeviceRepository->remove($pushDevice);

        return true;
    }
}
