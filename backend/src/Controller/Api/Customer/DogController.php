<?php

declare(strict_types=1);

namespace App\Controller\Api\Customer;

use App\Dto\DogCreateDto;
use App\Entity\Customer;
use App\Entity\Dog;
use App\Repository\DogRepository;
use App\Service\ApiNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/customer/dogs', name: 'api_customer_dogs_')]
#[IsGranted('ROLE_CUSTOMER')]
final class DogController extends AbstractController
{
    public function __construct(
        private readonly DogRepository $dogRepository,
        private readonly ApiNormalizer $normalizer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Customer $customer): JsonResponse
    {
        $dogs = $this->dogRepository->findByCustomer($customer);

        return $this->json(['items' => array_map(fn (Dog $d) => $this->normalizer->normalizeDog($d), $dogs)]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Customer $customer,
        #[MapRequestPayload(acceptFormat: 'json')] DogCreateDto $dto,
    ): JsonResponse {
        $dtoErrors = $this->validator->validate($dto);
        if (count($dtoErrors) > 0) {
            return $this->json(['errors' => $this->normalizer->violationsToArray($dtoErrors)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $dog = new Dog();
        $dog->setCustomer($customer);
        $dog->setName($dto->name);
        $dog->setColor($dto->color);
        $dog->setGender($dto->gender);
        $dog->setRace($dto->race);
        $dog->setShoulderHeightCm($dto->shoulderHeightCm ?? throw new \LogicException('Validated shoulder height is required.'));

        $errors = $this->validator->validate($dog);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->normalizer->violationsToArray($errors)], Response::HTTP_BAD_REQUEST);
        }
        $customer->addDog($dog);
        $this->dogRepository->save($dog);

        return $this->json($this->normalizer->normalizeDog($dog), Response::HTTP_CREATED);
    }
}
