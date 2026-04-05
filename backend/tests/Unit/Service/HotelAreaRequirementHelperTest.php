<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Dog;
use App\Service\HotelAreaRequirementHelper;
use PHPUnit\Framework\TestCase;

final class HotelAreaRequirementHelperTest extends TestCase
{
    public function testSquareMetersForShoulderHeightReturnsExpectedValues(): void
    {
        $helper = new HotelAreaRequirementHelper();

        self::assertSame(6, $helper->squareMetersForShoulderHeight(45));
        self::assertSame(6, $helper->squareMetersForShoulderHeight(50));
        self::assertSame(8, $helper->squareMetersForShoulderHeight(51));
        self::assertSame(8, $helper->squareMetersForShoulderHeight(65));
        self::assertSame(10, $helper->squareMetersForShoulderHeight(66));
    }

    public function testAggregateRequiredSquareMetersHalvesEveryAdditionalDog(): void
    {
        $helper = new HotelAreaRequirementHelper();

        self::assertSame(0, $helper->aggregateRequiredSquareMeters([]));
        self::assertSame(8, $helper->aggregateRequiredSquareMeters([8]));
        self::assertSame(11, $helper->aggregateRequiredSquareMeters([8, 6]));
        self::assertSame(17, $helper->aggregateRequiredSquareMeters([10, 8, 6]));
    }

    public function testSquareMetersForDogUsesDogHeight(): void
    {
        $helper = new HotelAreaRequirementHelper();
        $dog = (new Dog())
            ->setName('Luna')
            ->setShoulderHeightCm(58);

        self::assertSame(8, $helper->squareMetersForDog($dog));
    }
}
