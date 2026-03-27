<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Dto\PushDeviceUnregisterDto;
use App\Dto\PushDeviceUpsertDto;
use App\Entity\User;
use App\Service\ApiNormalizer;
use App\Service\PushDeviceManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/me/push-devices', name: 'api_admin_push_devices_')]
#[IsGranted('ROLE_ADMIN')]
final class PushDeviceController extends AbstractController
{
    public function __construct(
        private readonly PushDeviceManager $pushDeviceManager,
        private readonly ApiNormalizer $normalizer,
    ) {
    }

    #[Route('', name: 'upsert', methods: ['POST'])]
    public function upsert(
        User $user,
        #[MapRequestPayload(acceptFormat: 'json')] PushDeviceUpsertDto $dto,
    ): JsonResponse {
        $pushDevice = $this->pushDeviceManager->registerForUser($user, $dto);

        return $this->json($this->normalizer->normalizePushDevice($pushDevice));
    }

    #[Route('/unregister', name: 'unregister', methods: ['POST'])]
    public function unregister(
        User $user,
        #[MapRequestPayload(acceptFormat: 'json')] PushDeviceUnregisterDto $dto,
    ): JsonResponse {
        return $this->json([
            'success' => $this->pushDeviceManager->unregisterForUser($user, $dto->token),
        ]);
    }
}
