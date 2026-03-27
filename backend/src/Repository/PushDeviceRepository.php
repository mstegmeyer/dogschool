<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\Notification;
use App\Entity\PushDevice;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PushDevice>
 */
class PushDeviceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PushDevice::class);
    }

    public function findOneByToken(string $token): ?PushDevice
    {
        return $this->findOneBy(['token' => $token]);
    }

    public function findOwnedByCustomerAndToken(Customer $customer, string $token): ?PushDevice
    {
        return $this->findOneBy(['customer' => $customer, 'token' => $token]);
    }

    public function findOwnedByUserAndToken(User $user, string $token): ?PushDevice
    {
        return $this->findOneBy(['user' => $user, 'token' => $token]);
    }

    /**
     * @return list<PushDevice>
     */
    public function findCustomerTargetsForNotification(Notification $notification): array
    {
        $qb = $this->createQueryBuilder('pd')
            ->innerJoin('pd.customer', 'customer');

        if ($notification->isGlobal()) {
            /** @var list<PushDevice> $result */
            $result = $qb
                ->orderBy('pd.updatedAt', 'DESC')
                ->getQuery()
                ->getResult();

            return $result;
        }

        /** @var list<PushDevice> $result */
        $result = $qb
            ->innerJoin('customer.subscribedCourses', 'course')
            ->innerJoin('course.notifications', 'notification')
            ->where('notification.id = :notificationId')
            ->setParameter('notificationId', $notification->getId())
            ->groupBy('pd.id')
            ->orderBy('pd.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function save(PushDevice $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PushDevice $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
