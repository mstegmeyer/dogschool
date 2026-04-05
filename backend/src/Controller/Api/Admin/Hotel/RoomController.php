<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Hotel;

use App\Dto\RoomUpsertDto;
use App\Entity\Room;
use App\Repository\RoomRepository;
use App\Service\ApiNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin/hotel/rooms', name: 'api_admin_hotel_rooms_')]
#[IsGranted('ROLE_ADMIN')]
final class RoomController extends AbstractController
{
    public function __construct(
        private readonly RoomRepository $roomRepository,
        private readonly ApiNormalizer $normalizer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[Route('/', name: 'list_slash', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $rooms = $this->roomRepository->findAllOrderByName();

        return $this->json([
            'items' => array_map(fn (Room $room) => $this->normalizer->normalizeRoom($room), $rooms),
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[Route('/', name: 'create_slash', methods: ['POST'])]
    public function create(
        #[MapRequestPayload(acceptFormat: 'json')] RoomUpsertDto $dto,
    ): JsonResponse {
        $room = new Room();
        $room->setName($dto->name);
        $room->setSquareMeters($dto->squareMeters ?? throw new \LogicException('Validated room size is required.'));

        $errors = $this->validator->validate($room);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->normalizer->violationsToArray($errors)], Response::HTTP_BAD_REQUEST);
        }

        $this->roomRepository->save($room);

        return $this->json($this->normalizer->normalizeRoom($room), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(
        string $id,
        #[MapRequestPayload(acceptFormat: 'json')] RoomUpsertDto $dto,
    ): JsonResponse {
        $room = $this->roomRepository->find($id);
        if (!$room instanceof Room) {
            return $this->json(['error' => 'Zimmer nicht gefunden'], Response::HTTP_NOT_FOUND);
        }

        $room->setName($dto->name);
        $room->setSquareMeters($dto->squareMeters ?? throw new \LogicException('Validated room size is required.'));

        $errors = $this->validator->validate($room);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->normalizer->violationsToArray($errors)], Response::HTTP_BAD_REQUEST);
        }

        $this->roomRepository->save($room);

        return $this->json($this->normalizer->normalizeRoom($room));
    }
}
