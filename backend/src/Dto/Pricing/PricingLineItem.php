<?php

declare(strict_types=1);

namespace App\Dto\Pricing;

final class PricingLineItem
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly int $quantity,
        public readonly string $unitPrice,
        public readonly string $amount,
        public readonly string $billingPeriod,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            self::readString($data, 'key'),
            self::readString($data, 'label'),
            self::readInt($data, 'quantity', 1),
            self::readString($data, 'unitPrice', '0.00'),
            self::readString($data, 'amount', '0.00'),
            self::readString($data, 'billingPeriod', 'ONCE'),
        );
    }

    public static function manualAdjustment(string $amount): self
    {
        return new self(
            'manual_adjustment',
            'Manuelle Preisanpassung',
            1,
            $amount,
            $amount,
            'ONCE',
        );
    }

    public static function listFromArray(mixed $data): array
    {
        if (!is_array($data)) {
            return [];
        }

        $items = [];
        foreach ($data as $item) {
            if (is_array($item)) {
                /* @var array<string, mixed> $item */
                $items[] = self::fromArray($item);
            }
        }

        return $items;
    }

    /**
     * @return array{
     *   key: string,
     *   label: string,
     *   quantity: int,
     *   unitPrice: string,
     *   amount: string,
     *   billingPeriod: string
     * }
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'quantity' => $this->quantity,
            'unitPrice' => $this->unitPrice,
            'amount' => $this->amount,
            'billingPeriod' => $this->billingPeriod,
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
