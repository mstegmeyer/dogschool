<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\CourseDate;
use App\Entity\Customer;
use App\Entity\Dog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function findActiveByDogAndCourseDate(Dog $dog, CourseDate $courseDate): ?Booking
    {
        $result = $this->createQueryBuilder('b')
            ->andWhere('b.dog = :dog')
            ->andWhere('b.courseDate = :courseDate')
            ->andWhere('b.cancelledAt IS NULL')
            ->setParameter('dog', $dog)
            ->setParameter('courseDate', $courseDate)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof Booking ? $result : null;
    }

    /**
     * @return array<int, Booking>
     */
    public function findByCustomer(Customer $customer): array
    {
        $rows = $this->createQueryBuilder('b')
            ->andWhere('b.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        if (!is_iterable($rows)) {
            return [];
        }

        $bookings = [];
        foreach ($rows as $row) {
            if ($row instanceof Booking) {
                $bookings[] = $row;
            }
        }

        return $bookings;
    }

    /**
     * @return array<int, Booking>
     */
    public function findActiveByCustomer(Customer $customer): array
    {
        $rows = $this->createQueryBuilder('b')
            ->leftJoin('b.courseDate', 'courseDate')
            ->addSelect('courseDate')
            ->leftJoin('courseDate.course', 'course')
            ->addSelect('course')
            ->leftJoin('course.courseType', 'courseType')
            ->addSelect('courseType')
            ->leftJoin('b.dog', 'dog')
            ->addSelect('dog')
            ->andWhere('b.customer = :customer')
            ->andWhere('b.cancelledAt IS NULL')
            ->setParameter('customer', $customer)
            ->orderBy('courseDate.date', 'ASC')
            ->addOrderBy('courseDate.startTime', 'ASC')
            ->getQuery()
            ->getResult();

        if (!is_iterable($rows)) {
            return [];
        }

        $bookings = [];
        foreach ($rows as $row) {
            if ($row instanceof Booking) {
                $bookings[] = $row;
            }
        }

        return $bookings;
    }

    /**
     * @return array<int, Booking>
     */
    public function findActiveForCalendarFeed(Customer $customer): array
    {
        $rows = $this->createQueryBuilder('b')
            ->leftJoin('b.courseDate', 'courseDate')
            ->addSelect('courseDate')
            ->leftJoin('courseDate.course', 'course')
            ->addSelect('course')
            ->leftJoin('course.courseType', 'courseType')
            ->addSelect('courseType')
            ->leftJoin('b.dog', 'dog')
            ->addSelect('dog')
            ->andWhere('b.customer = :customer')
            ->andWhere('b.cancelledAt IS NULL')
            ->setParameter('customer', $customer)
            ->orderBy('courseDate.date', 'ASC')
            ->addOrderBy('courseDate.startTime', 'ASC')
            ->getQuery()
            ->getResult();

        if (!is_iterable($rows)) {
            return [];
        }

        $bookings = [];
        foreach ($rows as $row) {
            if ($row instanceof Booking) {
                $bookings[] = $row;
            }
        }

        return $bookings;
    }

    public function save(Booking $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
