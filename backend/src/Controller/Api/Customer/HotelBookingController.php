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
            return $this->json(['errors' => ['dogId' => 'Ungültiger Hund oder nicht Ihr Hund.']], Response::HTTP_BAD_REQUEST);
        }

        $startAt = $this->parseValidatedDateTime($dto->startAt);
        $endAt = $this->parseValidatedDateTime($dto->endAt);

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
        $this->hotelBookingRepository->save($booking);

        return $this->json($this->normalizer->normalizeHotelBooking($booking), Response::HTTP_CREATED);
    }

    private function parseValidatedDateTime(?string $value): \DateTimeImmutable
    {
        try {
            return new \DateTimeImmutable($value ?? throw new \LogicException('Hotel booking datetime is required.'));
        } catch (\Exception) {
            throw new \LogicException('Validated hotel booking datetime could not be parsed.');
        }
    }
}
