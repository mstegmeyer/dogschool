<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Dto\NotificationCreateDto;
use App\Dto\NotificationUpdateDto;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Repository\NotificationRepository;
use App\Service\ApiNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin/notifications', name: 'api_admin_notifications_')]
#[IsGranted('ROLE_ADMIN')]
final class NotificationController extends AbstractController
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly CourseRepository $courseRepository,
        private readonly ApiNormalizer $normalizer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $courseId = $request->query->get('courseId');
        $notifications = $courseId !== null && $courseId !== ''
            ? $this->notificationRepository->findByCourse($courseId)
            : $this->notificationRepository->findRecent(100);

        return $this->json(['items' => array_map(fn (Notification $n) => $this->normalizer->normalizeNotification($n), $notifications)]);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $notification = $this->notificationRepository->find($id);
        if ($notification === null) {
            return $this->json(['error' => 'Notification not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->normalizer->normalizeNotification($notification));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        User $user,
        #[MapRequestPayload(acceptFormat: 'json')] NotificationCreateDto $dto,
    ): JsonResponse {
        $notification = new Notification();
        $notification->setAuthor($user);
        $notification->setTitle($dto->title);
        $notification->setMessage($dto->message);

        foreach ($dto->courseIds as $courseId) {
            $course = $this->courseRepository->find($courseId);
            if ($course === null) {
                return $this->json(['errors' => ['courseIds' => "Course {$courseId} not found"]], Response::HTTP_BAD_REQUEST);
            }
            $notification->addCourse($course);
        }

        $errors = $this->validator->validate($notification);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->normalizer->violationsToArray($errors)], Response::HTTP_BAD_REQUEST);
        }
        $this->notificationRepository->save($notification);

        return $this->json($this->normalizer->normalizeNotification($notification), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(
        string $id,
        #[MapRequestPayload(acceptFormat: 'json')] NotificationUpdateDto $dto,
    ): JsonResponse {
        $notification = $this->notificationRepository->find($id);
        if ($notification === null) {
            return $this->json(['error' => 'Notification not found'], Response::HTTP_NOT_FOUND);
        }

        if ($dto->title !== null) {
            $notification->setTitle($dto->title);
        }
        if ($dto->message !== null) {
            $notification->setMessage($dto->message);
        }
        if ($dto->courseIds !== null) {
            foreach ($notification->getCourses()->toArray() as $oldCourse) {
                $notification->removeCourse($oldCourse);
            }
            foreach ($dto->courseIds as $courseId) {
                $course = $this->courseRepository->find($courseId);
                if ($course === null) {
                    return $this->json(['errors' => ['courseIds' => "Course {$courseId} not found"]], Response::HTTP_BAD_REQUEST);
                }
                $notification->addCourse($course);
            }
        }

        $errors = $this->validator->validate($notification);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->normalizer->violationsToArray($errors)], Response::HTTP_BAD_REQUEST);
        }
        $this->notificationRepository->save($notification);

        return $this->json($this->normalizer->normalizeNotification($notification));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $notification = $this->notificationRepository->find($id);
        if ($notification === null) {
            return $this->json(['error' => 'Notification not found'], Response::HTTP_NOT_FOUND);
        }
        $this->notificationRepository->remove($notification);

        return $this->json(['success' => true]);
    }
}
