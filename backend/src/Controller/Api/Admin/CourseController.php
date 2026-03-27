<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Dto\CoursePayloadDto;
use App\Entity\Course;
use App\Entity\CourseType;
use App\Repository\CourseRepository;
use App\Repository\CourseTypeRepository;
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
        $course = new Course();
        $this->applyPayload($course, $dto, $courseType);
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
        #[MapRequestPayload(acceptFormat: 'json')] CoursePayloadDto $dto,
    ): JsonResponse {
        $course = $this->courseRepository->find($id);
        if ($course === null) {
            return $this->json(['error' => 'Course not found'], Response::HTTP_NOT_FOUND);
        }

        $courseType = null;
        if ($dto->typeCode !== '') {
            $courseType = $this->courseTypeRepository->findByCode($dto->typeCode);
            if ($courseType === null) {
                return $this->json(['errors' => ['typeCode' => 'Unknown course type code.']], Response::HTTP_BAD_REQUEST);
            }
        }
        $this->applyPayload($course, $dto, $courseType);
        $course->computeDurationMinutes();

        $errors = $this->validator->validate($course);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->normalizer->violationsToArray($errors)], Response::HTTP_BAD_REQUEST);
        }
        $this->courseRepository->save($course);

        return $this->json($this->normalizer->normalizeCourse($course));
    }

    #[Route('/{id}/archive', name: 'archive', methods: ['POST'])]
    public function archive(string $id): JsonResponse
    {
        $course = $this->courseRepository->find($id);
        if ($course === null) {
            return $this->json(['error' => 'Course not found'], Response::HTTP_NOT_FOUND);
        }
        $course->setArchived(true);
        $this->courseRepository->save($course);

        return $this->json($this->normalizer->normalizeCourse($course));
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

    private function applyPayload(Course $course, CoursePayloadDto $dto, ?CourseType $courseType): void
    {
        $course->setDayOfWeek($dto->dayOfWeek);
        $course->setStartTime($dto->startTime);
        $course->setEndTime($dto->endTime);
        if ($courseType !== null) {
            $course->setCourseType($courseType);
        }
        $course->setLevel($dto->level);
        if ($dto->comment !== null) {
            $course->setComment($dto->comment === '' ? null : $dto->comment);
        }
        if ($dto->archived !== null) {
            $course->setArchived($dto->archived);
        }
    }
}
