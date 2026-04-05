<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\HotelPeakSeason;
use App\Entity\PricingConfig;
use App\Repository\PricingConfigRepository;
use App\Support\AppClock;

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
        $year = (int) AppClock::today()->format('Y');
        $nextYear = $year + 1;

        return [
            [sprintf('%d-01-01', $year), sprintf('%d-01-11', $year)],
            [sprintf('%d-03-27', $year), sprintf('%d-04-14', $year)],
            [sprintf('%d-06-29', $year), sprintf('%d-09-13', $year)],
            [sprintf('%d-10-16', $year), sprintf('%d-11-02', $year)],
            [sprintf('%d-12-21', $year), sprintf('%d-01-10', $nextYear)],
        ];
    }
}
