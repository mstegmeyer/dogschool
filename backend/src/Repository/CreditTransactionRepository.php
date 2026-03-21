<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Contract;
use App\Entity\CreditTransaction;
use App\Entity\Customer;
use App\Enum\CreditTransactionType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CreditTransaction>
 */
class CreditTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CreditTransaction::class);
    }

    public function getBalance(Customer $customer): int
    {
        return (int) $this->createQueryBuilder('ct')
            ->select('COALESCE(SUM(ct.amount), 0)')
            ->andWhere('ct.customer = :customer')
            ->setParameter('customer', $customer)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<int, CreditTransaction>
     */
    public function findByCustomer(Customer $customer, int $limit = 100): array
    {
        $rows = $this->createQueryBuilder('ct')
            ->andWhere('ct.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('ct.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        if (!is_iterable($rows)) {
            return [];
        }

        $transactions = [];
        foreach ($rows as $row) {
            if ($row instanceof CreditTransaction) {
                $transactions[] = $row;
            }
        }

        return $transactions;
    }

    /** Sum of all WEEKLY_GRANT amounts posted for this contract (positive integer). */
    public function sumWeeklyGrantAmountForContract(Contract $contract): int
    {
        return (int) $this->createQueryBuilder('ct')
            ->select('COALESCE(SUM(ct.amount), 0)')
            ->andWhere('ct.contract = :contract')
            ->andWhere('ct.type = :type')
            ->setParameter('contract', $contract)
            ->setParameter('type', CreditTransactionType::WEEKLY_GRANT)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function weeklyGrantExists(Contract $contract, string $weekRef): bool
    {
        return (int) $this->createQueryBuilder('ct')
            ->select('COUNT(ct.id)')
            ->andWhere('ct.contract = :contract')
            ->andWhere('ct.weekRef = :weekRef')
            ->andWhere('ct.type = :type')
            ->setParameter('contract', $contract)
            ->setParameter('weekRef', $weekRef)
            ->setParameter('type', CreditTransactionType::WEEKLY_GRANT)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function save(CreditTransaction $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
