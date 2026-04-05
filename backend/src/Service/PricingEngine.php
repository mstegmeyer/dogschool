<?php

declare(strict_types=1);

namespace App\Service;

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

    /**
     * @return array{
     *   monthlyPrice: string,
     *   registrationFee: string,
     *   firstInvoiceTotal: string,
     *   monthlyUnitPrice: string,
     *   coursesPerWeek: int,
     *   requiresRegistrationFee: bool,
     *   snapshot: array<string, mixed>
     * }
     */
    public function previewContract(Customer $customer, int $coursesPerWeek): array
    {
        $pricingConfig = $this->pricingConfigProvider->getCurrent();
        $monthlyUnitPrice = self::schoolUnitPriceForCourseCount($pricingConfig, $coursesPerWeek);

        $monthlyPrice = self::formatAmount(self::amountToCents($monthlyUnitPrice) * $coursesPerWeek);
        $requiresRegistrationFee = !$this->contractRepository->customerHasActivatedContract($customer);
        $registrationFee = $requiresRegistrationFee ? $pricingConfig->getSchoolRegistrationFee() : '0.00';
        $firstInvoiceTotal = self::formatAmount(self::amountToCents($monthlyPrice) + self::amountToCents($registrationFee));

        return [
            'monthlyPrice' => $monthlyPrice,
            'registrationFee' => $registrationFee,
            'firstInvoiceTotal' => $firstInvoiceTotal,
            'monthlyUnitPrice' => $monthlyUnitPrice,
            'coursesPerWeek' => $coursesPerWeek,
            'requiresRegistrationFee' => $requiresRegistrationFee,
            'snapshot' => [
                'type' => 'contract',
                'coursesPerWeek' => $coursesPerWeek,
                'monthlyUnitPrice' => $monthlyUnitPrice,
                'monthlyPrice' => $monthlyPrice,
                'registrationFee' => $registrationFee,
                'firstInvoiceTotal' => $firstInvoiceTotal,
                'lineItems' => [
                    [
                        'key' => 'school_contract_monthly',
                        'label' => sprintf('%dx Training pro Woche', $coursesPerWeek),
                        'quantity' => $coursesPerWeek,
                        'unitPrice' => $monthlyUnitPrice,
                        'amount' => $monthlyPrice,
                        'billingPeriod' => 'MONTH',
                    ],
                    [
                        'key' => 'school_registration_fee',
                        'label' => 'Anmeldegebühr',
                        'quantity' => 1,
                        'unitPrice' => $registrationFee,
                        'amount' => $registrationFee,
                        'billingPeriod' => 'ONCE',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{
     *   monthlyPrice: string,
     *   registrationFee: string,
     *   firstInvoiceTotal: string,
     *   monthlyUnitPrice: string,
     *   coursesPerWeek: int,
     *   requiresRegistrationFee: bool,
     *   snapshot: array<string, mixed>
     * }
     */
    public function previewExistingContract(Contract $contract): array
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

        return [
            'monthlyPrice' => $monthlyPrice,
            'registrationFee' => $registrationFee,
            'firstInvoiceTotal' => $firstInvoiceTotal,
            'monthlyUnitPrice' => $monthlyUnitPrice,
            'coursesPerWeek' => $coursesPerWeek,
            'requiresRegistrationFee' => $requiresRegistrationFee,
            'snapshot' => [
                'type' => 'contract',
                'coursesPerWeek' => $coursesPerWeek,
                'monthlyUnitPrice' => $monthlyUnitPrice,
                'monthlyPrice' => $monthlyPrice,
                'registrationFee' => $registrationFee,
                'firstInvoiceTotal' => $firstInvoiceTotal,
                'lineItems' => [
                    [
                        'key' => 'school_contract_monthly',
                        'label' => sprintf('%dx Training pro Woche', $coursesPerWeek),
                        'quantity' => $coursesPerWeek,
                        'unitPrice' => $monthlyUnitPrice,
                        'amount' => $monthlyPrice,
                        'billingPeriod' => 'MONTH',
                    ],
                    [
                        'key' => 'school_registration_fee',
                        'label' => 'Anmeldegebühr',
                        'quantity' => 1,
                        'unitPrice' => $registrationFee,
                        'amount' => $registrationFee,
                        'billingPeriod' => 'ONCE',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{
     *   pricingKind: HotelBookingPricingKind,
     *   billableDays: int,
     *   baseDailyPrice: string,
     *   serviceFee: string,
     *   travelProtectionPrice: string,
     *   quotedTotalPrice: string,
     *   includesTravelProtection: bool,
     *   snapshot: array<string, mixed>
     * }
     */
    public function previewHotelBooking(
        \DateTimeImmutable $startAt,
        \DateTimeImmutable $endAt,
        bool $includesTravelProtection,
    ): array {
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
        $quotedTotalPrice = self::formatAmount(
            self::amountToCents($baseAmount)
            + self::amountToCents($serviceFee)
            + self::amountToCents($travelProtectionPrice)
        );
        $baseLabel = match ($pricingKind) {
            HotelBookingPricingKind::DAYCARE => sprintf(
                'HUTA %s',
                $this->isPeakSeasonDate($pricingConfig, $startAt) ? 'Hauptsaison' : 'Nebensaison',
            ),
            HotelBookingPricingKind::HOTEL => 'Hundehotel',
        };

        return [
            'pricingKind' => $pricingKind,
            'billableDays' => $billableDays,
            'baseDailyPrice' => $baseDailyPrice,
            'serviceFee' => $serviceFee,
            'travelProtectionPrice' => $travelProtectionPrice,
            'quotedTotalPrice' => $quotedTotalPrice,
            'includesTravelProtection' => $includesTravelProtection,
            'snapshot' => [
                'type' => 'hotelBooking',
                'pricingKind' => $pricingKind->value,
                'billableDays' => $billableDays,
                'baseDailyPrice' => $baseDailyPrice,
                'serviceFee' => $serviceFee,
                'travelProtectionPrice' => $travelProtectionPrice,
                'quotedTotalPrice' => $quotedTotalPrice,
                'lineItems' => [
                    [
                        'key' => 'hotel_base',
                        'label' => $baseLabel,
                        'quantity' => $billableDays,
                        'unitPrice' => $baseDailyPrice,
                        'amount' => $baseAmount,
                        'billingPeriod' => 'DAY',
                    ],
                    [
                        'key' => 'hotel_service_fee',
                        'label' => 'Servicepauschale',
                        'quantity' => 1,
                        'unitPrice' => $serviceFee,
                        'amount' => $serviceFee,
                        'billingPeriod' => 'ONCE',
                    ],
                    [
                        'key' => 'hotel_travel_protection',
                        'label' => 'Reiseschutz',
                        'quantity' => $includesTravelProtection ? 1 : 0,
                        'unitPrice' => $travelProtectionPrice,
                        'amount' => $travelProtectionPrice,
                        'billingPeriod' => 'ONCE',
                    ],
                ],
            ],
        ];
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

    /**
     * @param array<string, mixed> $snapshot
     *
     * @return array<string, mixed>
     */
    public static function normalizeSnapshot(array $snapshot, string $type): array
    {
        $normalized = $snapshot;
        $normalized['type'] = $type;

        $rawLineItems = $snapshot['lineItems'] ?? $snapshot['items'] ?? [];
        $lineItems = [];
        if (is_array($rawLineItems)) {
            foreach ($rawLineItems as $item) {
                if (is_array($item)) {
                    $lineItems[] = $item;
                }
            }
        }
        $normalized['lineItems'] = $lineItems;

        return $normalized;
    }

    /**
     * @param array<string, mixed> $snapshot
     *
     * @return array<string, mixed>
     */
    public static function finalizeContractSnapshot(array $snapshot, string $finalMonthlyPrice, string $registrationFee): array
    {
        $normalized = self::normalizeSnapshot($snapshot, 'contract');
        $quotedMonthlyPrice = is_string($normalized['quotedMonthlyPrice'] ?? null)
            ? $normalized['quotedMonthlyPrice']
            : (is_string($normalized['monthlyPrice'] ?? null) ? $normalized['monthlyPrice'] : $finalMonthlyPrice);
        $adjustmentCents = self::amountToCents($finalMonthlyPrice) - self::amountToCents($quotedMonthlyPrice);

        if ($adjustmentCents !== 0) {
            $normalized = self::appendManualAdjustmentLineItem($normalized, $adjustmentCents);
        }

        $normalized['quotedMonthlyPrice'] = $quotedMonthlyPrice;
        $normalized['monthlyPrice'] = $finalMonthlyPrice;
        $normalized['registrationFee'] = $registrationFee;
        $normalized['firstInvoiceTotal'] = self::formatAmount(
            self::amountToCents($finalMonthlyPrice) + self::amountToCents($registrationFee)
        );

        return $normalized;
    }

    /**
     * @param array<string, mixed> $snapshot
     *
     * @return array<string, mixed>
     */
    public static function finalizeHotelBookingSnapshot(array $snapshot, string $finalTotalPrice): array
    {
        $normalized = self::normalizeSnapshot($snapshot, 'hotelBooking');
        $quotedTotalPrice = is_string($normalized['quotedTotalPrice'] ?? null)
            ? $normalized['quotedTotalPrice']
            : (is_string($normalized['totalPrice'] ?? null) ? $normalized['totalPrice'] : $finalTotalPrice);
        $adjustmentCents = self::amountToCents($finalTotalPrice) - self::amountToCents($quotedTotalPrice);

        if ($adjustmentCents !== 0) {
            $normalized = self::appendManualAdjustmentLineItem($normalized, $adjustmentCents);
        }

        $normalized['quotedTotalPrice'] = $quotedTotalPrice;
        $normalized['totalPrice'] = $finalTotalPrice;
        $normalized['finalTotalPrice'] = $finalTotalPrice;

        return $normalized;
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

    /**
     * @param array<string, mixed> $snapshot
     *
     * @return array<string, mixed>
     */
    private static function appendManualAdjustmentLineItem(array $snapshot, int $adjustmentCents): array
    {
        $lineItems = isset($snapshot['lineItems']) && is_array($snapshot['lineItems'])
            ? $snapshot['lineItems']
            : [];
        $lineItems[] = [
            'key' => 'manual_adjustment',
            'label' => 'Manuelle Preisanpassung',
            'quantity' => 1,
            'unitPrice' => self::formatAmount($adjustmentCents),
            'amount' => self::formatAmount($adjustmentCents),
            'billingPeriod' => 'ONCE',
        ];
        $snapshot['lineItems'] = $lineItems;

        return $snapshot;
    }
}
