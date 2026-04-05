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
        $boundaries = [$from->getTimestamp(), $to->getTimestamp()];

        foreach ($bookings as $booking) {
            $start = max($from->getTimestamp(), $booking->getStartAt()->getTimestamp());
            $end = min($to->getTimestamp(), $booking->getEndAt()->getTimestamp());

            if ($start >= $end) {
                continue;
            }

            $boundaries[] = $start;
            $boundaries[] = $end;
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

            $active = array_values(array_filter(
                $bookings,
                static fn (HotelBooking $booking): bool => $booking->getStartAt()->getTimestamp() < $segmentEnd
                    && $booking->getEndAt()->getTimestamp() > $segmentStart,
            ));

            $requirements = [];
            $dogNames = [];
            foreach ($active as $booking) {
                $dog = $booking->getDog();
                if ($dog === null) {
                    continue;
                }

                $requirements[] = $this->areaRequirementHelper->squareMetersForDog($dog);
                $dogNames[] = $dog->getName();
            }

            $usedSquareMeters = $this->areaRequirementHelper->aggregateRequiredSquareMeters($requirements);

            $segments[] = [
                'startAt' => LocalDateTime::formatWallTime((new \DateTimeImmutable())->setTimestamp($segmentStart)),
                'endAt' => LocalDateTime::formatWallTime((new \DateTimeImmutable())->setTimestamp($segmentEnd)),
                'usedSquareMeters' => $usedSquareMeters,
                'freeSquareMeters' => $roomSquareMeters - $usedSquareMeters,
                'bookingCount' => count($dogNames),
                'dogNames' => $dogNames,
            ];
        }

        return $segments;
    }
}
