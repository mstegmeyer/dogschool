<?php

declare(strict_types=1);

namespace App\Controller\Api\Customer;

use App\Dto\BookingDogRequestDto;
use App\Entity\CourseDate;
use App\Entity\Customer;
use App\Repository\CourseDateRepository;
use App\Repository\DogRepository;
use App\Service\ApiNormalizer;
use App\Service\CreditService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/customer/calendar', name: 'api_customer_calendar_')]
#[IsGranted('ROLE_CUSTOMER')]
final class CalendarController extends AbstractController
{
    public function __construct(
        private readonly CourseDateRepository $courseDateRepository,
        private readonly DogRepository $dogRepository,
        private readonly CreditService $creditService,
        private readonly ApiNormalizer $normalizer,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request, Customer $customer): JsonResponse
    {
        $tz = new \DateTimeZone(CourseDate::TIMEZONE);
        $from = $request->query->get('from');

        if ($from !== null && $from !== '') {
            $fromDate = new \DateTimeImmutable($from, $tz);
            $days = $request->query->getInt('days', 14);
            $dates = $this->courseDateRepository->findFromDate($fromDate, $days);

            return $this->json([
                'from' => $fromDate->format('Y-m-d'),
                'items' => array_map(
                    fn (CourseDate $cd) => $this->normalizer->normalizeCourseDateForCustomer($cd, $customer),
                    $dates,
                ),
            ]);
        }

        [$rangeFrom, $rangeTo] = $this->resolveWeekOrMonth($request);
        $dates = $this->courseDateRepository->findByDateRange($rangeFrom, $rangeTo);

        return $this->json([
            'from' => $rangeFrom->format('Y-m-d'),
            'to' => $rangeTo->format('Y-m-d'),
            'items' => array_map(
                fn (CourseDate $cd) => $this->normalizer->normalizeCourseDateForCustomer($cd, $customer),
                $dates,
            ),
        ]);
    }

    #[Route('/course-dates/{id}/book', name: 'book', methods: ['POST'])]
    public function book(
        string $id,
        Customer $customer,
        #[MapRequestPayload(acceptFormat: 'json')] BookingDogRequestDto $dto,
    ): JsonResponse {
        $courseDate = $this->courseDateRepository->find($id);
        if ($courseDate === null) {
            return $this->json(['error' => 'CourseDate not found'], Response::HTTP_NOT_FOUND);
        }

        $dog = $this->dogRepository->findOneByIdAndCustomer((string) $dto->dogId, $customer);
        if ($dog === null) {
            return $this->json(['error' => 'Dog not found'], Response::HTTP_NOT_FOUND);
        }

        $result = $this->creditService->bookCourseDate($customer, $courseDate, $dog);
        if (is_string($result)) {
            return $this->json(['error' => $result], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'booking' => $this->normalizer->normalizeBooking($result),
            'creditBalance' => $this->creditService->getBalance($customer),
        ], Response::HTTP_CREATED);
    }

    #[Route('/course-dates/{id}/book', name: 'cancel_booking', methods: ['DELETE'])]
    public function cancelBooking(string $id, Customer $customer, Request $request): JsonResponse
    {
        $dogId = $request->query->getString('dogId');
        if ($dogId === '') {
            return $this->json(['error' => 'dogId query parameter is required'], Response::HTTP_BAD_REQUEST);
        }

        $courseDate = $this->courseDateRepository->find($id);
        if ($courseDate === null) {
            return $this->json(['error' => 'CourseDate not found'], Response::HTTP_NOT_FOUND);
        }

        $dog = $this->dogRepository->findOneByIdAndCustomer($dogId, $customer);
        if ($dog === null) {
            return $this->json(['error' => 'Dog not found'], Response::HTTP_NOT_FOUND);
        }

        $result = $this->creditService->cancelBooking($customer, $courseDate, $dog);
        if (is_string($result)) {
            return $this->json(['error' => $result], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'booking' => $this->normalizer->normalizeBooking($result),
            'creditBalance' => $this->creditService->getBalance($customer),
        ]);
    }

    /**
     * @return array{\DateTimeImmutable, \DateTimeImmutable}
     */
    private function resolveWeekOrMonth(Request $request): array
    {
        $tz = new \DateTimeZone(CourseDate::TIMEZONE);
        $week = $request->query->get('week');
        $month = $request->query->get('month');

        if ($week !== null && $week !== '') {
            $dt = new \DateTimeImmutable($week, $tz);
            $from = $dt->modify('monday this week')->setTime(0, 0);
            $to = $from->modify('sunday this week')->setTime(23, 59, 59);
        } elseif ($month !== null && $month !== '') {
            $from = new \DateTimeImmutable($month . '-01', $tz);
            $to = $from->modify('last day of this month')->setTime(23, 59, 59);
        } else {
            $from = new \DateTimeImmutable('monday this week', $tz)->setTime(0, 0);
            $to = $from->modify('sunday this week')->setTime(23, 59, 59);
        }

        return [$from, $to];
    }
}
