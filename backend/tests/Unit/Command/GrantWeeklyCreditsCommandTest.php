<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\GrantWeeklyCreditsCommand;
use App\Entity\Contract;
use App\Entity\CreditTransaction;
use App\Entity\Customer;
use App\Repository\BookingRepository;
use App\Repository\ContractRepository;
use App\Repository\CreditTransactionRepository;
use App\Service\CreditService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class GrantWeeklyCreditsCommandTest extends TestCase
{
    #[Test]
    public function itGrantsCreditsOnlyForEligibleContracts(): void
    {
        $commandContractRepository = $this->createMock(ContractRepository::class);
        $creditTransactionRepository = $this->createMock(CreditTransactionRepository::class);

        $eligible = $this->makeContract('Alice', 2, '-1 day');
        $alreadyGranted = $this->makeContract('Bob', 3, '-1 day');
        $zeroCourses = $this->makeContract('Zero', 0, '-1 day');
        $futureContract = $this->makeContract('Future', 1, '+1 day');
        $endedContract = $this->makeContract('Ended', 1, '-5 days', '-1 day');

        $commandContractRepository
            ->expects(self::once())
            ->method('findAllCreditEligiblePerpetual')
            ->willReturn([$zeroCourses, $futureContract, $endedContract, $eligible, $alreadyGranted]);

        $creditTransactionRepository
            ->expects(self::exactly(2))
            ->method('weeklyGrantExists')
            ->willReturnCallback(static function (Contract $contract, string $weekRef) use ($eligible, $alreadyGranted): bool {
                self::assertSame('2026-W12', $weekRef);

                return match ($contract->getId()) {
                    $eligible->getId() => false,
                    $alreadyGranted->getId() => true,
                    default => throw new \LogicException('Unexpected contract passed to weeklyGrantExists().'),
                };
            });

        $creditTransactionRepository
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(function (CreditTransaction $transaction) use ($eligible): bool {
                self::assertSame($eligible, $transaction->getContract());
                self::assertSame($eligible->getCustomer(), $transaction->getCustomer());
                self::assertSame(2, $transaction->getAmount());
                self::assertSame('2026-W12', $transaction->getWeekRef());

                return true;
            }));

        $tester = new CommandTester(new GrantWeeklyCreditsCommand(
            $commandContractRepository,
            $this->createCreditService($creditTransactionRepository),
        ));

        $exitCode = $tester->execute(['--week' => '2026-W12']);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Granting weekly credits for week 2026-W12 ...', $tester->getDisplay());
        self::assertStringContainsString('  +2 credits → Alice', $tester->getDisplay());
        self::assertStringContainsString('Granted credits for 1 contract(s), skipped 1 (already granted).', $tester->getDisplay());
    }

    #[Test]
    public function itUsesTheCurrentWeekWhenNoOptionIsProvided(): void
    {
        $commandContractRepository = $this->createMock(ContractRepository::class);
        $commandContractRepository
            ->expects(self::once())
            ->method('findAllCreditEligiblePerpetual')
            ->willReturn([]);

        $creditTransactionRepository = $this->createMock(CreditTransactionRepository::class);
        $creditTransactionRepository->expects(self::never())->method('weeklyGrantExists');
        $creditTransactionRepository->expects(self::never())->method('save');

        $tester = new CommandTester(new GrantWeeklyCreditsCommand(
            $commandContractRepository,
            $this->createCreditService($creditTransactionRepository),
        ));

        $currentWeek = (new \DateTimeImmutable())->format('o-\WW');
        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString(sprintf('Granting weekly credits for week %s ...', $currentWeek), $tester->getDisplay());
        self::assertStringContainsString('Granted credits for 0 contract(s), skipped 0 (already granted).', $tester->getDisplay());
    }

    private function createCreditService(CreditTransactionRepository&MockObject $creditTransactionRepository): CreditService
    {
        return new CreditService(
            $creditTransactionRepository,
            $this->createMock(BookingRepository::class),
            $this->createMock(ContractRepository::class),
            $this->createMock(EntityManagerInterface::class),
        );
    }

    private function makeContract(
        string $customerName,
        int $coursesPerWeek,
        string $startDate,
        ?string $endDate = null,
    ): Contract {
        $customer = new Customer();
        $customer->setName($customerName);
        $customer->setEmail(strtolower($customerName).'@example.com');
        $customer->setPassword('hashed');

        $contract = new Contract();
        $contract->setCustomer($customer);
        $contract->setCoursesPerWeek($coursesPerWeek);
        $contract->setStartDate(new \DateTimeImmutable($startDate));
        $contract->setPrice('100.00');

        if ($endDate !== null) {
            $contract->setEndDate(new \DateTimeImmutable($endDate));
        }

        return $contract;
    }
}
