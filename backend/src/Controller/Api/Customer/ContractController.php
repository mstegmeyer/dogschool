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
        $dog = $this->dogRepository->findOneByIdAndCustomer($dto->dogId, $customer);
        if ($dog === null) {
            return $this->json(['errors' => ['dogId' => 'Invalid or not your dog']], Response::HTTP_BAD_REQUEST);
        }

        $contract = new Contract();
        $contract->setCustomer($customer);
        $contract->setDog($dog);
        $contract->setState(ContractState::REQUESTED);
        $contract->setStartDate(new \DateTimeImmutable($dto->startDate ?? 'now'));
        $contract->setEndDate(new \DateTimeImmutable($dto->endDate ?? '+1 year'));
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
