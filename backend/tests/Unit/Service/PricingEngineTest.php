<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Contract;
use App\Entity\Customer;
use App\Entity\HotelPeakSeason;
use App\Entity\PricingConfig;
use App\Enum\ContractState;
use App\Repository\ContractRepository;
use App\Repository\PricingConfigRepository;
use App\Service\PricingConfigProvider;
use App\Service\PricingEngine;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PricingEngineTest extends TestCase
{
    private PricingConfigProvider $pricingConfigProvider;
    private ContractRepository&MockObject $contractRepository;
    private PricingConfigRepository&MockObject $pricingConfigRepository;
    private PricingEngine $pricingEngine;
    private PricingConfig $pricingConfig;

    protected function setUp(): void
    {
        $this->contractRepository = $this->createMock(ContractRepository::class);
        $this->pricingConfigRepository = $this->createMock(PricingConfigRepository::class);
        $this->pricingConfig = new PricingConfig();

        foreach (PricingConfigProvider::defaultPeakSeasonRanges() as [$startDate, $endDate]) {
            $season = new HotelPeakSeason();
            $season->setStartDate(new \DateTimeImmutable($startDate));
            $season->setEndDate(new \DateTimeImmutable($endDate));
            $this->pricingConfig->addHotelPeakSeason($season);
        }

        $this->pricingConfigRepository
            ->method('findCurrent')
            ->willReturn($this->pricingConfig);

        $this->pricingConfigProvider = new PricingConfigProvider($this->pricingConfigRepository);

        $this->pricingEngine = new PricingEngine(
            $this->pricingConfigProvider,
            $this->contractRepository,
        );
    }

    #[Test]
    public function previewContractUsesConfiguredLadderPrices(): void
    {
        $customer = new Customer();
        $customer->setName('Test');
        $customer->setEmail('test@example.com');
        $customer->setPassword('hashed');

        $this->contractRepository
            ->method('customerHasActivatedContract')
            ->willReturn(false);

        self::assertSame('89.00', $this->pricingEngine->previewContract($customer, 1)['monthlyPrice']);
        self::assertSame('160.00', $this->pricingEngine->previewContract($customer, 2)['monthlyPrice']);
        self::assertSame('228.00', $this->pricingEngine->previewContract($customer, 3)['monthlyPrice']);
        self::assertSame('284.00', $this->pricingEngine->previewContract($customer, 4)['monthlyPrice']);
        self::assertSame('335.00', $this->pricingEngine->previewContract($customer, 5)['monthlyPrice']);
    }

    #[Test]
    public function previewExistingContractSkipsRegistrationFeeWhenCustomerAlreadyHasActivatedContract(): void
    {
        $customer = new Customer();
        $customer->setName('Active');
        $customer->setEmail('active@example.com');
        $customer->setPassword('hashed');

        $contract = new Contract();
        $contract->setCustomer($customer);
        $contract->setState(ContractState::REQUESTED);
        $contract->setCoursesPerWeek(2);

        $this->contractRepository
            ->expects(self::once())
            ->method('customerHasActivatedContract')
            ->with($customer, null)
            ->willReturn(true);

        $quote = $this->pricingEngine->previewExistingContract($contract);

        self::assertSame('160.00', $quote['monthlyPrice']);
        self::assertSame('0.00', $quote['registrationFee']);
        self::assertSame('160.00', $quote['firstInvoiceTotal']);
    }

    #[Test]
    public function previewHotelBookingUsesPeakSeasonDaycarePricingWithTravelProtection(): void
    {
        $quote = $this->pricingEngine->previewHotelBooking(
            new \DateTimeImmutable('2026-04-05T08:30:00+02:00'),
            new \DateTimeImmutable('2026-04-05T18:00:00+02:00'),
            true,
        );

        self::assertSame('DAYCARE', $quote['pricingKind']->value);
        self::assertSame(1, $quote['billableDays']);
        self::assertSame('46.00', $quote['baseDailyPrice']);
        self::assertSame('7.50', $quote['serviceFee']);
        self::assertSame('49.00', $quote['travelProtectionPrice']);
        self::assertSame('102.50', $quote['quotedTotalPrice']);
    }

    #[Test]
    public function previewHotelBookingUsesStartedCalendarDaysForOvernightHotelStays(): void
    {
        $quote = $this->pricingEngine->previewHotelBooking(
            new \DateTimeImmutable('2026-04-16T08:00:00+02:00'),
            new \DateTimeImmutable('2026-04-17T10:00:00+02:00'),
            false,
        );

        self::assertSame('HOTEL', $quote['pricingKind']->value);
        self::assertSame(2, $quote['billableDays']);
        self::assertSame('58.00', $quote['baseDailyPrice']);
        self::assertSame('7.50', $quote['serviceFee']);
        self::assertSame('0.00', $quote['travelProtectionPrice']);
        self::assertSame('123.50', $quote['quotedTotalPrice']);
        self::assertNotContains(
            'hotel_travel_protection',
            array_map(
                static fn (array $item): string => (string) ($item['key'] ?? ''),
                $quote['snapshot']['lineItems'] ?? [],
            ),
        );
    }
}
