<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Course;
use App\Entity\CourseDate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CourseDate>
 */
class CourseDateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseDate::class);
    }

    /**
     * @return array<int, CourseDate>
     */
    public function findByDateRange(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->createQueryBuilder('cd')
            ->andWhere('cd.date >= :from')
            ->andWhere('cd.date <= :to')
            ->setParameter('from', $from, Types::DATE_IMMUTABLE)
            ->setParameter('to', $to, Types::DATE_IMMUTABLE)
            ->addOrderBy('cd.date', 'ASC')
            ->addOrderBy('cd.startTime', 'ASC')
            ->getQuery()
            ->getResult();

        if (!is_iterable($rows)) {
            return [];
        }

        $dates = [];
        foreach ($rows as $row) {
            if ($row instanceof CourseDate) {
                $dates[] = $row;
            }
        }

        return $dates;
    }

    /**
     * @return array<int, CourseDate>
     */
    public function findByDateRangeAndCourse(\DateTimeImmutable $from, \DateTimeImmutable $to, Course $course): array
    {
        $rows = $this->createQueryBuilder('cd')
            ->andWhere('cd.date >= :from')
            ->andWhere('cd.date <= :to')
            ->andWhere('cd.course = :course')
            ->setParameter('from', $from, Types::DATE_IMMUTABLE)
            ->setParameter('to', $to, Types::DATE_IMMUTABLE)
            ->setParameter('course', $course)
            ->addOrderBy('cd.date', 'ASC')
            ->addOrderBy('cd.startTime', 'ASC')
            ->getQuery()
            ->getResult();

        if (!is_iterable($rows)) {
            return [];
        }

        $dates = [];
        foreach ($rows as $row) {
            if ($row instanceof CourseDate) {
                $dates[] = $row;
            }
        }

        return $dates;
    }

    /**
     * @return array<int, CourseDate>
     */
    public function findUpcomingForCourse(Course $course, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->createQueryBuilder('cd')
            ->andWhere('cd.course = :course')
            ->andWhere('(cd.date > :fromDate OR (cd.date = :fromDate AND cd.startTime >= :fromTime))')
            ->andWhere('cd.date <= :toDate')
            ->setParameter('course', $course)
            ->setParameter('fromDate', $from->setTime(0, 0, 0), Types::DATE_IMMUTABLE)
            ->setParameter('fromTime', $from->format('H:i'))
            ->setParameter('toDate', $to->setTime(23, 59, 59), Types::DATE_IMMUTABLE)
            ->addOrderBy('cd.date', 'ASC')
            ->addOrderBy('cd.startTime', 'ASC')
            ->getQuery()
            ->getResult();

        if (!is_iterable($rows)) {
            return [];
        }

        $dates = [];
        foreach ($rows as $row) {
            if ($row instanceof CourseDate) {
                $dates[] = $row;
            }
        }

        return $dates;
    }

    /**
     * @return array<int, CourseDate>
     */
    public function findFromDate(\DateTimeImmutable $from, int $days = 14): array
    {
        $to = $from->modify(sprintf('+%d days', $days))->setTime(23, 59, 59);

        return $this->findByDateRange($from, $to);
    }

    public function existsForCourseAndDate(Course $course, \DateTimeImmutable $date): bool
    {
        return (int) $this->createQueryBuilder('cd')
            ->select('COUNT(cd.id)')
            ->andWhere('cd.course = :course')
            ->andWhere('cd.date = :date')
            ->setParameter('course', $course)
            ->setParameter('date', $date, Types::DATE_IMMUTABLE)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * Returns the latest date already generated for a given course.
     */
    public function findLatestDateForCourse(Course $course): ?\DateTimeImmutable
    {
        $result = $this->createQueryBuilder('cd')
            ->andWhere('cd.course = :course')
            ->setParameter('course', $course)
            ->orderBy('cd.date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof CourseDate ? $result->getDate() : null;
    }

    public function save(CourseDate $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
