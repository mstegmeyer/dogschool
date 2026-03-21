<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Contract;
use App\Enum\ContractState;
use App\Enum\ContractType;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ContractTest extends TestCase
{
    #[Test]
    public function constructorSetsDefaults(): void
    {
        $contract = new Contract();

        self::assertSame(36, strlen($contract->getId()));
        self::assertSame(36, strlen($contract->getContractGroupId()));
        self::assertSame(1, $contract->getVersion());
        self::assertSame(ContractType::PERPETUAL, $contract->getType());
        self::assertSame(ContractState::REQUESTED, $contract->getState());
    }

    #[Test]
    public function stateTransitionsWork(): void
    {
        $contract = new Contract();

        self::assertSame(ContractState::REQUESTED, $contract->getState());

        $contract->setState(ContractState::ACTIVE);
        self::assertSame(ContractState::ACTIVE, $contract->getState());

        $contract->setState(ContractState::CANCELLED);
        self::assertSame(ContractState::CANCELLED, $contract->getState());
    }

    #[Test]
    public function versionCanBeIncremented(): void
    {
        $contract = new Contract();
        self::assertSame(1, $contract->getVersion());

        $contract->setVersion(2);
        self::assertSame(2, $contract->getVersion());
    }

    #[Test]
    public function contractGroupIdCanBeSharedAcrossVersions(): void
    {
        $v1 = new Contract();
        $groupId = $v1->getContractGroupId();

        $v2 = new Contract();
        $v2->setContractGroupId($groupId);
        $v2->setVersion(2);

        self::assertSame($groupId, $v2->getContractGroupId());
        self::assertSame(2, $v2->getVersion());
    }
}
