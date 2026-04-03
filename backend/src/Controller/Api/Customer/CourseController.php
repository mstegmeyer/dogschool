<?php

declare(strict_types=1);

namespace App\Controller\Api\Customer;

use App\Entity\Course;
use App\Entity\CourseDate;
use App\Entity\Customer;
use App\Entity\Notification;
use App\Repository\CourseRepository;
use App\Repository\CourseDateRepository;
use App\Repository\CustomerRepository;
use App\Repository\NotificationRepository;
use App\Service\ApiNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/customer/courses', name: 'api_customer_courses_')]
#[IsGranted('ROLE_CUSTOMER')]
final class CourseController extends AbstractController
{
    public function __construct(
        private readonly CourseRepository $courseRepository,
        private readonly CourseDateRepository $courseDateRepository,
        private readonly NotificationRepository $notificationRepository,
        private readonly CustomerRepository $customerRepository,
        private readonly ApiNormalizer $normalizer,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Customer $customer): JsonResponse
    {
        $courses = $this->courseRepository->findNonArchived();

        return $this->json(['items' => array_map(fn (Course $c) => $this->normalizer->normalizeCourse($c), $courses)]);
    }

    #[Route('/subscribed', name: 'subscribed', methods: ['GET'])]
    public function subscribed(Customer $customer): JsonResponse
    {
        $items = array_map(
            fn (Course $c) => $this->normalizer->normalizeCourse($c),
            $customer->getSubscribedCourses()->toArray()
        );

        return $this->json(['items' => $items]);
    }

    #[Route('/{id}/detail', name: 'detail', methods: ['GET'])]
    public function detail(string $id): JsonResponse
    {
        $course = $this->courseRepository->find($id);
        if ($course === null) {
            return $this->json(['error' => 'Course not found'], Response::HTTP_NOT_FOUND);
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone(CourseDate::TIMEZONE));
        $upcomingUntil = $now->modify('+1 month');
        $notificationSince = $now->modify('-6 months');

        $upcomingDates = $this->courseDateRepository->findUpcomingForCourse($course, $now, $upcomingUntil);
        $notifications = $this->notificationRepository->findRecentHistoryByCourse($course->getId(), $notificationSince);

        return $this->json([
            'course' => $this->normalizer->normalizeCourse($course),
            'upcomingDates' => array_map(
                fn (CourseDate $courseDate) => $this->normalizer->normalizeCourseDate($courseDate),
                $upcomingDates
            ),
            'notifications' => array_map(
                fn (Notification $notification) => $this->normalizer->normalizeNotification($notification),
                $notifications
            ),
        ]);
    }

    #[Route('/{id}/subscribe', name: 'subscribe', methods: ['POST'])]
    public function subscribe(Customer $customer, string $id): JsonResponse
    {
        $course = $this->courseRepository->find($id);
        if ($course === null) {
            return $this->json(['error' => 'Course not found'], Response::HTTP_NOT_FOUND);
        }
        if ($course->isArchived()) {
            return $this->json(['error' => 'Course is archived'], Response::HTTP_BAD_REQUEST);
        }
        $customer->addSubscribedCourse($course);
        $this->customerRepository->save($customer);

        return $this->json($this->normalizer->normalizeCourse($course));
    }

    #[Route('/{id}/subscribe', name: 'unsubscribe', methods: ['DELETE'])]
    public function unsubscribe(Customer $customer, string $id): JsonResponse
    {
        $course = $this->courseRepository->find($id);
        if ($course === null) {
            return $this->json(['error' => 'Course not found'], Response::HTTP_NOT_FOUND);
        }
        $customer->removeSubscribedCourse($course);
        $this->customerRepository->save($customer);

        return $this->json(['success' => true]);
    }
}
