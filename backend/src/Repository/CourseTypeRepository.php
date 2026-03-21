<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CourseType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CourseType>
 */
class CourseTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseType::class);
    }

    public function findByCode(string $code): ?CourseType
    {
        return $this->findOneBy(['code' => $code]);
    }

    public function save(CourseType $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
