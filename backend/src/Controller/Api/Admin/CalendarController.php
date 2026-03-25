<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Dto\CourseDateMoveDto;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\CourseDateRepository;
use App\Repository\NotificationRepository;
use App\Service\ApiNormalizer;
use App\Service\CreditService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin/calendar', name: 'api_admin_calendar_')]
#[IsGranted('ROLE_ADMIN')]
final class CalendarController extends AbstractController
{
    public function __construct(
        private readonly CourseDateRepository $courseDateRepository,
        private readonly NotificationRepository $notificationRepository,
        private readonly ApiNormalizer $normalizer,
        private readonly ValidatorInterface $validator,
        private readonly CreditService $creditService,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        [$from, $to] = $this->resolveRange($request);

        $dates = $this->courseDateRepository->findByDateRange($from, $to);

        return $this->json([
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'items' => array_map(fn ($cd) => $this->normalizer->normalizeCourseDateForAdmin($cd), $dates),
        ]);
    }

    #[Route('/course-dates/{id}', name: 'get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $cd = $this->courseDateRepository->find($id);
        if ($cd === null) {
            return $this->json(['error' => 'CourseDate not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->normalizer->normalizeCourseDateForAdmin($cd));
    }

    #[Route('/course-dates/{id}/move', name: 'move', methods: ['PUT', 'PATCH'])]
    public function move(
        string $id,
        #[MapRequestPayload(acceptFormat: 'json')] CourseDateMoveDto $dto,
    ): JsonResponse {
        $cd = $this->courseDateRepository->find($id);
        if ($cd === null) {
            return $this->json(['error' => 'CourseDate not found'], Response::HTTP_NOT_FOUND);
        }

        $cd->setDate(new \DateTimeImmutable($dto->date));

        if ($dto->startTime !== null) {
            $cd->setStartTime($dto->startTime);
        }
        if ($dto->endTime !== null) {
            $cd->setEndTime($dto->endTime);
        }

        $errors = $this->validator->validate($cd);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->normalizer->violationsToArray($errors)], Response::HTTP_BAD_REQUEST);
        }

        $this->courseDateRepository->save($cd);

        return $this->json($this->normalizer->normalizeCourseDateForAdmin($cd));
    }

    #[Route('/course-dates/{id}/cancel', name: 'cancel', methods: ['POST'])]
    public function cancel(string $id, User $user, Request $request): JsonResponse
    {
        $cd = $this->courseDateRepository->find($id);
        if ($cd === null) {
            return $this->json(['error' => 'CourseDate not found'], Response::HTTP_NOT_FOUND);
        }

        $cd->setCancelled(true);
        $refundedCount = $this->creditService->refundBookingsForCancelledCourseDate($cd);
        $this->courseDateRepository->save($cd);

        $data = json_decode($request->getContent(), true);
        $notifTitle = is_array($data) ? ($data['notificationTitle'] ?? null) : null;
        $notifMessage = is_array($data) ? ($data['notificationMessage'] ?? null) : null;

        $notificationCreated = false;
        if (is_string($notifTitle) && $notifTitle !== '' && is_string($notifMessage) && $notifMessage !== '') {
            $notification = new Notification();
            $notification->setAuthor($user);
            $notification->setTitle($notifTitle);
            $notification->setMessage($notifMessage);
            $notification->setPinnedUntil($cd->getDate()->setTime(23, 59, 59));

            $course = $cd->getCourse();
            if ($course !== null) {
                $notification->addCourse($course);
            }

            $this->notificationRepository->save($notification);
            $notificationCreated = true;
        }

        return $this->json([
            ...$this->normalizer->normalizeCourseDateForAdmin($cd),
            'refundedBookings' => $refundedCount,
            'notificationCreated' => $notificationCreated,
        ]);
    }

    #[Route('/course-dates/{id}/uncancel', name: 'uncancel', methods: ['POST'])]
    public function uncancel(string $id): JsonResponse
    {
        $cd = $this->courseDateRepository->find($id);
        if ($cd === null) {
            return $this->json(['error' => 'CourseDate not found'], Response::HTTP_NOT_FOUND);
        }

        $cd->setCancelled(false);
        $this->courseDateRepository->save($cd);

        return $this->json($this->normalizer->normalizeCourseDateForAdmin($cd));
    }

    /**
     * @return array{\DateTimeImmutable, \DateTimeImmutable}
     */
    private function resolveRange(Request $request): array
    {
        $week = $request->query->get('week');
        $month = $request->query->get('month');

        if ($week !== null && $week !== '') {
            $dt = new \DateTimeImmutable($week);
            $from = $dt->modify('monday this week');
            $to = $dt->modify('sunday this week');
        } elseif ($month !== null && $month !== '') {
            $from = new \DateTimeImmutable($month.'-01');
            $to = $from->modify('last day of this month');
        } else {
            $from = new \DateTimeImmutable('monday this week');
            $to = $from->modify('sunday this week');
        }

        return [$from, $to];
    }
}
