<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Contract;
use App\Entity\Customer;
use App\Enum\ContractState;
use App\Enum\ContractType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    public function save(Contract $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
