<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Hotel;

use App\Entity\HotelBooking;
use App\Repository\HotelBookingRepository;
use App\Service\ApiNormalizer;
use App\Support\LocalDateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/hotel/movements', name: 'api_admin_hotel_movements_')]
#[IsGranted('ROLE_ADMIN')]
final class MovementController extends AbstractController
{
    public function __construct(
        private readonly HotelBookingRepository $hotelBookingRepository,
        private readonly ApiNormalizer $normalizer,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[Route('/', name: 'list_slash', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $from = $this->parseDateTime((string) $request->query->get('from', ''));
        $to = $this->parseDateTime((string) $request->query->get('to', ''));

        if ($from === null || $to === null || $to <= $from) {
            return $this->json(['error' => 'Invalid movements range'], Response::HTTP_BAD_REQUEST);
        }

        $arrivals = $this->hotelBookingRepository->findConfirmedAssignedArrivalsByRange($from, $to);
        $departures = $this->hotelBookingRepository->findConfirmedAssignedDeparturesByRange($from, $to);

        return $this->json([
            'from' => LocalDateTime::formatWallTime($from),
            'to' => LocalDateTime::formatWallTime($to),
            'arrivals' => array_map(fn (HotelBooking $booking): array => $this->normalizer->normalizeHotelBooking($booking), $arrivals),
            'departures' => array_map(fn (HotelBooking $booking): array => $this->normalizer->normalizeHotelBooking($booking), $departures),
        ]);
    }

    private function parseDateTime(string $value): ?\DateTimeImmutable
    {
        if ($value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }
}
