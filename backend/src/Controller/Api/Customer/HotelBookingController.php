<?php

declare(strict_types=1);

namespace App\Controller\Api\Customer;

use App\Dto\CustomerReviewDecisionDto;
use App\Dto\HotelBookingRequestDto;
use App\Entity\Customer;
use App\Entity\HotelBooking;
use App\Enum\HotelBookingState;
use App\Repository\DogRepository;
use App\Repository\HotelBookingRepository;
use App\Service\ApiNormalizer;
use App\Service\PricingEngine;
use App\Service\RoomOccupancyService;
use App\Support\LocalDateTime;
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
        private readonly PricingEngine $pricingEngine,
        private readonly RoomOccupancyService $roomOccupancyService,
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
        [$dog, $startAt, $endAt, $validationError] = $this->validateBookingRequest($customer, $dto);
        if ($validationError instanceof JsonResponse) {
            return $validationError;
        }
        if ($dog === null || !$startAt instanceof \DateTimeImmutable || !$endAt instanceof \DateTimeImmutable) {
            throw new \LogicException('Validated hotel booking request is incomplete.');
        }

        $overlap = $this->hotelBookingRepository->findOverlappingActiveByDog($dog, $startAt, $endAt);
        if ($overlap !== null) {
            return $this->json(['errors' => ['startAt' => 'Für diesen Hund existiert bereits eine überlappende Hotelbuchung.']], Response::HTTP_BAD_REQUEST);
        }

        if ($dto->currentShoulderHeightCm !== null) {
            $dog->setShoulderHeightCm($dto->currentShoulderHeightCm);
            $this->dogRepository->save($dog, false);
        }

        $booking = new HotelBooking();
        $booking->setCustomer($customer);
        $booking->setDog($dog);
        $booking->setStartAt($startAt);
        $booking->setEndAt($endAt);
        $booking->setState(HotelBookingState::REQUESTED);
        $booking->setIncludesTravelProtection($dto->includesTravelProtection);
        $booking->setCustomerComment($dto->customerComment);
        $this->applyQuotedPrice($booking, $this->pricingEngine->previewHotelBooking($startAt, $endAt, $dto->includesTravelProtection));
        $this->hotelBookingRepository->save($booking);

        return $this->json($this->normalizer->normalizeHotelBooking($booking), Response::HTTP_CREATED);
    }

    #[Route('/preview', name: 'preview', methods: ['POST'])]
    #[Route('/preview/', name: 'preview_slash', methods: ['POST'])]
    public function preview(
        Customer $customer,
        #[MapRequestPayload(acceptFormat: 'json')] HotelBookingRequestDto $dto,
    ): JsonResponse {
        [, $startAt, $endAt, $validationError] = $this->validateBookingRequest($customer, $dto);
        if ($validationError instanceof JsonResponse) {
            return $validationError;
        }
        if (!$startAt instanceof \DateTimeImmutable || !$endAt instanceof \DateTimeImmutable) {
            throw new \LogicException('Validated hotel booking preview request is incomplete.');
        }

        return $this->json($this->pricingEngine->previewHotelBooking($startAt, $endAt, $dto->includesTravelProtection));
    }

    #[Route('/{id}/accept-price', name: 'accept_price', methods: ['POST'])]
    public function acceptPrice(Customer $customer, string $id): JsonResponse
    {
        $booking = $this->hotelBookingRepository->findOneByIdAndCustomer($id, $customer);
        if (!$booking instanceof HotelBooking) {
            return $this->json(['error' => 'Hotelbuchung nicht gefunden'], Response::HTTP_NOT_FOUND);
        }

        if ($booking->getState() !== HotelBookingState::PENDING_CUSTOMER_APPROVAL) {
            return $this->json(['error' => 'Nur angepasste Buchungen können bestätigt werden'], Response::HTTP_BAD_REQUEST);
        }

        if ($booking->getRoom() === null) {
            return $this->json(['errors' => ['roomId' => 'Vor dem Bestätigen muss ein Raum zugewiesen werden.']], Response::HTTP_BAD_REQUEST);
        }

        $availability = $this->roomOccupancyService->buildAvailabilityForRoom($booking->getRoom(), $booking);
        if (!$availability['available']) {
            return $this->json(['errors' => ['roomId' => 'Der zugewiesene Raum ist nicht mehr verfügbar.']], Response::HTTP_BAD_REQUEST);
        }

        $booking->setState(HotelBookingState::CONFIRMED);
        $this->hotelBookingRepository->save($booking);

        return $this->json($this->normalizer->normalizeHotelBooking($booking));
    }

    #[Route('/{id}/decline-price', name: 'decline_price', methods: ['POST'])]
    public function declinePrice(Customer $customer, string $id): JsonResponse
    {
        $booking = $this->hotelBookingRepository->findOneByIdAndCustomer($id, $customer);
        if (!$booking instanceof HotelBooking) {
            return $this->json(['error' => 'Hotelbuchung nicht gefunden'], Response::HTTP_NOT_FOUND);
        }

        if ($booking->getState() !== HotelBookingState::PENDING_CUSTOMER_APPROVAL) {
            return $this->json(['error' => 'Nur angepasste Buchungen können abgelehnt werden'], Response::HTTP_BAD_REQUEST);
        }

        $booking->setState(HotelBookingState::DECLINED);
        $booking->setRoom(null);
        $this->hotelBookingRepository->save($booking);

        return $this->json($this->normalizer->normalizeHotelBooking($booking));
    }

    #[Route('/{id}/resubmit', name: 'resubmit', methods: ['POST'])]
    public function resubmit(
        Customer $customer,
        string $id,
        #[MapRequestPayload(acceptFormat: 'json')] CustomerReviewDecisionDto $dto,
    ): JsonResponse {
        $booking = $this->hotelBookingRepository->findOneByIdAndCustomer($id, $customer);
        if (!$booking instanceof HotelBooking) {
            return $this->json(['error' => 'Hotelbuchung nicht gefunden'], Response::HTTP_NOT_FOUND);
        }

        if ($booking->getState() !== HotelBookingState::PENDING_CUSTOMER_APPROVAL) {
            return $this->json(['error' => 'Nur angepasste Buchungen können erneut eingereicht werden'], Response::HTTP_BAD_REQUEST);
        }

        $booking->setState(HotelBookingState::REQUESTED);
        $booking->setCustomerComment($dto->customerComment);
        $booking->setAdminComment(null);
        $this->applyQuotedPrice(
            $booking,
            $this->pricingEngine->previewHotelBooking(
                $booking->getStartAt(),
                $booking->getEndAt(),
                $booking->includesTravelProtection(),
            ),
        );
        $this->hotelBookingRepository->save($booking);

        return $this->json($this->normalizer->normalizeHotelBooking($booking));
    }

    private function parseValidatedDateTime(?string $value): \DateTimeImmutable
    {
        return LocalDateTime::parseWallTime($value)
            ?? throw new \LogicException('Validated hotel booking datetime could not be parsed.');
    }

    /**
     * @return array{0: \App\Entity\Dog, 1: \DateTimeImmutable, 2: \DateTimeImmutable, 3: null}|array{0: null, 1: null, 2: null, 3: JsonResponse}
     */
    private function validateBookingRequest(Customer $customer, HotelBookingRequestDto $dto): array
    {
        $dog = $this->dogRepository->findOneByIdAndCustomer($dto->dogId ?? '', $customer);
        if ($dog === null) {
            return [null, null, null, $this->json(['errors' => ['dogId' => 'Ungültiger Hund oder nicht Ihr Hund.']], Response::HTTP_BAD_REQUEST)];
        }

        $startAt = $this->parseValidatedDateTime($dto->startAt);
        $endAt = $this->parseValidatedDateTime($dto->endAt);

        return [$dog, $startAt, $endAt, null];
    }

    /**
     * @param array{
     *   pricingKind: \App\Enum\HotelBookingPricingKind,
     *   billableDays: int,
     *   quotedTotalPrice: string,
     *   serviceFee: string,
     *   travelProtectionPrice: string,
     *   snapshot: array<string, mixed>
     * } $quote
     */
    private function applyQuotedPrice(HotelBooking $booking, array $quote): void
    {
        $booking->setPricingKind($quote['pricingKind']);
        $booking->setBillableDays($quote['billableDays']);
        $booking->setQuotedTotalPrice($quote['quotedTotalPrice']);
        $booking->setTotalPrice($quote['quotedTotalPrice']);
        $booking->setServiceFee($quote['serviceFee']);
        $booking->setTravelProtectionPrice($quote['travelProtectionPrice']);
        $booking->setPricingSnapshot($quote['snapshot']);
    }
}
