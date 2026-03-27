<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Customer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Customer>
 */
class CustomerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    public function findByEmail(string $email): ?Customer
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findOneByCalendarFeedToken(string $token): ?Customer
    {
        return $this->findOneBy(['calendarFeedToken' => $token]);
    }

    /**
     * @return array<int, Customer>
     */
    public function findAllOrderByCreatedAt(): array
    {
        return $this->findBy([], ['createdAt' => 'DESC']);
    }

    /**
     * @return array<int, Customer>
     */
    public function findPageForAdminList(
        int $page,
        int $limit,
        ?string $query = null,
        string $sortBy = 'createdAt',
        string $sortDirection = 'DESC',
    ): array
    {
        return $this->createAdminListQueryBuilder($query)
            ->orderBy('customer.'.$sortBy, $sortDirection)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countForAdminList(?string $query = null): int
    {
        return (int) $this->createAdminListQueryBuilder($query)
            ->select('COUNT(customer.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(Customer $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    private function createAdminListQueryBuilder(?string $query = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('customer');

        if ($query !== null && $query !== '') {
            $searchTerm = '%'.mb_strtolower($query).'%';

            $qb
                ->andWhere(
                    'LOWER(customer.name) LIKE :search
                    OR LOWER(customer.email) LIKE :search
                    OR LOWER(COALESCE(customer.address.city, \'\')) LIKE :search'
                )
                ->setParameter('search', $searchTerm);
        }

        return $qb;
    }
}
