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
    public function findAllOrderByCreatedAt(): array
    {
        return $this->findBy([], ['createdAt' => 'DESC']);
    }

    /**
     * @return array<int, Contract>
     */
    public function findPageForAdminList(
        int $page,
        int $limit,
        ?ContractState $state = null,
        string $sortBy = 'createdAt',
        string $sortDirection = 'DESC',
    ): array {
        return $this->createAdminListQueryBuilder($state)
            ->orderBy('contract.'.$sortBy, $sortDirection)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countForAdminList(?ContractState $state = null): int
    {
        return (int) $this->createAdminListQueryBuilder($state)
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

    private function createAdminListQueryBuilder(?ContractState $state = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('contract');

        if ($state !== null) {
            $qb
                ->andWhere('contract.state = :state')
                ->setParameter('state', $state);
        }

        return $qb;
    }
}
