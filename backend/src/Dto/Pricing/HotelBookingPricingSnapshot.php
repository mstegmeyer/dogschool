<?php

declare(strict_types=1);

namespace App\Dto\Pricing;

use App\Enum\HotelBookingPricingKind;
use App\Service\PricingEngine;

final class HotelBookingPricingSnapshot
{
    /**
     * @param list<PricingLineItem> $lineItems
     */
    public function __construct(
        public readonly string $pricingKind,
        public readonly int $billableDays,
        public readonly string $baseDailyPrice,
        public readonly string $serviceFee,
        public readonly string $travelProtectionPrice,
        public readonly string $singleRoomPrice,
        public readonly string $quotedTotalPrice,
        public readonly string $totalPrice,
        public readonly string $finalTotalPrice,
        public readonly array $lineItems,
    ) {
    }

    public static function forQuote(
        HotelBookingPricingKind $pricingKind,
        int $billableDays,
        string $baseDailyPrice,
        string $serviceFee,
        string $travelProtectionPrice,
        string $singleRoomPrice,
        string $quotedTotalPrice,
        string $baseLabel,
        bool $includesTravelProtection,
        bool $includesSingleRoom,
    ): self {
        $lineItems = [
            new PricingLineItem(
                'hotel_base',
                $baseLabel,
                $billableDays,
                $baseDailyPrice,
                PricingEngine::formatAmount(PricingEngine::amountToCents($baseDailyPrice) * $billableDays),
                'DAY',
            ),
            new PricingLineItem(
                'hotel_service_fee',
                'Servicepauschale',
                1,
                $serviceFee,
                $serviceFee,
                'ONCE',
            ),
        ];

        if ($includesSingleRoom) {
            $lineItems[] = new PricingLineItem(
                'hotel_single_room',
                'Einzelzimmer-Zuschlag',
                $billableDays,
                PricingEngine::formatAmount(intdiv(PricingEngine::amountToCents($singleRoomPrice), max(1, $billableDays))),
                $singleRoomPrice,
                'DAY',
            );
        }

        if ($includesTravelProtection) {
            $lineItems[] = new PricingLineItem(
                'hotel_travel_protection',
                'Reiseschutz',
                1,
                $travelProtectionPrice,
                $travelProtectionPrice,
                'ONCE',
            );
        }

        return new self(
            $pricingKind->value,
            $billableDays,
            $baseDailyPrice,
            $serviceFee,
            $travelProtectionPrice,
            $singleRoomPrice,
            $quotedTotalPrice,
            $quotedTotalPrice,
            $quotedTotalPrice,
            $lineItems,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $quotedTotalPrice = self::readString(
            $data,
            'quotedTotalPrice',
            self::readString($data, 'totalPrice', '0.00'),
        );
        $totalPrice = self::readString($data, 'totalPrice', $quotedTotalPrice);

        return new self(
            self::readString($data, 'pricingKind', HotelBookingPricingKind::HOTEL->value),
            self::readInt($data, 'billableDays', 0),
            self::readString($data, 'baseDailyPrice', '0.00'),
            self::readString($data, 'serviceFee', '0.00'),
            self::readString($data, 'travelProtectionPrice', '0.00'),
            self::readString($data, 'singleRoomPrice', '0.00'),
            $quotedTotalPrice,
            $totalPrice,
            self::readString($data, 'finalTotalPrice', $totalPrice),
            PricingLineItem::listFromArray($data['lineItems'] ?? $data['items'] ?? []),
        );
    }

    public function finalize(string $finalTotalPrice): self
    {
        $lineItems = array_values(array_filter(
            $this->lineItems,
            static fn (PricingLineItem $item): bool => $item->key !== 'manual_adjustment',
        ));
        $adjustmentCents = PricingEngine::amountToCents($finalTotalPrice) - PricingEngine::amountToCents($this->quotedTotalPrice);

        if ($adjustmentCents !== 0) {
            $lineItems[] = PricingLineItem::manualAdjustment(PricingEngine::formatAmount($adjustmentCents));
        }

        return new self(
            $this->pricingKind,
            $this->billableDays,
            $this->baseDailyPrice,
            $this->serviceFee,
            $this->travelProtectionPrice,
            $this->singleRoomPrice,
            $this->quotedTotalPrice,
            $finalTotalPrice,
            $finalTotalPrice,
            $lineItems,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => 'hotelBooking',
            'pricingKind' => $this->pricingKind,
            'billableDays' => $this->billableDays,
            'baseDailyPrice' => $this->baseDailyPrice,
            'serviceFee' => $this->serviceFee,
            'travelProtectionPrice' => $this->travelProtectionPrice,
            'singleRoomPrice' => $this->singleRoomPrice,
            'quotedTotalPrice' => $this->quotedTotalPrice,
            'totalPrice' => $this->totalPrice,
            'finalTotalPrice' => $this->finalTotalPrice,
            'lineItems' => array_map(
                static fn (PricingLineItem $item): array => $item->toArray(),
                $this->lineItems,
            ),
        ];
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
