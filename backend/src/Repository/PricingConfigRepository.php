<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PricingConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PricingConfig>
 */
class PricingConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PricingConfig::class);
    }

    public function findCurrent(): ?PricingConfig
    {
        return $this->findOneBy([], ['createdAt' => 'DESC']);
    }

    public function save(PricingConfig $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
