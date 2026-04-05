<?php

declare(strict_types=1);

namespace App\Controller\Api\Customer;

use App\Dto\HotelBookingRequestDto;
use App\Entity\Customer;
use App\Entity\HotelBooking;
use App\Enum\HotelBookingState;
use App\Repository\DogRepository;
use App\Repository\HotelBookingRepository;
use App\Service\ApiNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/customer/hotel-bookings', name: 'api_customer_hotel_bookings_')]
#[IsGranted('ROLE_CUSTOMER')]
final class HotelBookingController extends AbstractController
{
    public function __construct(
        private readonly HotelBookingRepository $hotelBookingRepository,
        private readonly DogRepository $dogRepository,
        private readonly ApiNormalizer $normalizer,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[Route('/', name: 'list_slash', methods: ['GET'])]
    public function list(Customer $customer): JsonResponse
    {
        $bookings = $this->hotelBookingRepository->findByCustomer($customer);

        return $this->json([
            'items' => array_map(fn (HotelBooking $booking) => $this->normalizer->normalizeHotelBooking($booking), $bookings),
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[Route('/', name: 'create_slash', methods: ['POST'])]
    public function create(
        Customer $customer,
        #[MapRequestPayload(acceptFormat: 'json')] HotelBookingRequestDto $dto,
    ): JsonResponse {
        $dog = $this->dogRepository->findOneByIdAndCustomer($dto->dogId ?? '', $customer);
        if ($dog === null) {
            return $this->json(['errors' => ['dogId' => 'Invalid or not your dog']], Response::HTTP_BAD_REQUEST);
        }

        $startAt = $this->parseDateTime($dto->startAt);
        if ($startAt === null) {
            return $this->json(['errors' => ['startAt' => 'Ungültiger Startzeitpunkt.']], Response::HTTP_BAD_REQUEST);
        }

        $endAt = $this->parseDateTime($dto->endAt);
        if ($endAt === null) {
            return $this->json(['errors' => ['endAt' => 'Ungültiger Endzeitpunkt.']], Response::HTTP_BAD_REQUEST);
        }

        if ($endAt <= $startAt) {
            return $this->json(['errors' => ['endAt' => 'Ende muss nach dem Beginn liegen.']], Response::HTTP_BAD_REQUEST);
        }

        $startMinutes = ((int) $startAt->format('H') * 60) + (int) $startAt->format('i');
        if ($startMinutes < 360 || $startMinutes > 1320) {
            return $this->json(['errors' => ['startAt' => 'Beginn muss zwischen 06:00 und 22:00 Uhr liegen.']], Response::HTTP_BAD_REQUEST);
        }

        $endMinutes = ((int) $endAt->format('H') * 60) + (int) $endAt->format('i');
        if ($endMinutes < 360 || $endMinutes > 1320) {
            return $this->json(['errors' => ['endAt' => 'Ende muss zwischen 06:00 und 22:00 Uhr liegen.']], Response::HTTP_BAD_REQUEST);
        }

        if ($dto->currentShoulderHeightCm !== null) {
            $dog->setShoulderHeightCm($dto->currentShoulderHeightCm);
            $this->dogRepository->save($dog, false);
        }

        $overlap = $this->hotelBookingRepository->findOverlappingActiveByDog($dog, $startAt, $endAt);
        if ($overlap !== null) {
            return $this->json(['errors' => ['startAt' => 'Für diesen Hund existiert bereits eine überlappende Hotelbuchung.']], Response::HTTP_BAD_REQUEST);
        }

        $booking = new HotelBooking();
        $booking->setCustomer($customer);
        $booking->setDog($dog);
        $booking->setStartAt($startAt);
        $booking->setEndAt($endAt);
        $booking->setState(HotelBookingState::REQUESTED);
        $this->hotelBookingRepository->save($booking);

        return $this->json($this->normalizer->normalizeHotelBooking($booking), Response::HTTP_CREATED);
    }

    private function parseDateTime(?string $value): ?\DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }
}
