<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Dto\CoursePayloadDto;
use App\Entity\Course;
use App\Entity\CourseDate;
use App\Entity\CourseType;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Repository\CourseTypeRepository;
use App\Repository\UserRepository;
use App\Service\ApiNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin/courses', name: 'api_admin_courses_')]
#[IsGranted('ROLE_ADMIN')]
final class CourseController extends AbstractController
{
    public function __construct(
        private readonly CourseRepository $courseRepository,
        private readonly CourseTypeRepository $courseTypeRepository,
        private readonly UserRepository $userRepository,
        private readonly ApiNormalizer $normalizer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $archived = $request->query->get('archived');
        $archivedFilter = $archived !== null && $archived !== '' ? filter_var($archived, FILTER_VALIDATE_BOOLEAN) : null;
        $hasPaginatedRequest = $request->query->has('page')
            || $request->query->has('limit');

        if ($hasPaginatedRequest) {
            $page = max(1, $request->query->getInt('page', 1));
            $limit = min(100, max(1, $request->query->getInt('limit', 20)));
            $sortBy = match ($request->query->get('sort')) {
                'archived' => 'archived',
                default => 'dayOfWeek',
            };
            $sortDirection = strtolower((string) $request->query->get('direction', 'asc')) === 'desc'
                ? 'DESC'
                : 'ASC';
            $total = $this->courseRepository->countForAdminList($archivedFilter);
            $courses = $this->courseRepository->findPageForAdminList($page, $limit, $archivedFilter, $sortBy, $sortDirection);

            return $this->json([
                'items' => array_map(fn (Course $c) => $this->normalizer->normalizeCourse($c), $courses),
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => max(1, (int) ceil($total / $limit)),
                ],
            ]);
        }

        $courses = $this->courseRepository->findAllWithArchivedFilter($archivedFilter);

        return $this->json(['items' => array_map(fn (Course $c) => $this->normalizer->normalizeCourse($c), $courses)]);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $course = $this->courseRepository->find($id);
        if ($course === null) {
            return $this->json(['error' => 'Course not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->normalizer->normalizeCourse($course));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload(acceptFormat: 'json')] CoursePayloadDto $dto,
    ): JsonResponse {
        if ($dto->typeCode === '') {
            return $this->json(['errors' => ['typeCode' => 'Course type code is required.']], Response::HTTP_BAD_REQUEST);
        }
        $courseType = $this->courseTypeRepository->findByCode($dto->typeCode);
        if ($courseType === null) {
            return $this->json(['errors' => ['typeCode' => 'Unknown course type code.']], Response::HTTP_BAD_REQUEST);
        }
        $trainer = $this->resolveTrainer($dto->trainerId);
        if ($trainer === false) {
            return $this->json(['errors' => ['trainerId' => 'Unknown trainer.']], Response::HTTP_BAD_REQUEST);
        }
        $course = new Course();
        $this->applyPayload($course, $dto, $courseType, true, $trainer);
        $course->computeDurationMinutes();

        $errors = $this->validator->validate($course);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->normalizer->violationsToArray($errors)], Response::HTTP_BAD_REQUEST);
        }
        $this->courseRepository->save($course);

