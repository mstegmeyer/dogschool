<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Contract;
use App\Entity\CourseDate;
use App\Entity\CreditTransaction;
use App\Entity\Customer;
use App\Enum\CreditTransactionType;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CreditTransactionTest extends TestCase
{
    private mixed $serverFixedNow;
    private mixed $envFixedNow;
    private bool $hadServerFixedNow;
    private bool $hadEnvFixedNow;

    protected function setUp(): void
    {
        $this->hadServerFixedNow = array_key_exists('APP_FIXED_NOW', $_SERVER);
        $this->serverFixedNow = $_SERVER['APP_FIXED_NOW'] ?? null;
        $this->hadEnvFixedNow = array_key_exists('APP_FIXED_NOW', $_ENV);
        $this->envFixedNow = $_ENV['APP_FIXED_NOW'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->hadServerFixedNow) {
            $_SERVER['APP_FIXED_NOW'] = $this->serverFixedNow;
        } else {
            unset($_SERVER['APP_FIXED_NOW']);
        }

        if ($this->hadEnvFixedNow) {
            $_ENV['APP_FIXED_NOW'] = $this->envFixedNow;
        } else {
            unset($_ENV['APP_FIXED_NOW']);
        }
    }

    #[Test]
    public function constructorGeneratesIdAndUsesAppClockForCreatedAt(): void
    {
        $_SERVER['APP_FIXED_NOW'] = '2026-04-04T12:34:56+00:00';
        unset($_ENV['APP_FIXED_NOW']);

        $transaction = new CreditTransaction();

        self::assertSame(36, strlen($transaction->getId()));
        self::assertSame('2026-04-04T12:34:56+00:00', $transaction->getCreatedAt()->format(\DateTimeInterface::ATOM));
    }

    #[Test]
    public function settersExposeAllAssignedValues(): void
    {
        $customer = (new Customer())
            ->setName('Customer')
            ->setEmail('customer@example.com')
            ->setPassword('hashed');

        $courseDate = new CourseDate();
        $contract = new Contract();

        $transaction = (new CreditTransaction())
            ->setCustomer($customer)
            ->setAmount(-2)
            ->setType(CreditTransactionType::BOOKING)
            ->setDescription('Booked agility')
            ->setCourseDate($courseDate)
            ->setContract($contract)
            ->setWeekRef('2026-W14');

        self::assertSame($customer, $transaction->getCustomer());
        self::assertSame(-2, $transaction->getAmount());
        self::assertSame(CreditTransactionType::BOOKING, $transaction->getType());
        self::assertSame('Booked agility', $transaction->getDescription());
        self::assertSame($courseDate, $transaction->getCourseDate());
        self::assertSame($contract, $transaction->getContract());
        self::assertSame('2026-W14', $transaction->getWeekRef());
    }
}
