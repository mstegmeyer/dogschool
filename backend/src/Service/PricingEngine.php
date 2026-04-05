<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Pricing\ContractPricingQuote;
use App\Dto\Pricing\ContractPricingSnapshot;
use App\Dto\Pricing\HotelBookingPricingQuote;
use App\Dto\Pricing\HotelBookingPricingSnapshot;
use App\Entity\Contract;
use App\Entity\Customer;
use App\Entity\HotelPeakSeason;
use App\Entity\PricingConfig;
use App\Enum\ContractState;
use App\Enum\HotelBookingPricingKind;
use App\Repository\ContractRepository;

final class PricingEngine
{
    public function __construct(
        private readonly PricingConfigProvider $pricingConfigProvider,
        private readonly ContractRepository $contractRepository,
    ) {
    }

    public function previewContract(Customer $customer, int $coursesPerWeek): ContractPricingQuote
    {
        $pricingConfig = $this->pricingConfigProvider->getCurrent();
        $monthlyUnitPrice = self::schoolUnitPriceForCourseCount($pricingConfig, $coursesPerWeek);

        $monthlyPrice = self::formatAmount(self::amountToCents($monthlyUnitPrice) * $coursesPerWeek);
        $requiresRegistrationFee = !$this->contractRepository->customerHasActivatedContract($customer);
        $registrationFee = $requiresRegistrationFee ? $pricingConfig->getSchoolRegistrationFee() : '0.00';
        $firstInvoiceTotal = self::formatAmount(self::amountToCents($monthlyPrice) + self::amountToCents($registrationFee));

        return new ContractPricingQuote(
            $monthlyPrice,
            $registrationFee,
            $firstInvoiceTotal,
            $monthlyUnitPrice,
            $coursesPerWeek,
            $requiresRegistrationFee,
            ContractPricingSnapshot::forQuote(
                $coursesPerWeek,
                $monthlyUnitPrice,
                $monthlyPrice,
                $registrationFee,
                $firstInvoiceTotal,
            ),
        );
    }

    public function previewExistingContract(Contract $contract): ContractPricingQuote
    {
        $customer = $contract->getCustomer() ?? throw new \LogicException('Contract customer is required for pricing.');
        $coursesPerWeek = $contract->getCoursesPerWeek();
        $pricingConfig = $this->pricingConfigProvider->getCurrent();
        $monthlyUnitPrice = self::schoolUnitPriceForCourseCount($pricingConfig, $coursesPerWeek);
        $monthlyPrice = self::formatAmount(self::amountToCents($monthlyUnitPrice) * $coursesPerWeek);

        $requiresRegistrationFee = !$this->contractRepository->customerHasActivatedContract(
            $customer,
            $contract->getState() === ContractState::ACTIVE || $contract->getState() === ContractState::CANCELLED
                ? $contract->getId()
                : null,
        );
        $registrationFee = $requiresRegistrationFee ? $pricingConfig->getSchoolRegistrationFee() : '0.00';
        $firstInvoiceTotal = self::formatAmount(self::amountToCents($monthlyPrice) + self::amountToCents($registrationFee));

        return new ContractPricingQuote(
            $monthlyPrice,
            $registrationFee,
            $firstInvoiceTotal,
            $monthlyUnitPrice,
            $coursesPerWeek,
            $requiresRegistrationFee,
            ContractPricingSnapshot::forQuote(
                $coursesPerWeek,
                $monthlyUnitPrice,
                $monthlyPrice,
                $registrationFee,
                $firstInvoiceTotal,
            ),
        );
    }

