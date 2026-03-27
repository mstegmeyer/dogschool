<?php

declare(strict_types=1);

namespace App\Controller\Api\Customer;

use App\Dto\ContractRequestDto;
use App\Entity\Contract;
use App\Entity\Customer;
use App\Enum\ContractState;
use App\Repository\ContractRepository;
use App\Repository\DogRepository;
use App\Service\ApiNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/customer/contracts', name: 'api_customer_contracts_')]
#[IsGranted('ROLE_CUSTOMER')]
final class ContractController extends AbstractController
{
    public function __construct(
        private readonly ContractRepository $contractRepository,
        private readonly DogRepository $dogRepository,
        private readonly ApiNormalizer $normalizer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Customer $customer): JsonResponse
    {
        $contracts = $this->contractRepository->findByCustomer($customer);

        return $this->json(['items' => array_map(fn (Contract $c) => $this->normalizer->normalizeContract($c), $contracts)]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function request(
        Customer $customer,
        #[MapRequestPayload(acceptFormat: 'json')] ContractRequestDto $dto,
    ): JsonResponse {
        if ($dto->endDate !== null && $dto->endDate !== '') {
            return $this->json(['errors' => ['endDate' => 'Enddatum darf bei Vertragsanfragen nicht gesetzt werden.']], Response::HTTP_BAD_REQUEST);
        }

        if ($dto->startDate === null || $dto->startDate === '') {
            return $this->json(['errors' => ['startDate' => 'Startdatum ist erforderlich.']], Response::HTTP_BAD_REQUEST);
        }

        try {
            $startDate = new \DateTimeImmutable($dto->startDate);
        } catch (\Exception) {
            return $this->json(['errors' => ['startDate' => 'Ungültiges Startdatum.']], Response::HTTP_BAD_REQUEST);
        }

        if ($startDate->format('d') !== '01') {
            return $this->json(['errors' => ['startDate' => 'Startdatum muss der erste Tag eines Monats sein.']], Response::HTTP_BAD_REQUEST);
        }

        $dog = $this->dogRepository->findOneByIdAndCustomer($dto->dogId, $customer);
        if ($dog === null) {
            return $this->json(['errors' => ['dogId' => 'Invalid or not your dog']], Response::HTTP_BAD_REQUEST);
        }

        $contract = new Contract();
        $contract->setCustomer($customer);
        $contract->setDog($dog);
        $contract->setState(ContractState::REQUESTED);
        $contract->setStartDate($startDate);
        $contract->setEndDate(null);
        $contract->setPrice($dto->price ?? '0');
        $contract->setCoursesPerWeek($dto->coursesPerWeek ?? 0);

        $errors = $this->validator->validate($contract);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->normalizer->violationsToArray($errors)], Response::HTTP_BAD_REQUEST);
        }
        $this->contractRepository->save($contract);

        return $this->json($this->normalizer->normalizeContract($contract), Response::HTTP_CREATED);
    }
}
