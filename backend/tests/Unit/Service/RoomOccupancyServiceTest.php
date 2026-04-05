<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Customer;
use App\Entity\Dog;
use App\Entity\HotelBooking;
use App\Entity\Room;
use App\Enum\HotelBookingState;
use App\Repository\HotelBookingRepository;
use App\Service\HotelAreaRequirementHelper;
use App\Service\RoomOccupancyService;
use PHPUnit\Framework\TestCase;

final class RoomOccupancyServiceTest extends TestCase
{
    public function testBuildAvailabilityForRoomReturnsUnavailableWhenPeakOccupancyExceedsCapacity(): void
    {
        $room = (new Room())
            ->setName('Suite 1')
            ->setSquareMeters(10);

        $existing = $this->createBooking('Existing', 60, '2026-04-05 08:00', '2026-04-05 12:00');
        $existing->setState(HotelBookingState::CONFIRMED);
        $existing->setRoom($room);

        $candidate = $this->createBooking('Candidate', 45, '2026-04-05 09:00', '2026-04-05 11:00');

        $repository = $this->createMock(HotelBookingRepository::class);
        $repository
            ->expects(self::once())
            ->method('findConfirmedAssignedOverlappingByRoom')
            ->willReturn([$existing]);

        $service = new RoomOccupancyService($repository, new HotelAreaRequirementHelper());
        $availability = $service->buildAvailabilityForRoom($room, $candidate);

        self::assertFalse($availability['available']);
        self::assertSame(11, $availability['peakRequiredSquareMeters']);
        self::assertSame(-1, $availability['remainingSquareMeters']);
        self::assertNotEmpty($availability['segments']);
    }

    public function testBuildOccupancyOverviewBuildsPerRoomSegments(): void
    {
        $room = (new Room())
            ->setName('Garten')
            ->setSquareMeters(14);

        $first = $this->createBooking('Mila', 48, '2026-04-05 08:00', '2026-04-05 10:00');
        $first->setState(HotelBookingState::CONFIRMED);
        $first->setRoom($room);

        $second = $this->createBooking('Bruno', 66, '2026-04-05 09:00', '2026-04-05 11:00');
        $second->setState(HotelBookingState::CONFIRMED);
        $second->setRoom($room);

        $repository = $this->createMock(HotelBookingRepository::class);
        $repository
            ->expects(self::once())
            ->method('findConfirmedAssignedByRange')
            ->willReturn([$first, $second]);

        $service = new RoomOccupancyService($repository, new HotelAreaRequirementHelper());
        $overview = $service->buildOccupancyOverview(
            [$room],
            new \DateTimeImmutable('2026-04-05 07:00'),
            new \DateTimeImmutable('2026-04-05 12:00'),
        );

        self::assertCount(1, $overview);
        self::assertSame(13, $overview[0]['peakRequiredSquareMeters']);
        self::assertCount(5, $overview[0]['segments']);
        self::assertSame(['Mila', 'Bruno'], $overview[0]['segments'][2]['dogNames']);
    }

    private function createBooking(
        string $dogName,
        int $heightCm,
        string $startAt,
        string $endAt,
    ): HotelBooking {
        $customer = (new Customer())
            ->setName('Customer')
            ->setEmail(sprintf('%s@example.com', strtolower($dogName)))
            ->setPassword('secret');
        $dog = (new Dog())
            ->setName($dogName)
            ->setCustomer($customer)
            ->setShoulderHeightCm($heightCm);

        return (new HotelBooking())
            ->setCustomer($customer)
            ->setDog($dog)
            ->setStartAt(new \DateTimeImmutable($startAt))
            ->setEndAt(new \DateTimeImmutable($endAt));
    }
}
