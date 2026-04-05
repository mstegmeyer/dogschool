<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\Dog;
use App\Entity\HotelBooking;
use App\Entity\Room;
use App\Enum\HotelBookingState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HotelBooking>
 */
class HotelBookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HotelBooking::class);
    }

    /**
     * @return array<int, HotelBooking>
     */
    public function findByCustomer(Customer $customer): array
    {
        /** @var list<HotelBooking> $bookings */
        $bookings = $this->createBaseQueryBuilder()
            ->andWhere('hotelBooking.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('hotelBooking.startAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $bookings;
    }

    /**
     * @return array<int, HotelBooking>
     */
    public function findPageForAdminList(
        int $page,
        int $limit,
        ?HotelBookingState $state = null,
        string $sortBy = 'createdAt',
        string $sortDirection = 'DESC',
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null,
    ): array {
        /** @var list<HotelBooking> $bookings */
        $bookings = $this->createAdminListQueryBuilder($state, $from, $to)
            ->orderBy('hotelBooking.'.$sortBy, $sortDirection)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $bookings;
    }

    public function countForAdminList(
        ?HotelBookingState $state = null,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null,
    ): int {
        return (int) $this->createAdminListQueryBuilder($state, $from, $to)
            ->select('COUNT(hotelBooking.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findOneByIdAndCustomer(string $id, Customer $customer): ?HotelBooking
    {
        $result = $this->createBaseQueryBuilder()
            ->andWhere('hotelBooking.id = :id')
            ->andWhere('hotelBooking.customer = :customer')
            ->setParameter('id', $id)
            ->setParameter('customer', $customer)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof HotelBooking ? $result : null;
    }

    public function findOverlappingActiveByDog(
        Dog $dog,
        \DateTimeImmutable $startAt,
        \DateTimeImmutable $endAt,
        ?string $excludeId = null,
    ): ?HotelBooking {
        $query = $this->createBaseQueryBuilder()
            ->andWhere('hotelBooking.dog = :dog')
            ->andWhere('hotelBooking.state IN (:states)')
            ->andWhere('hotelBooking.startAt < :endAt')
            ->andWhere('hotelBooking.endAt > :startAt')
            ->setParameter('dog', $dog)
            ->setParameter('states', [
                HotelBookingState::REQUESTED,
                HotelBookingState::PENDING_CUSTOMER_APPROVAL,
                HotelBookingState::CONFIRMED,
            ])
            ->setParameter('startAt', $startAt)
            ->setParameter('endAt', $endAt);

        if ($excludeId !== null && $excludeId !== '') {
            $query
                ->andWhere('hotelBooking.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        $result = $query
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof HotelBooking ? $result : null;
    }

    /**
     * @return array<int, HotelBooking>
     */
    public function findConfirmedAssignedOverlappingByRoom(
        Room $room,
        \DateTimeImmutable $startAt,
        \DateTimeImmutable $endAt,
        ?string $excludeId = null,
    ): array {
        $query = $this->createBaseQueryBuilder()
            ->andWhere('hotelBooking.room = :room')
            ->andWhere('hotelBooking.state = :state')
            ->andWhere('hotelBooking.startAt < :endAt')
            ->andWhere('hotelBooking.endAt > :startAt')
            ->setParameter('room', $room)
            ->setParameter('state', HotelBookingState::CONFIRMED)
            ->setParameter('startAt', $startAt)
            ->setParameter('endAt', $endAt)
            ->orderBy('hotelBooking.startAt', 'ASC');

        if ($excludeId !== null && $excludeId !== '') {
            $query
                ->andWhere('hotelBooking.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        /** @var list<HotelBooking> $bookings */
        $bookings = $query->getQuery()->getResult();

        return $bookings;
    }

    /**
     * @return array<int, HotelBooking>
     */
    public function findConfirmedAssignedByRange(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        /** @var list<HotelBooking> $bookings */
        $bookings = $this->createBaseQueryBuilder()
            ->andWhere('hotelBooking.room IS NOT NULL')
            ->andWhere('hotelBooking.state = :state')
            ->andWhere('hotelBooking.startAt < :to')
            ->andWhere('hotelBooking.endAt > :from')
            ->setParameter('state', HotelBookingState::CONFIRMED)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('room.name', 'ASC')
            ->addOrderBy('hotelBooking.startAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $bookings;
    }

    /**
     * @return array<int, HotelBooking>
     */
    public function findConfirmedAssignedArrivalsByRange(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        /** @var list<HotelBooking> $bookings */
        $bookings = $this->createBaseQueryBuilder()
            ->andWhere('hotelBooking.room IS NOT NULL')
            ->andWhere('hotelBooking.state = :state')
            ->andWhere('hotelBooking.startAt >= :from')
            ->andWhere('hotelBooking.startAt <= :to')
            ->setParameter('state', HotelBookingState::CONFIRMED)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('hotelBooking.startAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $bookings;
    }

    /**
     * @return array<int, HotelBooking>
     */
    public function findConfirmedAssignedDeparturesByRange(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        /** @var list<HotelBooking> $bookings */
        $bookings = $this->createBaseQueryBuilder()
            ->andWhere('hotelBooking.room IS NOT NULL')
            ->andWhere('hotelBooking.state = :state')
            ->andWhere('hotelBooking.endAt >= :from')
            ->andWhere('hotelBooking.endAt <= :to')
            ->setParameter('state', HotelBookingState::CONFIRMED)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('hotelBooking.endAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $bookings;
    }

    public function save(HotelBooking $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    private function createBaseQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('hotelBooking')
            ->leftJoin('hotelBooking.customer', 'customer')
            ->addSelect('customer')
            ->leftJoin('hotelBooking.dog', 'dog')
            ->addSelect('dog')
            ->leftJoin('hotelBooking.room', 'room')
            ->addSelect('room');
    }

    private function createAdminListQueryBuilder(
        ?HotelBookingState $state = null,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null,
    ): QueryBuilder {
        $query = $this->createBaseQueryBuilder();

        if ($state !== null) {
            $query
                ->andWhere('hotelBooking.state = :state')
                ->setParameter('state', $state);
        }

        if ($from !== null && $to !== null) {
            $query
                ->andWhere('hotelBooking.startAt < :to')
                ->andWhere('hotelBooking.endAt > :from')
                ->setParameter('from', $from)
                ->setParameter('to', $to);
        } elseif ($from !== null) {
            $query
                ->andWhere('hotelBooking.endAt >= :from')
                ->setParameter('from', $from);
        } elseif ($to !== null) {
            $query
                ->andWhere('hotelBooking.startAt <= :to')
                ->setParameter('to', $to);
        }

        return $query;
    }
}
