<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\HotelBooking;
use App\Entity\Room;
use App\Repository\HotelBookingRepository;
use App\Support\LocalDateTime;

final class RoomOccupancyService
{
    public function __construct(
        private readonly HotelBookingRepository $hotelBookingRepository,
        private readonly HotelAreaRequirementHelper $areaRequirementHelper,
    ) {
    }

    /**
     * @return array{
     *     available: bool,
     *     requiredSquareMeters: int,
     *     peakRequiredSquareMeters: int,
     *     remainingSquareMeters: int,
     *     segments: list<array{
     *         startAt: string,
     *         endAt: string,
     *         usedSquareMeters: int,
     *         freeSquareMeters: int,
     *         bookingCount: int,
     *         dogNames: list<string>
     *     }>
     * }
     */
    public function buildAvailabilityForRoom(Room $room, HotelBooking $candidate): array
    {
        $requiredSquareMeters = $this->areaRequirementHelper->squareMetersForDog(
            $candidate->getDog() ?? throw new \LogicException('Hotel booking dog is required.'),
        );

        $existingBookings = $this->hotelBookingRepository->findConfirmedAssignedOverlappingByRoom(
            $room,
            $candidate->getStartAt(),
            $candidate->getEndAt(),
            $candidate->getId(),
        );

        $segments = $this->buildSegments(
            $candidate->getStartAt(),
            $candidate->getEndAt(),
            array_merge($existingBookings, [$candidate]),
            $room->getSquareMeters(),
        );

        $peakRequired = 0;
        foreach ($segments as $segment) {
            $peakRequired = max($peakRequired, $segment['usedSquareMeters']);
        }

        return [
            'available' => $peakRequired <= $room->getSquareMeters(),
            'requiredSquareMeters' => $requiredSquareMeters,
            'peakRequiredSquareMeters' => $peakRequired,
            'remainingSquareMeters' => $room->getSquareMeters() - $peakRequired,
            'segments' => $segments,
        ];
    }

    /**
     * @param list<Room> $rooms
     *
     * @return list<array<string, mixed>>
     */
    public function buildAvailableRoomsForBooking(HotelBooking $booking, array $rooms): array
    {
        $items = [];

        foreach ($rooms as $room) {
            $items[] = [
                'room' => $room,
                ...$this->buildAvailabilityForRoom($room, $booking),
            ];
        }

        usort($items, static function (array $left, array $right): int {
            if ($left['available'] !== $right['available']) {
                return $left['available'] ? -1 : 1;
            }

            return strcmp((string) $left['room']->getName(), (string) $right['room']->getName());
        });

        return $items;
    }

    /**
     * @param list<Room> $rooms
     *
     * @return list<array<string, mixed>>
     */
    public function buildOccupancyOverview(array $rooms, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $bookings = $this->hotelBookingRepository->findConfirmedAssignedByRange($from, $to);
        $byRoom = [];
        foreach ($bookings as $booking) {
            $roomId = $booking->getRoom()?->getId();
            if ($roomId === null) {
                continue;
            }
            $byRoom[$roomId] ??= [];
            $byRoom[$roomId][] = $booking;
        }

        $items = [];
        foreach ($rooms as $room) {
            $roomBookings = $byRoom[$room->getId()] ?? [];
            $segments = $this->buildSegments($from, $to, $roomBookings, $room->getSquareMeters());
            $peakRequired = 0;
            foreach ($segments as $segment) {
                $peakRequired = max($peakRequired, $segment['usedSquareMeters']);
            }

            $items[] = [
                'room' => $room,
                'peakRequiredSquareMeters' => $peakRequired,
                'segments' => $segments,
                'bookings' => $roomBookings,
            ];
        }

        return $items;
    }

    /**
     * @param list<HotelBooking> $bookings
     *
     * @phpstan-type ActiveBooking array{id: string, dogName: string, requirement: int}
     *
     * @return list<array{
     *     startAt: string,
     *     endAt: string,
     *     usedSquareMeters: int,
     *     freeSquareMeters: int,
     *     bookingCount: int,
     *     dogNames: list<string>
     * }>
     */
    private function buildSegments(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        array $bookings,
        int $roomSquareMeters,
    ): array {
        $fromTimestamp = LocalDateTime::normalizeWallTime($from)->getTimestamp();
        $toTimestamp = LocalDateTime::normalizeWallTime($to)->getTimestamp();
        $boundaries = [$fromTimestamp, $toTimestamp];
        $activeBookings = [];
        $startingBookings = [];
        $endingBookings = [];

        foreach ($bookings as $booking) {
            $start = max($fromTimestamp, LocalDateTime::normalizeWallTime($booking->getStartAt())->getTimestamp());
            $end = min($toTimestamp, LocalDateTime::normalizeWallTime($booking->getEndAt())->getTimestamp());

            if ($start >= $end) {
                continue;
            }

            $dog = $booking->getDog();
            if ($dog === null) {
                continue;
            }

            $activeBooking = [
                'id' => $booking->getId(),
                'dogName' => $dog->getName(),
                'requirement' => $this->areaRequirementHelper->squareMetersForDog($dog),
            ];

            $boundaries[] = $start;
            $boundaries[] = $end;
            $startingBookings[$start][] = $activeBooking;
            $endingBookings[$end][] = $booking->getId();
        }

        $boundaries = array_values(array_unique($boundaries));
        sort($boundaries);

        $segments = [];
        for ($index = 0; $index < count($boundaries) - 1; ++$index) {
            $segmentStart = $boundaries[$index];
            $segmentEnd = $boundaries[$index + 1];

            if ($segmentStart >= $segmentEnd) {
                continue;
            }

            foreach ($endingBookings[$segmentStart] ?? [] as $bookingId) {
                unset($activeBookings[$bookingId]);
            }

            foreach ($startingBookings[$segmentStart] ?? [] as $activeBooking) {
                $activeBookings[$activeBooking['id']] = $activeBooking;
            }

            $requirements = array_map(
                static fn (array $activeBooking): int => $activeBooking['requirement'],
                array_values($activeBookings),
            );
            $dogNames = array_map(
                static fn (array $activeBooking): string => $activeBooking['dogName'],
                array_values($activeBookings),
            );
            $usedSquareMeters = $this->areaRequirementHelper->aggregateRequiredSquareMeters($requirements);

            $segments[] = [
                'startAt' => LocalDateTime::formatWallTime(LocalDateTime::fromTimestamp($segmentStart)),
                'endAt' => LocalDateTime::formatWallTime(LocalDateTime::fromTimestamp($segmentEnd)),
                'usedSquareMeters' => $usedSquareMeters,
                'freeSquareMeters' => $roomSquareMeters - $usedSquareMeters,
                'bookingCount' => count($dogNames),
                'dogNames' => $dogNames,
            ];
        }

        return $segments;
    }
}
