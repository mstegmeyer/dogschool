<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * Notifications relevant to the given course IDs: those linked to at least one
     * of the courses, plus global notifications (no courses assigned).
     *
     * Actively pinned notifications (pinnedUntil > now) are returned first.
     *
     * @param array<int, string> $courseIds
     *
     * @return array<int, Notification>
     */
    public function findForCustomerCourses(array $courseIds): array
    {
        $qb = $this->createQueryBuilder('n')
            ->leftJoin('n.courses', 'c')
            ->addSelect('c')
            ->leftJoin('c.courseType', 'ct')
            ->addSelect('ct');

        if ($courseIds !== []) {
            $qb->where('c.id IN (:ids) OR c.id IS NULL')
                ->setParameter('ids', $courseIds);
        } else {
            $qb->where('c.id IS NULL');
        }

        /** @var list<Notification> $result */
        $result = $qb
            ->addSelect('CASE WHEN n.pinnedUntil IS NOT NULL AND n.pinnedUntil > CURRENT_TIMESTAMP() THEN 1 ELSE 0 END AS HIDDEN pinSort')
            ->orderBy('pinSort', 'DESC')
            ->addOrderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * All notifications that belong to a specific course.
     *
     * @return array<int, Notification>
     */
    public function findByCourse(string $courseId): array
    {
        /** @var list<Notification> $result */
        $result = $this->createQueryBuilder('n')
            ->innerJoin('n.courses', 'c')
            ->where('c.id = :id')
            ->setParameter('id', $courseId)
            ->addSelect('CASE WHEN n.pinnedUntil IS NOT NULL AND n.pinnedUntil > CURRENT_TIMESTAMP() THEN 1 ELSE 0 END AS HIDDEN pinSort')
            ->orderBy('pinSort', 'DESC')
            ->addOrderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * @return array<int, Notification>
     */
    public function findRecent(int $limit = 100): array
    {
        /** @var list<Notification> $result */
        $result = $this->createRecentAdminListQueryBuilder()
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * @return array<int, Notification>
     */
    public function findPageForAdminList(int $page, int $limit): array
    {
        /** @var list<Notification> $notifications */
        $notifications = $this->createRecentAdminListQueryBuilder()
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $notifications;
    }

    public function countForAdminList(): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(Notification $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Notification $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    private function createRecentAdminListQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('n')
            ->leftJoin('n.courses', 'c')
            ->addSelect('c')
            ->leftJoin('c.courseType', 'ct')
            ->addSelect('ct')
            ->addSelect('CASE WHEN n.pinnedUntil IS NOT NULL AND n.pinnedUntil > CURRENT_TIMESTAMP() THEN 1 ELSE 0 END AS HIDDEN pinSort')
            ->orderBy('pinSort', 'DESC')
            ->addOrderBy('n.createdAt', 'DESC');
    }
}
