<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Dog;

final class HotelAreaRequirementHelper
{
    public function squareMetersForDog(Dog $dog): int
    {
        return $this->squareMetersForShoulderHeight($dog->getShoulderHeightCm());
    }

    public function squareMetersForShoulderHeight(int $shoulderHeightCm): int
    {
        return match (true) {
            $shoulderHeightCm <= 50 => 6,
            $shoulderHeightCm <= 65 => 8,
            default => 10,
        };
    }

    /**
     * @param list<int> $requirements
     */
    public function aggregateRequiredSquareMeters(array $requirements): int
    {
        if ($requirements === []) {
            return 0;
        }

        rsort($requirements);
        /** @var int $required */
        $required = array_shift($requirements);

        foreach ($requirements as $value) {
            $required += (int) ($value / 2);
        }

        return $required;
    }
}
