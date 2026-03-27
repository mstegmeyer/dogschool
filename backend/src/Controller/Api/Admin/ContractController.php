<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Entity\Contract;
use App\Enum\ContractState;
use App\Repository\ContractRepository;
use App\Service\ApiNormalizer;
use App\Service\CreditService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/contracts', name: 'api_admin_contracts_')]
#[IsGranted('ROLE_ADMIN')]
final class ContractController extends AbstractController
{
    public function __construct(
        private readonly ContractRepository $contractRepository,
        private readonly ApiNormalizer $normalizer,
        private readonly CreditService $creditService,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $stateParam = $request->query->get('state');
        $state = is_string($stateParam) && $stateParam !== '' && $stateParam !== 'all'
            ? ContractState::tryFrom($stateParam)
            : null;
        $hasPaginatedRequest = $request->query->has('page')
            || $request->query->has('limit')
            || $state !== null;

        if ($hasPaginatedRequest) {
            $page = max(1, $request->query->getInt('page', 1));
            $limit = min(100, max(1, $request->query->getInt('limit', 20)));
            $sortBy = match ($request->query->get('sort')) {
                'state' => 'state',
                default => 'createdAt',
            };
            $sortDirection = strtolower((string) $request->query->get('direction', 'desc')) === 'asc'
                ? 'ASC'
                : 'DESC';
            $total = $this->contractRepository->countForAdminList($state);
            $contracts = $this->contractRepository->findPageForAdminList($page, $limit, $state, $sortBy, $sortDirection);

            return $this->json([
                'items' => array_map(fn (Contract $c) => $this->normalizer->normalizeContract($c), $contracts),
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => max(1, (int) ceil($total / $limit)),
                ],
            ]);
        }

        $contracts = $this->contractRepository->findAllOrderByCreatedAt();

        return $this->json(['items' => array_map(fn (Contract $c) => $this->normalizer->normalizeContract($c), $contracts)]);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $contract = $this->contractRepository->find($id);
        if ($contract === null) {
            return $this->json(['error' => 'Contract not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->normalizer->normalizeContract($contract));
    }

    #[Route('/{id}/approve', name: 'approve', methods: ['POST'])]
    public function approve(string $id): JsonResponse
    {
        return $this->setState($id, ContractState::ACTIVE);
    }

    #[Route('/{id}/decline', name: 'decline', methods: ['POST'])]
    public function decline(string $id): JsonResponse
    {
        return $this->setState($id, ContractState::DECLINED);
    }

    #[Route('/{id}/cancel', name: 'cancel', methods: ['POST'])]
    public function cancel(string $id): JsonResponse
    {
        return $this->setState($id, ContractState::CANCELLED);
    }

    private function setState(string $id, ContractState $state): JsonResponse
    {
        $contract = $this->contractRepository->find($id);
        if ($contract === null) {
            return $this->json(['error' => 'Contract not found'], Response::HTTP_NOT_FOUND);
        }
        $previous = $contract->getState();
        $contract->setState($state);
        $this->contractRepository->save($contract);

        if ($state === ContractState::CANCELLED && $previous === ContractState::ACTIVE) {
            $this->creditService->applyContractCancellationCredits($contract);
        }

        return $this->json($this->normalizer->normalizeContract($contract));
    }
}
