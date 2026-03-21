<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\Dog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dog>
 */
class DogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dog::class);
    }

    /**
     * @return array<int, Dog>
     */
    public function findByCustomer(Customer $customer): array
    {
        return $this->findBy(['customer' => $customer], ['name' => 'ASC']);
    }

    public function findOneByIdAndCustomer(string $id, Customer $customer): ?Dog
    {
        return $this->findOneBy(['id' => $id, 'customer' => $customer]);
    }

    public function save(Dog $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
