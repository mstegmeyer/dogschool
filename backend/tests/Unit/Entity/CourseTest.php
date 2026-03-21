<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Course;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CourseTest extends TestCase
{
    #[Test]
    public function computeDurationMinutesCalculatesFromStartAndEndTime(): void
    {
        $course = new Course();
        $course->setDayOfWeek(1);
        $course->setStartTime('09:00');
        $course->setEndTime('10:30');
        $course->computeDurationMinutes();

        self::assertSame(90, $course->getDurationMinutes());
    }

    #[Test]
    public function computeDurationMinutesHandlesOneHourSlot(): void
    {
        $course = new Course();
        $course->setDayOfWeek(1);
        $course->setStartTime('14:00');
        $course->setEndTime('15:00');
        $course->computeDurationMinutes();

        self::assertSame(60, $course->getDurationMinutes());
    }

    #[Test]
    public function archivedDefaultsToFalse(): void
    {
        $course = new Course();
        self::assertFalse($course->isArchived());
    }

    #[Test]
    public function setArchivedTogglesState(): void
    {
        $course = new Course();
        $course->setArchived(true);
        self::assertTrue($course->isArchived());
    }

    #[Test]
    public function idIsGeneratedOnConstruction(): void
    {
        $course = new Course();
        self::assertNotEmpty($course->getId());
        self::assertSame(36, strlen($course->getId()));
    }
}
