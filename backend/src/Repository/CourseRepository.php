<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Course;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Course>
 */
class CourseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Course::class);
    }

    /**
     * @return array<int, Course>
     */
    public function findByArchived(bool $archived): array
    {
        return $this->findBy(['archived' => $archived], ['dayOfWeek' => 'ASC', 'startTime' => 'ASC']);
    }

    /**
     * @return array<int, Course>
     */
    public function findAllWithArchivedFilter(?bool $archived): array
    {
        if ($archived === null) {
            return $this->findBy([], ['dayOfWeek' => 'ASC', 'startTime' => 'ASC']);
        }

        return $this->findByArchived($archived);
    }

    /**
     * @return array<int, Course>
     */
    public function findNonArchived(): array
    {
        return $this->findByArchived(false);
    }

    public function save(Course $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
