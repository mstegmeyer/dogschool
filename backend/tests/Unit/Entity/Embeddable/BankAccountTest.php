<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity\Embeddable;

use App\Entity\Embeddable\BankAccount;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BankAccountTest extends TestCase
{
    #[Test]
    public function settersExposeAssignedValues(): void
    {
        $bankAccount = (new BankAccount())
            ->setIban('DE89370400440532013000')
            ->setBic('COBADEFFXXX')
            ->setAccountHolder('Max Mustermann');

        self::assertSame('DE89370400440532013000', $bankAccount->getIban());
        self::assertSame('COBADEFFXXX', $bankAccount->getBic());
        self::assertSame('Max Mustermann', $bankAccount->getAccountHolder());
    }

    #[Test]
    public function fieldsCanBeCleared(): void
    {
        $bankAccount = (new BankAccount())
            ->setIban('DE89370400440532013000')
            ->setBic('COBADEFFXXX')
            ->setAccountHolder('Max Mustermann');

        $bankAccount
            ->setIban(null)
            ->setBic(null)
            ->setAccountHolder(null);

        self::assertNull($bankAccount->getIban());
        self::assertNull($bankAccount->getBic());
        self::assertNull($bankAccount->getAccountHolder());
    }
}
