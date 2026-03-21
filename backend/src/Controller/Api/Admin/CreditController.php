<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Entity\CreditTransaction;
use App\Enum\CreditTransactionType;
use App\Repository\CreditTransactionRepository;
use App\Repository\CustomerRepository;
use App\Service\ApiNormalizer;
use App\Service\CreditService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/credits', name: 'api_admin_credits_')]
#[IsGranted('ROLE_ADMIN')]
final class CreditController extends AbstractController
{
    public function __construct(
        private readonly CreditService $creditService,
        private readonly CreditTransactionRepository $creditTransactionRepository,
        private readonly CustomerRepository $customerRepository,
        private readonly ApiNormalizer $normalizer,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $customerId = $request->query->get('customerId');
        if (!is_string($customerId) || $customerId === '') {
            return $this->json(['error' => 'customerId query parameter is required'], Response::HTTP_BAD_REQUEST);
        }

        $customer = $this->customerRepository->find($customerId);
        if ($customer === null) {
            return $this->json(['error' => 'Customer not found'], Response::HTTP_NOT_FOUND);
        }

        $history = $this->creditService->getHistory($customer);
        $balance = $this->creditService->getBalance($customer);

        return $this->json([
            'customerId' => $customer->getId(),
            'balance' => $balance,
            'items' => array_map(fn (CreditTransaction $tx) => $this->normalizer->normalizeCreditTransaction($tx), $history),
        ]);
    }

    #[Route('/adjust', name: 'adjust', methods: ['POST'])]
    public function adjust(Request $request): JsonResponse
    {
        $raw = json_decode($request->getContent(), true);
        if (!is_array($raw)) {
            return $this->json(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $customerId = $raw['customerId'] ?? null;
        $amount = $raw['amount'] ?? null;
        $description = $raw['description'] ?? null;

        if (!is_string($customerId) || $customerId === '') {
            return $this->json(['error' => 'customerId (string) is required'], Response::HTTP_BAD_REQUEST);
        }
        if (!is_int($amount)) {
            return $this->json(['error' => 'amount (integer) is required'], Response::HTTP_BAD_REQUEST);
        }
        if (!is_string($description) || $description === '') {
            return $this->json(['error' => 'description (non-empty string) is required'], Response::HTTP_BAD_REQUEST);
        }

        $customer = $this->customerRepository->find($customerId);
        if ($customer === null) {
            return $this->json(['error' => 'Customer not found'], Response::HTTP_NOT_FOUND);
        }

        $tx = new CreditTransaction();
        $tx->setCustomer($customer);
        $tx->setAmount($amount);
        $tx->setType(CreditTransactionType::MANUAL_ADJUSTMENT);
        $tx->setDescription($description);

        $this->creditTransactionRepository->save($tx);

        return $this->json($this->normalizer->normalizeCreditTransaction($tx), Response::HTTP_CREATED);
    }
}
