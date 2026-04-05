<?php

declare(strict_types=1);

namespace App\Dto\Pricing;

use App\Service\PricingEngine;

final class ContractPricingSnapshot
{
    /**
     * @param list<PricingLineItem> $lineItems
     */
    public function __construct(
        public readonly int $coursesPerWeek,
        public readonly string $monthlyUnitPrice,
        public readonly string $monthlyPrice,
        public readonly string $registrationFee,
        public readonly string $firstInvoiceTotal,
        public readonly string $quotedMonthlyPrice,
        public readonly string $quotedRegistrationFee,
        public readonly array $lineItems,
    ) {
    }

    public static function forQuote(
        int $coursesPerWeek,
        string $monthlyUnitPrice,
        string $monthlyPrice,
        string $registrationFee,
        string $firstInvoiceTotal,
    ): self {
        return new self(
            $coursesPerWeek,
            $monthlyUnitPrice,
            $monthlyPrice,
            $registrationFee,
            $firstInvoiceTotal,
            $monthlyPrice,
            $registrationFee,
            [
                new PricingLineItem(
                    'school_contract_monthly',
                    sprintf('%dx Training pro Woche', $coursesPerWeek),
                    $coursesPerWeek,
                    $monthlyUnitPrice,
                    $monthlyPrice,
                    'MONTH',
                ),
                new PricingLineItem(
                    'school_registration_fee',
                    'Anmeldegebühr',
                    1,
                    $registrationFee,
                    $registrationFee,
                    'ONCE',
                ),
            ],
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $monthlyPrice = self::readString($data, 'monthlyPrice', '0.00');
        $registrationFee = self::readString($data, 'registrationFee', '0.00');

        return new self(
            self::readInt($data, 'coursesPerWeek', 0),
            self::readString($data, 'monthlyUnitPrice', '0.00'),
            $monthlyPrice,
            $registrationFee,
            self::readString(
                $data,
                'firstInvoiceTotal',
                PricingEngine::formatAmount(
                    PricingEngine::amountToCents($monthlyPrice) + PricingEngine::amountToCents($registrationFee),
                ),
            ),
            self::readString($data, 'quotedMonthlyPrice', $monthlyPrice),
            self::readString($data, 'quotedRegistrationFee', $registrationFee),
            PricingLineItem::listFromArray($data['lineItems'] ?? $data['items'] ?? []),
        );
    }

    public function finalize(string $finalMonthlyPrice, string $finalRegistrationFee): self
    {
        $lineItems = $this->removeLineItem('manual_adjustment');
        $adjustmentCents = PricingEngine::amountToCents($finalMonthlyPrice) - PricingEngine::amountToCents($this->quotedMonthlyPrice);

        if ($adjustmentCents !== 0) {
            $lineItems[] = PricingLineItem::manualAdjustment(PricingEngine::formatAmount($adjustmentCents));
        }

        $lineItems = $this->synchronizeRegistrationFeeLineItem($lineItems, $finalRegistrationFee);

        return new self(
            $this->coursesPerWeek,
            $this->monthlyUnitPrice,
            $finalMonthlyPrice,
            $finalRegistrationFee,
            PricingEngine::formatAmount(
                PricingEngine::amountToCents($finalMonthlyPrice) + PricingEngine::amountToCents($finalRegistrationFee),
            ),
            $this->quotedMonthlyPrice,
            $this->quotedRegistrationFee,
            $lineItems,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => 'contract',
            'coursesPerWeek' => $this->coursesPerWeek,
            'monthlyUnitPrice' => $this->monthlyUnitPrice,
            'monthlyPrice' => $this->monthlyPrice,
            'registrationFee' => $this->registrationFee,
            'firstInvoiceTotal' => $this->firstInvoiceTotal,
            'quotedMonthlyPrice' => $this->quotedMonthlyPrice,
            'quotedRegistrationFee' => $this->quotedRegistrationFee,
            'lineItems' => array_map(
                static fn (PricingLineItem $item): array => $item->toArray(),
                $this->lineItems,
            ),
        ];
    }

    /**
     * @return list<PricingLineItem>
     */
    private function removeLineItem(string $key): array
    {
        return array_values(array_filter(
            $this->lineItems,
            static fn (PricingLineItem $item): bool => $item->key !== $key,
        ));
    }

    /**
     * @param list<PricingLineItem> $lineItems
     *
     * @return list<PricingLineItem>
     */
    private function synchronizeRegistrationFeeLineItem(array $lineItems, string $registrationFee): array
    {
        $updatedLineItems = [];
        $updated = false;

        foreach ($lineItems as $item) {
            if ($item->key === 'school_registration_fee') {
                $item = new PricingLineItem(
                    $item->key,
                    $item->label,
                    1,
                    $registrationFee,
                    $registrationFee,
                    'ONCE',
                );
                $updated = true;
            }

            $updatedLineItems[] = $item;
        }

        if (!$updated) {
            $updatedLineItems[] = new PricingLineItem(
                'school_registration_fee',
                'Anmeldegebühr',
                1,
                $registrationFee,
                $registrationFee,
                'ONCE',
            );
        }

        return $updatedLineItems;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function readString(array $data, string $key, string $fallback = ''): string
    {
        $value = $data[$key] ?? null;

        return is_scalar($value) ? (string) $value : $fallback;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function readInt(array $data, string $key, int $fallback): int
    {
        $value = $data[$key] ?? null;

        return is_numeric($value) ? (int) $value : $fallback;
    }
}
