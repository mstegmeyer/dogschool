<?php

declare(strict_types=1);

namespace App\Controller\Api\Customer;

use App\Dto\ContractRequestDto;
use App\Dto\CustomerReviewDecisionDto;
use App\Entity\Contract;
use App\Entity\Customer;
use App\Enum\ContractState;
use App\Repository\ContractRepository;
use App\Repository\DogRepository;
use App\Service\ApiNormalizer;
use App\Service\PricingEngine;
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
        private readonly PricingEngine $pricingEngine,
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
        [$startDate, $dog, $validationError] = $this->validateContractRequest($customer, $dto);
        if ($validationError instanceof JsonResponse) {
            return $validationError;
        }
        if (!$startDate instanceof \DateTimeImmutable || $dog === null) {
            throw new \LogicException('Validated contract request is incomplete.');
        }

        $contract = new Contract();
        $contract->setCustomer($customer);
        $contract->setDog($dog);
        $contract->setState(ContractState::REQUESTED);
        $contract->setStartDate($startDate);
        $contract->setEndDate(null);
        $contract->setCoursesPerWeek($dto->coursesPerWeek ?? 0);
        $contract->setCustomerComment($dto->customerComment);
        $this->applyQuotedPrice($contract, $this->pricingEngine->previewContract($customer, $contract->getCoursesPerWeek()));

        $errors = $this->validator->validate($contract);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->normalizer->violationsToArray($errors)], Response::HTTP_BAD_REQUEST);
        }
        $this->contractRepository->save($contract);

        return $this->json($this->normalizer->normalizeContract($contract), Response::HTTP_CREATED);
    }

    #[Route('/preview', name: 'preview', methods: ['POST'])]
    public function preview(
        Customer $customer,
        #[MapRequestPayload(acceptFormat: 'json')] ContractRequestDto $dto,
    ): JsonResponse {
        [, , $validationError] = $this->validateContractRequest($customer, $dto);
        if ($validationError instanceof JsonResponse) {
            return $validationError;
        }

        return $this->json($this->pricingEngine->previewContract($customer, $dto->coursesPerWeek ?? 0));
    }

    #[Route('/{id}/accept-price', name: 'accept_price', methods: ['POST'])]
    public function acceptPrice(Customer $customer, string $id): JsonResponse
    {
        $contract = $this->contractRepository->findOneByIdAndCustomer($id, $customer);
        if (!$contract instanceof Contract) {
            return $this->json(['error' => 'Vertrag nicht gefunden'], Response::HTTP_NOT_FOUND);
        }

        if ($contract->getState() !== ContractState::PENDING_CUSTOMER_APPROVAL) {
            return $this->json(['error' => 'Nur Verträge mit Preisprüfung können angenommen werden'], Response::HTTP_BAD_REQUEST);
        }

        $contract->setState(ContractState::ACTIVE);
        $this->contractRepository->save($contract);

        return $this->json($this->normalizer->normalizeContract($contract));
    }

    #[Route('/{id}/decline-price', name: 'decline_price', methods: ['POST'])]
    public function declinePrice(Customer $customer, string $id): JsonResponse
    {
        $contract = $this->contractRepository->findOneByIdAndCustomer($id, $customer);
        if (!$contract instanceof Contract) {
            return $this->json(['error' => 'Vertrag nicht gefunden'], Response::HTTP_NOT_FOUND);
        }

        if ($contract->getState() !== ContractState::PENDING_CUSTOMER_APPROVAL) {
            return $this->json(['error' => 'Nur Verträge mit Preisprüfung können abgelehnt werden'], Response::HTTP_BAD_REQUEST);
        }

        $contract->setState(ContractState::DECLINED);
        $this->contractRepository->save($contract);

        return $this->json($this->normalizer->normalizeContract($contract));
    }

    #[Route('/{id}/resubmit', name: 'resubmit', methods: ['POST'])]
    public function resubmit(
        Customer $customer,
        string $id,
        #[MapRequestPayload(acceptFormat: 'json')] CustomerReviewDecisionDto $dto,
    ): JsonResponse {
        $contract = $this->contractRepository->findOneByIdAndCustomer($id, $customer);
        if (!$contract instanceof Contract) {
            return $this->json(['error' => 'Vertrag nicht gefunden'], Response::HTTP_NOT_FOUND);
        }

        if ($contract->getState() !== ContractState::PENDING_CUSTOMER_APPROVAL) {
            return $this->json(['error' => 'Nur Verträge mit Preisprüfung können erneut eingereicht werden'], Response::HTTP_BAD_REQUEST);
        }

        $contract->setState(ContractState::REQUESTED);
        $contract->setCustomerComment($dto->customerComment);
        $contract->setAdminComment(null);
        $this->applyQuotedPrice($contract, $this->pricingEngine->previewExistingContract($contract));
        $this->contractRepository->save($contract);

        return $this->json($this->normalizer->normalizeContract($contract));
    }

    /**
     * @return array{0: \DateTimeImmutable, 1: \App\Entity\Dog, 2: null}|array{0: null, 1: null, 2: JsonResponse}
     */
    private function validateContractRequest(Customer $customer, ContractRequestDto $dto): array
    {
        if ($dto->price !== null && trim($dto->price) !== '') {
            return [null, null, $this->json(['errors' => ['price' => 'Der Preis wird serverseitig berechnet und darf nicht gesendet werden.']], Response::HTTP_BAD_REQUEST)];
        }

        if ($dto->endDate !== null && $dto->endDate !== '') {
            return [null, null, $this->json(['errors' => ['endDate' => 'Enddatum darf bei Vertragsanfragen nicht gesetzt werden.']], Response::HTTP_BAD_REQUEST)];
        }

        if ($dto->startDate === null || $dto->startDate === '') {
            return [null, null, $this->json(['errors' => ['startDate' => 'Startdatum ist erforderlich.']], Response::HTTP_BAD_REQUEST)];
        }

        try {
            $startDate = new \DateTimeImmutable($dto->startDate);
        } catch (\Exception) {
            return [null, null, $this->json(['errors' => ['startDate' => 'Ungültiges Startdatum.']], Response::HTTP_BAD_REQUEST)];
        }

        if ($startDate->format('d') !== '01') {
            return [null, null, $this->json(['errors' => ['startDate' => 'Startdatum muss der erste Tag eines Monats sein.']], Response::HTTP_BAD_REQUEST)];
        }

        if ($dto->coursesPerWeek === null || $dto->coursesPerWeek < 1 || $dto->coursesPerWeek > 7) {
            return [null, null, $this->json(['errors' => ['coursesPerWeek' => 'Kurse pro Woche müssen zwischen 1 und 7 liegen.']], Response::HTTP_BAD_REQUEST)];
        }

        $dog = $this->dogRepository->findOneByIdAndCustomer($dto->dogId, $customer);
        if ($dog === null) {
            return [null, null, $this->json(['errors' => ['dogId' => 'Ungültiger Hund oder gehört nicht zu deinem Konto.']], Response::HTTP_BAD_REQUEST)];
        }

        return [$startDate, $dog, null];
    }

    /**
     * @param array{
     *   monthlyPrice: string,
     *   registrationFee: string,
     *   snapshot: array<string, mixed>
     * } $quote
     */
    private function applyQuotedPrice(Contract $contract, array $quote): void
    {
        $contract->setPrice($quote['monthlyPrice']);
        $contract->setQuotedMonthlyPrice($quote['monthlyPrice']);
        $contract->setRegistrationFee($quote['registrationFee']);
        $contract->setPricingSnapshot($quote['snapshot']);
    }
}
