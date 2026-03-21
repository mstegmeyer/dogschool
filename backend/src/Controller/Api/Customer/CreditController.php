<?php

declare(strict_types=1);

namespace App\Controller\Api\Customer;

use App\Entity\CreditTransaction;
use App\Entity\Customer;
use App\Service\ApiNormalizer;
use App\Service\CreditService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/customer/credits', name: 'api_customer_credits_')]
#[IsGranted('ROLE_CUSTOMER')]
final class CreditController extends AbstractController
{
    public function __construct(
        private readonly CreditService $creditService,
        private readonly ApiNormalizer $normalizer,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Customer $customer): JsonResponse
    {
        $history = $this->creditService->getHistory($customer);
        $balance = $this->creditService->getBalance($customer);
        $nextWeeklyGrants = $this->creditService->getNextWeeklyCreditHints($customer);

        return $this->json([
            'balance' => $balance,
            'nextWeeklyGrants' => $nextWeeklyGrants,
            'items' => array_map(fn (CreditTransaction $tx) => $this->normalizer->normalizeCreditTransaction($tx), $history),
        ]);
    }
}