        return $this->json($this->normalizer->normalizeCourse($course), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(
        string $id,
        Request $request,
        #[MapRequestPayload(acceptFormat: 'json')] CoursePayloadDto $dto,
    ): JsonResponse {
        $course = $this->courseRepository->find($id);
        if ($course === null) {
            return $this->json(['error' => 'Course not found'], Response::HTTP_NOT_FOUND);
        }

        $previousDayOfWeek = $course->getDayOfWeek();
        $previousStartTime = $course->getStartTime();
        $previousEndTime = $course->getEndTime();
        $previousTrainer = $course->getTrainer();

        $courseType = null;
        if ($dto->typeCode !== '') {
            $courseType = $this->courseTypeRepository->findByCode($dto->typeCode);
            if ($courseType === null) {
                return $this->json(['errors' => ['typeCode' => 'Unknown course type code.']], Response::HTTP_BAD_REQUEST);
            }
        }
        $applyTrainer = $this->requestContainsKey($request, 'trainerId');
        $trainer = null;
        if ($applyTrainer) {
            $trainer = $this->resolveTrainer($dto->trainerId);
            if ($trainer === false) {
                return $this->json(['errors' => ['trainerId' => 'Unknown trainer.']], Response::HTTP_BAD_REQUEST);
            }
        }
        $this->applyPayload($course, $dto, $courseType, $applyTrainer, $trainer);
        $course->computeDurationMinutes();

        $errors = $this->validator->validate($course);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->normalizer->violationsToArray($errors)], Response::HTTP_BAD_REQUEST);
        }

        $scheduleChanged = $previousDayOfWeek !== $course->getDayOfWeek()
            || $previousStartTime !== $course->getStartTime()
            || $previousEndTime !== $course->getEndTime();
        if ($scheduleChanged) {
            $this->courseRepository->syncUpcomingCourseDates($course, $previousDayOfWeek);
        }
        if (($previousTrainer?->getId() ?? null) !== ($course->getTrainer()?->getId() ?? null)) {
            $this->courseRepository->syncUpcomingCourseDateTrainerDefaults($course, $previousTrainer);
        }

        $this->courseRepository->save($course);

        return $this->json($this->normalizer->normalizeCourse($course));
    }

    #[Route('/{id}/archive', name: 'archive', methods: ['POST'])]
    public function archive(string $id, Request $request): JsonResponse
    {
        $course = $this->courseRepository->find($id);
        if ($course === null) {
            return $this->json(['error' => 'Course not found'], Response::HTTP_NOT_FOUND);
        }

        $payload = json_decode($request->getContent(), true);
        $removeFromDateRaw = is_array($payload) ? ($payload['removeFromDate'] ?? null) : null;
        if (!is_string($removeFromDateRaw) || $removeFromDateRaw === '') {
            return $this->json(['error' => 'removeFromDate is required (YYYY-MM-DD).'], Response::HTTP_BAD_REQUEST);
        }

        $timezone = new \DateTimeZone(CourseDate::TIMEZONE);
        $removeFromDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $removeFromDateRaw, $timezone);
        if ($removeFromDate === false || $removeFromDate->format('Y-m-d') !== $removeFromDateRaw) {
            return $this->json(['error' => 'removeFromDate must be a valid date in YYYY-MM-DD format.'], Response::HTTP_BAD_REQUEST);
        }

        $today = new \DateTimeImmutable('today', $timezone);
        if ($removeFromDate < $today) {
            return $this->json(['error' => 'removeFromDate cannot be in the past.'], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->courseRepository->archiveFromDate($course, $removeFromDate);

        return $this->json([
            ...$this->normalizer->normalizeCourse($course),
            'removeFromDate' => $removeFromDate->format('Y-m-d'),
            'removedCourseDates' => $result['removedCourseDates'],
            'refundedBookings' => $result['refundedBookings'],
        ]);
    }

    #[Route('/{id}/unarchive', name: 'unarchive', methods: ['POST'])]
    public function unarchive(string $id): JsonResponse
    {
        $course = $this->courseRepository->find($id);
        if ($course === null) {
            return $this->json(['error' => 'Course not found'], Response::HTTP_NOT_FOUND);
        }
        $course->setArchived(false);
        $this->courseRepository->save($course);

        return $this->json($this->normalizer->normalizeCourse($course));
    }

    private function applyPayload(
        Course $course,
        CoursePayloadDto $dto,
        ?CourseType $courseType,
        bool $applyTrainer,
        User|false|null $trainer,
    ): void {
        $course->setDayOfWeek($dto->dayOfWeek);
        $course->setStartTime($dto->startTime);
        $course->setEndTime($dto->endTime);
        if ($courseType !== null) {
            $course->setCourseType($courseType);
        }
        $course->setLevel($dto->level);
        if ($applyTrainer && $trainer !== false) {
            $course->setTrainer($trainer);
        }
        if ($dto->comment !== null) {
            $course->setComment($dto->comment === '' ? null : $dto->comment);
        }
        if ($dto->archived !== null) {
            $course->setArchived($dto->archived);
        }
    }

    private function requestContainsKey(Request $request, string $key): bool
    {
        $data = json_decode($request->getContent(), true);

        return is_array($data) && array_key_exists($key, $data);
    }

    private function resolveTrainer(?string $trainerId): User|false|null
    {
        if ($trainerId === null || $trainerId === '') {
            return null;
        }

        return $this->userRepository->find($trainerId) ?? false;
    }
}
