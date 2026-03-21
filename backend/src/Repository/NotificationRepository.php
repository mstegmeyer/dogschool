<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

        return $qb
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * All notifications that belong to a specific course.
     *
     * @return array<int, Notification>
     */
    public function findByCourse(string $courseId): array
    {
        return $this->createQueryBuilder('n')
            ->innerJoin('n.courses', 'c')
            ->where('c.id = :id')
            ->setParameter('id', $courseId)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, Notification>
     */
    public function findRecent(int $limit = 100): array
    {
        return $this->createQueryBuilder('n')
            ->leftJoin('n.courses', 'c')
            ->addSelect('c')
            ->leftJoin('c.courseType', 'ct')
            ->addSelect('ct')
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
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
}
