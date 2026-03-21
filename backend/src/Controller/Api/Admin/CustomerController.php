<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Dto\CustomerUpdateDto;
use App\Entity\Customer;
use App\Repository\CustomerRepository;
use App\Service\ApiNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin/customers', name: 'api_admin_customers_')]
#[IsGranted('ROLE_ADMIN')]
final class CustomerController extends AbstractController
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly ApiNormalizer $normalizer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $customers = $this->customerRepository->findAllOrderByCreatedAt();

        return $this->json(['items' => array_map(fn (Customer $c) => $this->normalizer->normalizeCustomer($c), $customers)]);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $customer = $this->customerRepository->find($id);
        if ($customer === null) {
            return $this->json(['error' => 'Customer not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->normalizer->normalizeCustomer($customer));
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(
        string $id,
        #[MapRequestPayload(acceptFormat: 'json')] CustomerUpdateDto $dto,
    ): JsonResponse {
        $customer = $this->customerRepository->find($id);
        if ($customer === null) {
            return $this->json(['error' => 'Customer not found'], Response::HTTP_NOT_FOUND);
        }

        if ($dto->name !== null) {
            $customer->setName($dto->name);
        }
        if ($dto->email !== null) {
            $customer->setEmail($dto->email);
        }
        if ($dto->address !== null) {
            $addr = $customer->getAddress();
            if ($dto->address->street !== null) {
                $addr->setStreet($dto->address->street);
            }
            if ($dto->address->postalCode !== null) {
                $addr->setPostalCode($dto->address->postalCode);
            }
            if ($dto->address->city !== null) {
                $addr->setCity($dto->address->city);
            }
            if ($dto->address->country !== null) {
                $addr->setCountry($dto->address->country);
            }
        }
        if ($dto->bankAccount !== null) {
            $bank = $customer->getBankAccount();
            if ($dto->bankAccount->iban !== null) {
                $bank->setIban($dto->bankAccount->iban);
            }
            if ($dto->bankAccount->bic !== null) {
                $bank->setBic($dto->bankAccount->bic);
            }
            if ($dto->bankAccount->accountHolder !== null) {
                $bank->setAccountHolder($dto->bankAccount->accountHolder);
            }
        }

        $errors = $this->validator->validate($customer);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->normalizer->violationsToArray($errors)], Response::HTTP_BAD_REQUEST);
        }
        $this->customerRepository->save($customer);

        return $this->json($this->normalizer->normalizeCustomer($customer));
    }
}
