<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Hotel;

use App\Entity\HotelBooking;
use App\Entity\Room;
use App\Repository\RoomRepository;
use App\Service\ApiNormalizer;
use App\Service\RoomOccupancyService;
use App\Support\LocalDateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/hotel/occupancy', name: 'api_admin_hotel_occupancy_')]
#[IsGranted('ROLE_ADMIN')]
final class OccupancyController extends AbstractController
{
    public function __construct(
        private readonly RoomRepository $roomRepository,
        private readonly RoomOccupancyService $roomOccupancyService,
        private readonly ApiNormalizer $normalizer,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[Route('/', name: 'list_slash', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $from = LocalDateTime::parseWallTime((string) $request->query->get('from', ''));
        $to = LocalDateTime::parseWallTime((string) $request->query->get('to', ''));

        if ($from === null || $to === null || $to <= $from) {
            return $this->json(['error' => 'Ungültiger Belegungszeitraum'], Response::HTTP_BAD_REQUEST);
        }

        $rooms = $this->roomRepository->findAllOrderByName();
        /**
         * @var list<array{
         *     room: Room,
         *     peakRequiredSquareMeters: int,
         *     segments: list<array{
         *         startAt: string,
         *         endAt: string,
         *         usedSquareMeters: int,
         *         freeSquareMeters: int,
         *         bookingCount: int,
         *         dogNames: list<string>,
         *         singleRoomActive: bool
         *     }>,
         *     bookings: list<HotelBooking>
         * }> $items
         */
        $items = $this->roomOccupancyService->buildOccupancyOverview($rooms, $from, $to);

        return $this->json([
            'from' => LocalDateTime::formatWallTime($from),
            'to' => LocalDateTime::formatWallTime($to),
            'items' => array_map(function (array $item): array {
                /** @var Room $room */
                $room = $item['room'];

                return [
                    'room' => $this->normalizer->normalizeRoom($room),
                    'peakRequiredSquareMeters' => $item['peakRequiredSquareMeters'],
                    'segments' => $item['segments'],
                    'bookings' => array_map(
                        fn (HotelBooking $booking): array => $this->normalizer->normalizeHotelBooking($booking),
                        $item['bookings'],
                    ),
                ];
            }, $items),
        ]);
    }
}
