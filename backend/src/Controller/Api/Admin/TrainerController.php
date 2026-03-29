<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ApiNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/trainers', name: 'api_admin_trainers_')]
#[IsGranted('ROLE_ADMIN')]
final class TrainerController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ApiNormalizer $normalizer,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $trainers = $this->userRepository->findAllOrderedByFullName();

        return $this->json([
            'items' => array_map(
                fn (User $trainer) => $this->normalizer->normalizeTrainer($trainer),
                $trainers,
            ),
        ]);
    }
}
