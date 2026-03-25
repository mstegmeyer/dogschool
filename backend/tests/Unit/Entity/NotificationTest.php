<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Course;
use App\Entity\CourseType;
use App\Entity\Notification;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NotificationTest extends TestCase
{
    #[Test]
    public function isGlobalReturnsTrueWhenNoCourses(): void
    {
        $notification = new Notification();
        $notification->setTitle('Global');
        $notification->setMessage('All courses');

        self::assertTrue($notification->isGlobal());
    }

    #[Test]
    public function isGlobalReturnsFalseWhenCoursesAttached(): void
    {
        $courseType = new CourseType();
        $courseType->setCode('MH');
        $courseType->setName('Mensch-Hund');

        $course = new Course();
        $course->setDayOfWeek(1);
        $course->setStartTime('10:00');
        $course->setEndTime('11:00');
        $course->setCourseType($courseType);

        $notification = new Notification();
        $notification->setTitle('Course-specific');
        $notification->setMessage('Only MH');
        $notification->addCourse($course);

        self::assertFalse($notification->isGlobal());
    }

    #[Test]
    public function addCourseDoesNotDuplicate(): void
    {
        $courseType = new CourseType();
        $courseType->setCode('AGI');
        $courseType->setName('Agility');

        $course = new Course();
        $course->setDayOfWeek(3);
        $course->setStartTime('18:00');
        $course->setEndTime('19:00');
        $course->setCourseType($courseType);

        $notification = new Notification();
        $notification->setTitle('Test');
        $notification->setMessage('Test');
        $notification->addCourse($course);
        $notification->addCourse($course);

        self::assertCount(1, $notification->getCourses());
    }

    #[Test]
    public function isPinnedReturnsFalseWhenNoPinnedUntil(): void
    {
        $notification = new Notification();
        $notification->setTitle('Test');
        $notification->setMessage('Body');

        self::assertFalse($notification->isPinned());
        self::assertNull($notification->getPinnedUntil());
    }

    #[Test]
    public function isPinnedReturnsTrueWhenDeadlineInFuture(): void
    {
        $notification = new Notification();
        $notification->setTitle('Vacation');
        $notification->setMessage('We are closed');
        $notification->setPinnedUntil(new \DateTimeImmutable('+7 days'));

        self::assertTrue($notification->isPinned());
    }

    #[Test]
    public function isPinnedReturnsFalseWhenDeadlinePassed(): void
    {
        $notification = new Notification();
        $notification->setTitle('Old pin');
        $notification->setMessage('Body');
        $notification->setPinnedUntil(new \DateTimeImmutable('-1 day'));

        self::assertFalse($notification->isPinned());
    }

    #[Test]
    public function isPinnedAcceptsCustomNow(): void
    {
        $notification = new Notification();
        $notification->setTitle('Test');
        $notification->setMessage('Body');
        $notification->setPinnedUntil(new \DateTimeImmutable('2026-06-01'));

        self::assertTrue($notification->isPinned(new \DateTimeImmutable('2026-05-15')));
        self::assertFalse($notification->isPinned(new \DateTimeImmutable('2026-07-01')));
    }

    #[Test]
    public function setPinnedUntilCanClearValue(): void
    {
        $notification = new Notification();
        $notification->setTitle('Test');
        $notification->setMessage('Body');
        $notification->setPinnedUntil(new \DateTimeImmutable('+7 days'));

        self::assertTrue($notification->isPinned());
        $notification->setPinnedUntil(null);
        self::assertFalse($notification->isPinned());
    }

    #[Test]
    public function removeCourseRemovesSuccessfully(): void
    {
        $courseType = new CourseType();
        $courseType->setCode('AGI');
        $courseType->setName('Agility');

        $course = new Course();
        $course->setDayOfWeek(3);
        $course->setStartTime('18:00');
        $course->setEndTime('19:00');
        $course->setCourseType($courseType);

        $notification = new Notification();
        $notification->setTitle('Test');
        $notification->setMessage('Test');
        $notification->addCourse($course);
        self::assertCount(1, $notification->getCourses());

        $notification->removeCourse($course);
        self::assertCount(0, $notification->getCourses());
        self::assertTrue($notification->isGlobal());
    }
}