    public function previewHotelBooking(
        \DateTimeImmutable $startAt,
        \DateTimeImmutable $endAt,
        bool $includesTravelProtection,
        bool $includesSingleRoom = false,
    ): HotelBookingPricingQuote {
        $pricingConfig = $this->pricingConfigProvider->getCurrent();
        $pricingKind = $startAt->format('Y-m-d') === $endAt->format('Y-m-d')
            ? HotelBookingPricingKind::DAYCARE
            : HotelBookingPricingKind::HOTEL;
        $billableDays = self::billableCalendarDays($startAt, $endAt);

        $baseDailyPrice = match ($pricingKind) {
            HotelBookingPricingKind::DAYCARE => $this->isPeakSeasonDate($pricingConfig, $startAt)
                ? $pricingConfig->getDaycarePeakSeasonDailyPrice()
                : $pricingConfig->getDaycareOffSeasonDailyPrice(),
            HotelBookingPricingKind::HOTEL => $pricingConfig->getHotelDailyPrice(),
        };
        $baseAmount = self::formatAmount(self::amountToCents($baseDailyPrice) * $billableDays);
        $serviceFee = $pricingConfig->getHotelServiceFee();
        $travelProtectionPrice = $includesTravelProtection
            ? self::formatAmount(
                self::amountToCents($pricingConfig->getHotelTravelProtectionBaseFee())
                + max(0, $billableDays - 7) * self::amountToCents($pricingConfig->getHotelTravelProtectionAdditionalDailyFee())
            )
            : '0.00';
        $singleRoomDailyPrice = match ($pricingKind) {
            HotelBookingPricingKind::DAYCARE => $pricingConfig->getHotelSingleRoomDaycareDailyPrice(),
            HotelBookingPricingKind::HOTEL => $pricingConfig->getHotelSingleRoomHotelDailyPrice(),
        };
        $singleRoomPrice = $includesSingleRoom
            ? self::formatAmount(self::amountToCents($singleRoomDailyPrice) * $billableDays)
            : '0.00';
        $quotedTotalPrice = self::formatAmount(
            self::amountToCents($baseAmount)
            + self::amountToCents($serviceFee)
            + self::amountToCents($travelProtectionPrice)
            + self::amountToCents($singleRoomPrice)
        );
        $baseLabel = match ($pricingKind) {
            HotelBookingPricingKind::DAYCARE => sprintf(
                'HUTA %s',
                $this->isPeakSeasonDate($pricingConfig, $startAt) ? 'Hauptsaison' : 'Nebensaison',
            ),
            HotelBookingPricingKind::HOTEL => 'Hundehotel',
        };

        return new HotelBookingPricingQuote(
            $pricingKind,
            $billableDays,
            $baseDailyPrice,
            $serviceFee,
            $travelProtectionPrice,
            $singleRoomPrice,
            $quotedTotalPrice,
            $includesTravelProtection,
            $includesSingleRoom,
            HotelBookingPricingSnapshot::forQuote(
                $pricingKind,
                $billableDays,
                $baseDailyPrice,
                $serviceFee,
                $travelProtectionPrice,
                $singleRoomPrice,
                $quotedTotalPrice,
                $baseLabel,
                $includesTravelProtection,
                $includesSingleRoom,
            ),
        );
    }

    public static function schoolUnitPriceForCourseCount(PricingConfig $pricingConfig, int $coursesPerWeek): string
    {
        return match (true) {
            $coursesPerWeek <= 1 => $pricingConfig->getSchoolOneCoursePrice(),
            $coursesPerWeek === 2 => $pricingConfig->getSchoolTwoCoursesUnitPrice(),
            $coursesPerWeek === 3 => $pricingConfig->getSchoolThreeCoursesUnitPrice(),
            $coursesPerWeek === 4 => $pricingConfig->getSchoolFourCoursesUnitPrice(),
            default => $pricingConfig->getSchoolAdditionalCoursesUnitPrice(),
        };
    }

    public static function amountToCents(string $amount): int
    {
        $normalized = trim(str_replace(',', '.', $amount));
        if ($normalized === '') {
            return 0;
        }

        $negative = str_starts_with($normalized, '-');
        if ($negative) {
            $normalized = substr($normalized, 1);
        }

        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '0');
        $wholePart = (int) preg_replace('/\D/', '', $whole);
        $fractionPart = (int) str_pad(substr(preg_replace('/\D/', '', $fraction) ?: '0', 0, 2), 2, '0');
        $cents = ($wholePart * 100) + $fractionPart;

        return $negative ? -$cents : $cents;
    }

    public static function formatAmount(int $cents): string
    {
        $negative = $cents < 0 ? '-' : '';
        $absolute = abs($cents);

        return sprintf('%s%d.%02d', $negative, intdiv($absolute, 100), $absolute % 100);
    }

    public static function billableCalendarDays(\DateTimeImmutable $startAt, \DateTimeImmutable $endAt): int
    {
        $startDate = $startAt->setTime(0, 0);
        $endDate = $endAt->setTime(0, 0);

        return ((int) $startDate->diff($endDate)->days) + 1;
    }

    private function isPeakSeasonDate(PricingConfig $pricingConfig, \DateTimeImmutable $date): bool
    {
        $comparisonDate = $date->setTime(0, 0);

        /** @var HotelPeakSeason $season */
        foreach ($pricingConfig->getHotelPeakSeasons() as $season) {
            $startDate = $season->getStartDate();
            $endDate = $season->getEndDate();
            if ($startDate === null || $endDate === null) {
                continue;
            }

            if ($comparisonDate >= $startDate->setTime(0, 0) && $comparisonDate <= $endDate->setTime(0, 0)) {
                return true;
            }
        }

        return false;
    }
}
