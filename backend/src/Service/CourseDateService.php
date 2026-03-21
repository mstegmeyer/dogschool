<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Course;
use App\Entity\CourseDate;
use App\Enum\RecurrenceKind;
use App\Repository\CourseDateRepository;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;

final class CourseDateService
{
    public function __construct(
        private readonly CourseRepository $courseRepository,
        private readonly CourseDateRepository $courseDateRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * Generate CourseDate rows for all non-archived RECURRING courses
     * from $from until $until.
     *
     * @return int number of new rows created
     */
    public function generateDates(\DateTimeImmutable $from, \DateTimeImmutable $until): int
    {
        $courses = $this->courseRepository->findNonArchived();
        $created = 0;

        foreach ($courses as $course) {
            if ($course->getCourseType()?->getRecurrenceKind() !== RecurrenceKind::RECURRING) {
                continue;
            }

            $created += $this->generateForCourse($course, $from, $until);
        }

        $this->em->flush();

        return $created;
    }

    /**
     * Generate dates for a single course within a range.
     */
    public function generateForCourse(Course $course, \DateTimeImmutable $from, \DateTimeImmutable $until): int
    {
        $dayOfWeek = $course->getDayOfWeek(); // 1=Mon .. 7=Sun
        $phpDayName = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'][$dayOfWeek - 1];

        $current = $from;
        $currentDow = (int) $current->format('N');
        if ($currentDow !== $dayOfWeek) {
            $current = $current->modify("next {$phpDayName}");
        }

        $created = 0;

        while ($current <= $until) {
            if (!$this->courseDateRepository->existsForCourseAndDate($course, $current)) {
                $cd = new CourseDate();
                $cd->setCourse($course);
                $cd->setDate($current);
                $cd->setStartTime($course->getStartTime());
                $cd->setEndTime($course->getEndTime());

                $this->em->persist($cd);
                $created++;
            }

            $current = $current->modify('+1 week');
        }

        return $created;
    }
}
