<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Hotel;

use App\Dto\HotelBookingRoomAssignDto;
use App\Entity\HotelBooking;
use App\Enum\HotelBookingState;
use App\Repository\HotelBookingRepository;
use App\Repository\RoomRepository;
use App\Service\ApiNormalizer;
use App\Service\PricingEngine;
use App\Service\RoomOccupancyService;
use App\Support\LocalDateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/hotel/bookings', name: 'api_admin_hotel_bookings_')]
#[IsGranted('ROLE_ADMIN')]
final class BookingController extends AbstractController
{
    public function __construct(
        private readonly HotelBookingRepository $hotelBookingRepository,
        private readonly RoomRepository $roomRepository,
        private readonly RoomOccupancyService $roomOccupancyService,
        private readonly ApiNormalizer $normalizer,
        private readonly PricingEngine $pricingEngine,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[Route('/', name: 'list_slash', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $stateParam = $request->query->get('state');
        $state = is_string($stateParam) && $stateParam !== '' && $stateParam !== 'all'
            ? HotelBookingState::tryFrom($stateParam)
            : null;
        $fromRaw = (string) $request->query->get('from', '');
        $toRaw = (string) $request->query->get('to', '');
        $from = LocalDateTime::parseWallTime($fromRaw);
        $to = LocalDateTime::parseWallTime($toRaw);

        if (($fromRaw !== '' && $from === null) || ($toRaw !== '' && $to === null)) {
            return $this->json(['error' => 'Ungültiger Buchungszeitraum'], Response::HTTP_BAD_REQUEST);
        }

        if ($from !== null && $to !== null && $to <= $from) {
            return $this->json(['error' => 'Ungültiger Buchungszeitraum'], Response::HTTP_BAD_REQUEST);
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 20)));
        $sortBy = match ($request->query->get('sort')) {
            'createdAt' => 'createdAt',
            'state' => 'state',
            default => 'startAt',
        };
        $sortDirection = strtolower((string) $request->query->get('direction', 'asc')) === 'desc'
            ? 'DESC'
            : 'ASC';
        $total = $this->hotelBookingRepository->countForAdminList($state, $from, $to);
        $bookings = $this->hotelBookingRepository->findPageForAdminList($page, $limit, $state, $sortBy, $sortDirection, $from, $to);

        return $this->json([
            'items' => array_map(fn (HotelBooking $booking) => $this->normalizer->normalizeHotelBooking($booking), $bookings),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => max(1, (int) ceil($total / $limit)),
            ],
        ]);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $booking = $this->hotelBookingRepository->find($id);
        if (!$booking instanceof HotelBooking) {
            return $this->json(['error' => 'Hotelbuchung nicht gefunden'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->buildDetailResponse($booking));
    }

    #[Route('/{id}/room', name: 'assign_room', methods: ['PUT'])]
    public function assignRoom(
        string $id,
        #[MapRequestPayload(acceptFormat: 'json')] HotelBookingRoomAssignDto $dto,
    ): JsonResponse {
        $booking = $this->hotelBookingRepository->find($id);
        if (!$booking instanceof HotelBooking) {
            return $this->json(['error' => 'Hotelbuchung nicht gefunden'], Response::HTTP_NOT_FOUND);
        }

        if ($booking->getState() === HotelBookingState::DECLINED) {
            return $this->json(['errors' => ['roomId' => 'Abgelehnte Buchungen können keinem Zimmer zugewiesen werden.']], Response::HTTP_BAD_REQUEST);
        }

        $room = $this->roomRepository->find($dto->roomId);
        if ($room === null) {
            return $this->json(['errors' => ['roomId' => 'Raum nicht gefunden.']], Response::HTTP_BAD_REQUEST);
        }

        $availability = $this->roomOccupancyService->buildAvailabilityForRoom($room, $booking);
        if (!$availability['available']) {
            return $this->json(['errors' => ['roomId' => 'Raum ist im ausgewählten Zeitraum nicht verfügbar.']], Response::HTTP_BAD_REQUEST);
        }

        $booking->setRoom($room);
        $this->hotelBookingRepository->save($booking);

        return $this->json($this->buildDetailResponse($booking));
    }

    #[Route('/{id}/confirm', name: 'confirm', methods: ['POST'])]
    public function confirm(Request $request, string $id): JsonResponse
    {
        $booking = $this->hotelBookingRepository->find($id);
        if (!$booking instanceof HotelBooking) {
            return $this->json(['error' => 'Hotelbuchung nicht gefunden'], Response::HTTP_NOT_FOUND);
        }

        if ($booking->getRoom() === null) {
            return $this->json(['errors' => ['roomId' => 'Vor dem Bestätigen muss ein Raum zugewiesen werden.']], Response::HTTP_BAD_REQUEST);
        }

        $availability = $this->roomOccupancyService->buildAvailabilityForRoom($booking->getRoom(), $booking);
        if (!$availability['available']) {
            return $this->json(['errors' => ['roomId' => 'Der zugewiesene Raum ist nicht mehr verfügbar.']], Response::HTTP_BAD_REQUEST);
        }

        $payload = $this->parsePayload($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $quote = $this->pricingEngine->previewHotelBooking(
            $booking->getStartAt(),
            $booking->getEndAt(),
            $booking->includesTravelProtection(),
        );
        $finalPrice = $payload['totalPrice'] ?? $quote['quotedTotalPrice'];
        $quotedTotalCents = PricingEngine::amountToCents($quote['quotedTotalPrice']);
        $finalTotalCents = PricingEngine::amountToCents($finalPrice);

        $booking->setPricingKind($quote['pricingKind']);
        $booking->setBillableDays($quote['billableDays']);
        $booking->setQuotedTotalPrice($quote['quotedTotalPrice']);
        $booking->setServiceFee($quote['serviceFee']);
        $booking->setTravelProtectionPrice($quote['travelProtectionPrice']);
        $booking->setPricingSnapshot(PricingEngine::finalizeHotelBookingSnapshot($quote['snapshot'], $finalPrice));
        $booking->setTotalPrice($finalPrice);
        $booking->setAdminComment($payload['adminComment'] ?? $booking->getAdminComment());
        $booking->setState(
            $finalTotalCents > $quotedTotalCents
                ? HotelBookingState::PENDING_CUSTOMER_APPROVAL
                : HotelBookingState::CONFIRMED
        );
        $this->hotelBookingRepository->save($booking);

        return $this->json($this->buildDetailResponse($booking));
    }

    #[Route('/{id}/decline', name: 'decline', methods: ['POST'])]
    public function decline(Request $request, string $id): JsonResponse
    {
        $booking = $this->hotelBookingRepository->find($id);
        if (!$booking instanceof HotelBooking) {
            return $this->json(['error' => 'Hotelbuchung nicht gefunden'], Response::HTTP_NOT_FOUND);
        }

        $payload = $this->parsePayload($request, false);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $booking->setState(HotelBookingState::DECLINED);
        $booking->setRoom(null);
        $booking->setAdminComment($payload['adminComment'] ?? $booking->getAdminComment());
        $this->hotelBookingRepository->save($booking);

        return $this->json($this->normalizer->normalizeHotelBooking($booking));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDetailResponse(HotelBooking $booking): array
    {
        $rooms = $this->roomRepository->findAllOrderByName();
        $availableRooms = $this->roomOccupancyService->buildAvailableRoomsForBooking($booking, $rooms);

        return [
            ...$this->normalizer->normalizeHotelBooking($booking),
            'availableRooms' => array_map(function (array $item): array {
                /** @var \App\Entity\Room $room */
                $room = $item['room'];

                return [
                    'roomId' => $room->getId(),
                    'roomName' => $room->getName(),
                    'squareMeters' => $room->getSquareMeters(),
                    'available' => $item['available'],
                    'requiredSquareMeters' => $item['requiredSquareMeters'],
                    'peakRequiredSquareMeters' => $item['peakRequiredSquareMeters'],
                    'remainingSquareMeters' => $item['remainingSquareMeters'],
                    'segments' => $item['segments'],
                ];
            }, $availableRooms),
        ];
    }

    /**
     * @return array{totalPrice?: string, adminComment?: string}|JsonResponse
     */
    private function parsePayload(Request $request, bool $allowPrice = true): array|JsonResponse
    {
        if (($request->getContent() ?: '') === '') {
            return [];
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'Ungültiger Request Body.'], Response::HTTP_BAD_REQUEST);
        }

        $result = [];
        if ($allowPrice && array_key_exists('totalPrice', $payload)) {
            if (!is_scalar($payload['totalPrice']) || !preg_match('/^-?\d+(?:[.,]\d{1,2})?$/', (string) $payload['totalPrice'])) {
                return $this->json(['errors' => ['totalPrice' => 'Bitte einen gültigen Preis angeben.']], Response::HTTP_BAD_REQUEST);
            }

            $result['totalPrice'] = number_format((float) str_replace(',', '.', (string) $payload['totalPrice']), 2, '.', '');
        }

        if (array_key_exists('adminComment', $payload) && $payload['adminComment'] !== null && !is_string($payload['adminComment'])) {
            return $this->json(['errors' => ['adminComment' => 'Der Kommentar ist ungültig.']], Response::HTTP_BAD_REQUEST);
        }

        if (array_key_exists('adminComment', $payload)) {
            $result['adminComment'] = $payload['adminComment'];
        }

        return $result;
    }
}
