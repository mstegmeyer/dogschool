<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Course;
use App\Entity\CourseType;
use App\Enum\RecurrenceKind;
use App\Repository\CourseDateRepository;
use App\Repository\CourseRepository;
use App\Service\CourseDateService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CourseDateServiceTest extends TestCase
{
    private CourseRepository&MockObject $courseRepo;
    private CourseDateRepository&MockObject $courseDateRepo;
    private EntityManagerInterface&MockObject $em;
    private CourseDateService $service;

    protected function setUp(): void
    {
        $this->courseRepo = $this->createMock(CourseRepository::class);
        $this->courseDateRepo = $this->createMock(CourseDateRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->service = new CourseDateService(
            $this->courseRepo,
            $this->courseDateRepo,
            $this->em,
        );
    }

    private function makeRecurringCourse(int $dayOfWeek): Course
    {
        $courseType = new CourseType();
        $courseType->setCode('MH');
        $courseType->setName('Mensch-Hund');
        $courseType->setRecurrenceKind(RecurrenceKind::RECURRING);

        $course = new Course();
        $course->setDayOfWeek($dayOfWeek);
        $course->setStartTime('10:00');
        $course->setEndTime('11:00');
        $course->setCourseType($courseType);

        return $course;
    }

    #[Test]
    public function generateForCourseCreatesOnlyOnCorrectWeekday(): void
    {
        $course = $this->makeRecurringCourse(1); // Monday
        $from = new \DateTimeImmutable('2026-03-16'); // Monday
        $until = new \DateTimeImmutable('2026-04-06'); // Monday 3 weeks later

        $this->courseDateRepo->method('existsForCourseAndDate')->willReturn(false);

        $persistCount = 0;
        $this->em->expects(self::atLeastOnce())
            ->method('persist')
            ->willReturnCallback(function () use (&$persistCount): void {
                $persistCount++;
            });

        $created = $this->service->generateForCourse($course, $from, $until);

        self::assertSame(4, $created); // 4 Mondays: Mar 16, 23, 30, Apr 6
    }

    #[Test]
    public function generateForCourseSkipsExistingDates(): void
    {
        $course = $this->makeRecurringCourse(3); // Wednesday
        $from = new \DateTimeImmutable('2026-03-18'); // Wednesday
        $until = new \DateTimeImmutable('2026-03-25'); // Next Wednesday

        $callCount = 0;
        $this->courseDateRepo->method('existsForCourseAndDate')
            ->willReturnCallback(function () use (&$callCount): bool {
                $callCount++;
                return $callCount === 1; // First date exists, second doesn't
            });

        $created = $this->service->generateForCourse($course, $from, $until);

        self::assertSame(1, $created);
    }

    #[Test]
    public function generateForCourseAlignsToCorrectWeekday(): void
    {
        $course = $this->makeRecurringCourse(5); // Friday
        $from = new \DateTimeImmutable('2026-03-16'); // Monday

        $until = new \DateTimeImmutable('2026-03-22'); // Sunday

        $this->courseDateRepo->method('existsForCourseAndDate')->willReturn(false);

        $created = $this->service->generateForCourse($course, $from, $until);

        self::assertSame(1, $created); // Only the Friday 2026-03-20
    }

    #[Test]
    public function generateDatesSkipsNonRecurringCourses(): void
    {
        $recurringCourse = $this->makeRecurringCourse(1);

        $oneTimeCourseType = new CourseType();
        $oneTimeCourseType->setCode('SEM');
        $oneTimeCourseType->setName('Seminar');
        $oneTimeCourseType->setRecurrenceKind(RecurrenceKind::ONE_TIME);
        $oneTimeCourse = new Course();
        $oneTimeCourse->setDayOfWeek(2);
        $oneTimeCourse->setStartTime('14:00');
        $oneTimeCourse->setEndTime('16:00');
        $oneTimeCourse->setCourseType($oneTimeCourseType);

        $this->courseRepo->method('findNonArchived')->willReturn([$recurringCourse, $oneTimeCourse]);
        $this->courseDateRepo->method('existsForCourseAndDate')->willReturn(false);

        $from = new \DateTimeImmutable('2026-03-16');
        $until = new \DateTimeImmutable('2026-03-22');

        $this->em->expects(self::once())->method('flush');

        $created = $this->service->generateDates($from, $until);

        self::assertGreaterThan(0, $created);
    }

    #[Test]
    public function generateDatesReturnsZeroWhenNoCourses(): void
    {
        $this->courseRepo->method('findNonArchived')->willReturn([]);
        $this->em->expects(self::once())->method('flush');

        $created = $this->service->generateDates(
            new \DateTimeImmutable('2026-03-16'),
            new \DateTimeImmutable('2026-03-22'),
        );

        self::assertSame(0, $created);
    }
}
