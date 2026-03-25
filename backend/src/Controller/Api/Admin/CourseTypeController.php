<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Dto\CourseTypeCreateDto;
use App\Dto\CourseTypeUpdateDto;
use App\Entity\CourseType;
use App\Enum\RecurrenceKind;
use App\Repository\CourseTypeRepository;
use App\Service\ApiNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin/course-types', name: 'api_admin_course_types_')]
#[IsGranted('ROLE_ADMIN')]
final class CourseTypeController extends AbstractController
{
    public function __construct(
        private readonly CourseTypeRepository $courseTypeRepository,
        private readonly ApiNormalizer $normalizer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $courseTypes = $this->courseTypeRepository->findBy([], ['name' => 'ASC']);

        return $this->json([
            'items' => array_map(
                fn (CourseType $ct) => $this->normalizer->normalizeCourseType($ct),
                $courseTypes,
            ),
        ]);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $courseType = $this->courseTypeRepository->find($id);
        if ($courseType === null) {
            return $this->json(['error' => 'Course type not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->normalizer->normalizeCourseType($courseType));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload(acceptFormat: 'json')] CourseTypeCreateDto $dto,
    ): JsonResponse {
        $courseType = new CourseType();
        $courseType->setCode($dto->code);
        $courseType->setName($dto->name);
        $courseType->setRecurrenceKind(RecurrenceKind::from($dto->recurrenceKind));

        $errors = $this->validator->validate($courseType);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->normalizer->violationsToArray($errors)], Response::HTTP_BAD_REQUEST);
        }
        $this->courseTypeRepository->save($courseType);

        return $this->json($this->normalizer->normalizeCourseType($courseType), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(
        string $id,
        #[MapRequestPayload(acceptFormat: 'json')] CourseTypeUpdateDto $dto,
    ): JsonResponse {
        $courseType = $this->courseTypeRepository->find($id);
        if ($courseType === null) {
            return $this->json(['error' => 'Course type not found'], Response::HTTP_NOT_FOUND);
        }

        if ($dto->code !== null) {
            $courseType->setCode($dto->code);
        }
        if ($dto->name !== null) {
            $courseType->setName($dto->name);
        }
        if ($dto->recurrenceKind !== null) {
            $courseType->setRecurrenceKind(RecurrenceKind::from($dto->recurrenceKind));
        }

        $errors = $this->validator->validate($courseType);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->normalizer->violationsToArray($errors)], Response::HTTP_BAD_REQUEST);
        }
        $this->courseTypeRepository->save($courseType);

        return $this->json($this->normalizer->normalizeCourseType($courseType));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $courseType = $this->courseTypeRepository->find($id);
        if ($courseType === null) {
            return $this->json(['error' => 'Course type not found'], Response::HTTP_NOT_FOUND);
        }
        $this->courseTypeRepository->remove($courseType);

        return $this->json(['success' => true]);
    }
}
