<?php

declare(strict_types=1);

namespace App\Dto\Pricing;

use App\Enum\HotelBookingPricingKind;

final class HotelBookingPricingQuote
{
    public function __construct(
        public readonly HotelBookingPricingKind $pricingKind,
        public readonly int $billableDays,
        public readonly string $baseDailyPrice,
        public readonly string $serviceFee,
        public readonly string $travelProtectionPrice,
        public readonly string $singleRoomPrice,
        public readonly string $quotedTotalPrice,
        public readonly bool $includesTravelProtection,
        public readonly bool $includesSingleRoom,
        public readonly HotelBookingPricingSnapshot $snapshot,
    ) {
    }

    /**
     * @return array{
     *   pricingKind: string,
     *   billableDays: int,
     *   baseDailyPrice: string,
     *   serviceFee: string,
     *   travelProtectionPrice: string,
     *   singleRoomPrice: string,
     *   quotedTotalPrice: string,
     *   includesTravelProtection: bool,
     *   includesSingleRoom: bool,
     *   snapshot: array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'pricingKind' => $this->pricingKind->value,
            'billableDays' => $this->billableDays,
            'baseDailyPrice' => $this->baseDailyPrice,
            'serviceFee' => $this->serviceFee,
            'travelProtectionPrice' => $this->travelProtectionPrice,
            'singleRoomPrice' => $this->singleRoomPrice,
            'quotedTotalPrice' => $this->quotedTotalPrice,
            'includesTravelProtection' => $this->includesTravelProtection,
            'includesSingleRoom' => $this->includesSingleRoom,
            'snapshot' => $this->snapshot->toArray(),
        ];
    }
}
