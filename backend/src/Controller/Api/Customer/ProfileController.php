<?php

declare(strict_types=1);

namespace App\Controller\Api\Customer;

use App\Dto\CustomerUpdateDto;
use App\Entity\Customer;
use App\Repository\CustomerRepository;
use App\Service\ApiNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/customer')]
#[IsGranted('ROLE_CUSTOMER')]
final class ProfileController extends AbstractController
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly ApiNormalizer $normalizer,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/me', name: 'api_customer_me', methods: ['GET'])]
    public function me(Customer $customer): JsonResponse
    {
        return $this->json($this->normalizer->normalizeCustomer($customer));
    }

    #[Route('/me', name: 'api_customer_me_update', methods: ['PUT', 'PATCH'])]
    public function updateMe(
        Customer $customer,
        #[MapRequestPayload(acceptFormat: 'json')] CustomerUpdateDto $dto,
    ): JsonResponse {
        if ($dto->name !== null) {
            $customer->setName($dto->name);
        }
        if ($dto->email !== null) {
            $customer->setEmail($dto->email);
        }
        if ($dto->password !== null && $dto->password !== '') {
            $customer->setPassword($this->passwordHasher->hashPassword($customer, $dto->password));
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
