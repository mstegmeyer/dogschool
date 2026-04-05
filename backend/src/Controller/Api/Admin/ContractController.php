<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Dto\ContractCancellationDto;
use App\Entity\Contract;
use App\Enum\ContractState;
use App\Repository\ContractRepository;
use App\Service\ApiNormalizer;
use App\Service\CreditService;
use App\Service\PricingEngine;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
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
        private readonly PricingEngine $pricingEngine,
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
    public function approve(Request $request, string $id): JsonResponse
    {
        $contract = $this->contractRepository->find($id);
        if ($contract === null) {
            return $this->json(['error' => 'Contract not found'], Response::HTTP_NOT_FOUND);
        }

        if (!in_array($contract->getState(), [ContractState::REQUESTED, ContractState::PENDING_CUSTOMER_APPROVAL], true)) {
            return $this->json(['error' => 'Only requested contracts can be approved'], Response::HTTP_BAD_REQUEST);
        }

        $payload = $this->parsePayload($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $quote = $this->pricingEngine->previewExistingContract($contract);
        $contract->setQuotedMonthlyPrice($quote['monthlyPrice']);
        $contract->setRegistrationFee($quote['registrationFee']);
        $contract->setPricingSnapshot($quote['snapshot']);
        $finalPrice = $payload['price'] ?? $quote['monthlyPrice'];
        $contract->setPrice($finalPrice);
        $contract->setAdminComment($payload['adminComment'] ?? $contract->getAdminComment());
        $contract->setState(
            PricingEngine::amountToCents($finalPrice) > PricingEngine::amountToCents($quote['monthlyPrice'])
                ? ContractState::PENDING_CUSTOMER_APPROVAL
                : ContractState::ACTIVE
        );
        $this->contractRepository->save($contract);

        return $this->json($this->normalizer->normalizeContract($contract));
    }

    #[Route('/{id}/decline', name: 'decline', methods: ['POST'])]
    public function decline(Request $request, string $id): JsonResponse
    {
        $contract = $this->contractRepository->find($id);
        if ($contract === null) {
            return $this->json(['error' => 'Contract not found'], Response::HTTP_NOT_FOUND);
        }

        if (!in_array($contract->getState(), [ContractState::REQUESTED, ContractState::PENDING_CUSTOMER_APPROVAL], true)) {
            return $this->json(['error' => 'Only requested contracts can be declined'], Response::HTTP_BAD_REQUEST);
        }

        $payload = $this->parsePayload($request, false);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $contract->setState(ContractState::DECLINED);
        $contract->setAdminComment($payload['adminComment'] ?? $contract->getAdminComment());
        $this->contractRepository->save($contract);

        return $this->json($this->normalizer->normalizeContract($contract));
    }

    #[Route('/{id}/cancel', name: 'cancel', methods: ['POST'])]
    public function cancel(
        string $id,
        #[MapRequestPayload(acceptFormat: 'json')] ContractCancellationDto $dto,
    ): JsonResponse {
        $contract = $this->contractRepository->find($id);
        if ($contract === null) {
            return $this->json(['error' => 'Contract not found'], Response::HTTP_NOT_FOUND);
        }

        if ($contract->getState() !== ContractState::ACTIVE) {
            return $this->json(['error' => 'Only active contracts can be cancelled'], Response::HTTP_BAD_REQUEST);
        }

        if ($dto->endDate === null || $dto->endDate === '') {
            return $this->json(['errors' => ['endDate' => 'Enddatum ist erforderlich.']], Response::HTTP_BAD_REQUEST);
        }

        try {
            $endDate = new \DateTimeImmutable($dto->endDate);
        } catch (\Exception) {
            return $this->json(['errors' => ['endDate' => 'Ungültiges Enddatum.']], Response::HTTP_BAD_REQUEST);
        }

        $startDate = $contract->getStartDate();
        if ($startDate !== null && $endDate < $startDate) {
            return $this->json(['errors' => ['endDate' => 'Enddatum darf nicht vor dem Vertragsbeginn liegen.']], Response::HTTP_BAD_REQUEST);
        }

        if ($endDate->format('Y-m-t') !== $endDate->format('Y-m-d')) {
            return $this->json(['errors' => ['endDate' => 'Enddatum muss der letzte Tag eines Monats sein.']], Response::HTTP_BAD_REQUEST);
        }

        $contract->setEndDate($endDate);

        $contract->setState(ContractState::CANCELLED);
        $this->contractRepository->save($contract);
        $this->creditService->applyContractCancellationCredits($contract);

        return $this->json($this->normalizer->normalizeContract($contract));
    }

    /**
     * @return array{price?: string, adminComment?: string}|JsonResponse
     */
    private function parsePayload(Request $request, bool $allowPrice = true): array|JsonResponse
    {
        if (($request->getContent() ?: '') === '') {
            return [];
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'Ungültiger Request Body.'], Response::HTTP_BAD_REQUEST);
        }

        $result = [];
        if ($allowPrice && array_key_exists('price', $payload)) {
            if (!is_scalar($payload['price']) || !preg_match('/^-?\d+(?:[.,]\d{1,2})?$/', (string) $payload['price'])) {
                return $this->json(['errors' => ['price' => 'Bitte einen gültigen Preis angeben.']], Response::HTTP_BAD_REQUEST);
            }

            $result['price'] = number_format((float) str_replace(',', '.', (string) $payload['price']), 2, '.', '');
        }

        if (array_key_exists('adminComment', $payload) && $payload['adminComment'] !== null && !is_string($payload['adminComment'])) {
            return $this->json(['errors' => ['adminComment' => 'Der Kommentar ist ungültig.']], Response::HTTP_BAD_REQUEST);
        }

        if (array_key_exists('adminComment', $payload)) {
            $result['adminComment'] = $payload['adminComment'];
        }

        return $result;
    }
}
