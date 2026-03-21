<?php

declare(strict_types=1);

namespace App\Controller\Api\Customer;

use App\Dto\CustomerRegisterDto;
use App\Entity\Customer;
use App\Repository\CustomerRepository;
use App\Service\ApiNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/customer')]
final class RegisterController extends AbstractController
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly ApiNormalizer $normalizer,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/register', name: 'api_customer_register', methods: ['POST'])]
    public function register(
        #[MapRequestPayload(acceptFormat: 'json')] CustomerRegisterDto $dto,
    ): JsonResponse {
        if ($this->customerRepository->findByEmail($dto->email) !== null) {
            return $this->json(['errors' => ['email' => 'A customer with this email already exists.']], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $customer = new Customer();
        $customer->setEmail($dto->email);
        $customer->setName($dto->name ?? $dto->email);
        $customer->setPassword($this->passwordHasher->hashPassword($customer, $dto->password));

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

        return $this->json($this->normalizer->normalizeCustomer($customer), Response::HTTP_CREATED);
    }
}
