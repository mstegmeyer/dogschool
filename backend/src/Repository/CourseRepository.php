<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\Course;
use App\Entity\CourseDate;
use App\Entity\CreditTransaction;
use App\Enum\CreditTransactionType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
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

    /**
     * @return array<int, Course>
     */
    public function findPageForAdminList(
        int $page,
        int $limit,
        ?bool $archived = null,
        string $sortBy = 'dayOfWeek',
        string $sortDirection = 'ASC',
    ): array {
        $qb = $this->createAdminListQueryBuilder($archived)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        if ($sortBy === 'dayOfWeek') {
            $qb
                ->orderBy('course.dayOfWeek', $sortDirection)
                ->addOrderBy('course.startTime', $sortDirection);

            /** @var list<Course> $courses */
            $courses = $qb->getQuery()->getResult();

            return $courses;
        }

        /** @var list<Course> $courses */
        $courses = $qb
            ->orderBy('course.'.$sortBy, $sortDirection)
            ->addOrderBy('course.dayOfWeek', 'ASC')
            ->addOrderBy('course.startTime', 'ASC')
            ->getQuery()
            ->getResult();

        return $courses;
    }

    public function countForAdminList(?bool $archived = null): int
    {
        return (int) $this->createAdminListQueryBuilder($archived)
            ->select('COUNT(course.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array{removedCourseDates: int, refundedBookings: int}
     */
    public function archiveFromDate(Course $course, \DateTimeImmutable $removeFromDate): array
    {
        $em = $this->getEntityManager();
        $course->setArchived(true);

        /** @var list<CourseDate> $courseDates */
        $courseDates = $em->createQueryBuilder()
            ->select('cd')
            ->from(CourseDate::class, 'cd')
            ->andWhere('cd.course = :course')
            ->andWhere('cd.date >= :from')
            ->setParameter('course', $course)
            ->setParameter('from', $removeFromDate, Types::DATE_IMMUTABLE)
            ->addOrderBy('cd.date', 'ASC')
            ->addOrderBy('cd.startTime', 'ASC')
            ->getQuery()
            ->getResult();

        $removedCourseDates = 0;
        $refundedBookings = 0;
        $courseName = $course->getCourseType()?->getName() ?? 'Kurs';

        foreach ($courseDates as $courseDate) {
            /** @var list<CreditTransaction> $transactions */
            $transactions = $em->createQueryBuilder()
                ->select('ct')
                ->from(CreditTransaction::class, 'ct')
                ->andWhere('ct.courseDate = :courseDate')
                ->setParameter('courseDate', $courseDate)
                ->getQuery()
                ->getResult();

            foreach ($transactions as $transaction) {
                $transaction->setCourseDate(null);
            }

            foreach ($courseDate->getActiveBookings() as $booking) {
                $customer = $booking->getCustomer();
                if ($customer === null) {
                    continue;
                }

                $refund = new CreditTransaction();
                $refund->setCustomer($customer);
                $refund->setAmount(1);
                $refund->setType(CreditTransactionType::CANCELLATION);
                $refund->setDescription(sprintf(
                    'Kurs archiviert: %s am %s (%s)',
                    $courseName,
                    $courseDate->getDate()->format('Y-m-d'),
                    $booking->getDog()?->getName() ?? 'unbekannter Hund',
                ));
                $em->persist($refund);
                ++$refundedBookings;
            }

            foreach ($courseDate->getBookings()->toArray() as $booking) {
                if ($booking instanceof Booking) {
                    $em->remove($booking);
                }
            }

            $em->remove($courseDate);
            ++$removedCourseDates;
        }

        $em->persist($course);
        $em->flush();

        return [
            'removedCourseDates' => $removedCourseDates,
            'refundedBookings' => $refundedBookings,
        ];
    }

    /**
     * Apply the course's current weekday/time settings to all upcoming CourseDate rows.
     *
     * @return int number of updated rows
     */
    public function syncUpcomingCourseDates(Course $course, int $previousDayOfWeek, ?\DateTimeImmutable $now = null): int
    {
        $timezone = new \DateTimeZone(CourseDate::TIMEZONE);
        $reference = ($now ?? new \DateTimeImmutable('now', $timezone))->setTimezone($timezone);
        $today = $reference->setTime(0, 0);

        /** @var list<CourseDate> $courseDates */
        $courseDates = $this->getEntityManager()->createQueryBuilder()
            ->select('cd')
            ->from(CourseDate::class, 'cd')
            ->andWhere('cd.course = :course')
            ->andWhere('cd.date >= :from')
            ->setParameter('course', $course)
            ->setParameter('from', $today, Types::DATE_IMMUTABLE)
            ->addOrderBy('cd.date', 'ASC')
            ->addOrderBy('cd.startTime', 'ASC')
            ->getQuery()
            ->getResult();

        $upcomingDates = array_values(array_filter(
            $courseDates,
            static fn (CourseDate $courseDate): bool => $courseDate->startsAt() >= $reference,
        ));

        if ($upcomingDates === []) {
            return 0;
        }

        $updated = 0;
        $dayShift = $course->getDayOfWeek() - $previousDayOfWeek;
        $lastAssignedStart = null;

        foreach ($upcomingDates as $courseDate) {
            $candidateDate = $courseDate->getDate()->modify(sprintf('%+d days', $dayShift));
            $candidateStart = $this->createDateTimeForSchedule($candidateDate, $course->getStartTime(), $timezone);

            while ($candidateStart < $reference || ($lastAssignedStart !== null && $candidateStart <= $lastAssignedStart)) {
                $candidateDate = $candidateDate->modify('+1 week');
                $candidateStart = $candidateStart->modify('+1 week');
            }

            $courseDate->setDate($candidateDate);
            $courseDate->setStartTime($course->getStartTime());
            $courseDate->setEndTime($course->getEndTime());

            $lastAssignedStart = $candidateStart;
            ++$updated;
        }

        return $updated;
    }

    public function save(Course $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    private function createAdminListQueryBuilder(?bool $archived = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('course');

        if ($archived !== null) {
            $qb
                ->andWhere('course.archived = :archived')
                ->setParameter('archived', $archived);
        }

        return $qb;
    }

    private function createDateTimeForSchedule(
        \DateTimeImmutable $date,
        string $startTime,
        \DateTimeZone $timezone,
    ): \DateTimeImmutable {
        $dateTime = \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i',
            $date->format('Y-m-d').' '.$startTime,
            $timezone,
        );

        if ($dateTime === false) {
            throw new \LogicException('Invalid course date schedule.');
        }

        return $dateTime;
    }
}
