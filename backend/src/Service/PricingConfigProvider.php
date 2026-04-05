<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\HotelPeakSeason;
use App\Entity\PricingConfig;
use App\Repository\PricingConfigRepository;

final class PricingConfigProvider
{
    public function __construct(
        private readonly PricingConfigRepository $pricingConfigRepository,
    ) {
    }

    public function getCurrent(): PricingConfig
    {
        $pricingConfig = $this->pricingConfigRepository->findCurrent();
        if ($pricingConfig instanceof PricingConfig) {
            return $pricingConfig;
        }

        $pricingConfig = $this->createDefaultConfig();
        $this->pricingConfigRepository->save($pricingConfig);

        return $pricingConfig;
    }

    private function createDefaultConfig(): PricingConfig
    {
        $pricingConfig = new PricingConfig();

        foreach (self::defaultPeakSeasonRanges() as [$startDate, $endDate]) {
            $season = new HotelPeakSeason();
            $season->setStartDate(new \DateTimeImmutable($startDate));
            $season->setEndDate(new \DateTimeImmutable($endDate));
            $pricingConfig->addHotelPeakSeason($season);
        }

        return $pricingConfig;
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function defaultPeakSeasonRanges(): array
    {
        return [
            ['2026-01-01', '2026-01-11'],
            ['2026-03-27', '2026-04-14'],
            ['2026-06-29', '2026-09-13'],
            ['2026-10-16', '2026-11-02'],
            ['2026-12-21', '2027-01-10'],
        ];
    }
}
