<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Contract;
use App\Entity\Customer;
use App\Enum\ContractState;
use App\Enum\ContractType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contract>
 */
class ContractRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contract::class);
    }

    /**
     * @return array<int, Contract>
     */
    public function findByCustomer(Customer $customer): array
    {
        return $this->findBy(['customer' => $customer], ['createdAt' => 'DESC']);
    }

    public function findOneByIdAndCustomer(string $id, Customer $customer): ?Contract
    {
        return $this->findOneBy(['id' => $id, 'customer' => $customer]);
    }

    /**
     * @return array<int, Contract>
     */
    public function findActivePerpetualByCustomer(Customer $customer): array
    {
        return $this->findBy([
            'customer' => $customer,
            'state' => ContractState::ACTIVE,
            'type' => ContractType::PERPETUAL,
        ], ['createdAt' => 'DESC']);
    }

    /**
     * @return array<int, Contract>
     */
    public function findCreditEligiblePerpetualByCustomer(Customer $customer): array
    {
        /** @var list<Contract> $contracts */
        $contracts = $this->createQueryBuilder('contract')
            ->andWhere('contract.customer = :customer')
            ->andWhere('contract.type = :type')
            ->andWhere('contract.state IN (:states)')
            ->setParameter('customer', $customer)
            ->setParameter('type', ContractType::PERPETUAL)
            ->setParameter('states', [ContractState::ACTIVE, ContractState::CANCELLED])
            ->orderBy('contract.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $contracts;
    }

    /**
     * @return array<int, Contract>
     */
    public function findAllCreditEligiblePerpetual(): array
    {
        /** @var list<Contract> $contracts */
        $contracts = $this->createQueryBuilder('contract')
            ->andWhere('contract.type = :type')
            ->andWhere('contract.state IN (:states)')
            ->setParameter('type', ContractType::PERPETUAL)
            ->setParameter('states', [ContractState::ACTIVE, ContractState::CANCELLED])
            ->orderBy('contract.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $contracts;
    }

    /**
     * @return array<int, Contract>
     */
    public function findAllOrderByCreatedAt(): array
    {
        return $this->findBy([], ['createdAt' => 'DESC']);
    }

    /**
     * @return array<int, Contract>
     * @param list<ContractState>|null $states
     */
    public function findPageForAdminList(
        int $page,
        int $limit,
        ?array $states = null,
        string $sortBy = 'createdAt',
        string $sortDirection = 'DESC',
    ): array {
        /** @var list<Contract> $contracts */
        $contracts = $this->createAdminListQueryBuilder($states)
            ->orderBy('contract.'.$sortBy, $sortDirection)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $contracts;
    }

    /**
     * @param list<ContractState>|null $states
     */
    public function countForAdminList(?array $states = null): int
    {
        return (int) $this->createAdminListQueryBuilder($states)
            ->select('COUNT(contract.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(Contract $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function customerHasActivatedContract(Customer $customer, ?string $excludeId = null): bool
    {
        $query = $this->createQueryBuilder('contract')
            ->select('COUNT(contract.id)')
            ->andWhere('contract.customer = :customer')
            ->andWhere('contract.state IN (:states)')
            ->setParameter('customer', $customer)
            ->setParameter('states', [ContractState::ACTIVE, ContractState::CANCELLED]);

        if ($excludeId !== null && $excludeId !== '') {
            $query
                ->andWhere('contract.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return (int) $query->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * @param list<ContractState>|null $states
     */
    private function createAdminListQueryBuilder(?array $states = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('contract');

        if ($states !== null && $states !== []) {
            $qb
                ->andWhere('contract.state IN (:states)')
                ->setParameter('states', $states);
        }

        return $qb;
    }
}
