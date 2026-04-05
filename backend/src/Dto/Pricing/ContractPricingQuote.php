<?php

declare(strict_types=1);

namespace App\Dto\Pricing;

final class ContractPricingQuote
{
    public function __construct(
        public readonly string $monthlyPrice,
        public readonly string $registrationFee,
        public readonly string $firstInvoiceTotal,
        public readonly string $monthlyUnitPrice,
        public readonly int $coursesPerWeek,
        public readonly bool $requiresRegistrationFee,
        public readonly ContractPricingSnapshot $snapshot,
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
    public function toArray(): array
    {
        return [
            'monthlyPrice' => $this->monthlyPrice,
            'registrationFee' => $this->registrationFee,
            'firstInvoiceTotal' => $this->firstInvoiceTotal,
            'monthlyUnitPrice' => $this->monthlyUnitPrice,
            'coursesPerWeek' => $this->coursesPerWeek,
            'requiresRegistrationFee' => $this->requiresRegistrationFee,
            'snapshot' => $this->snapshot->toArray(),
        ];
    }
}
