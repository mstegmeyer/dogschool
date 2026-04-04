<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\GenerateCourseDatesCommand;
use App\Repository\CourseDateRepository;
use App\Repository\CourseRepository;
use App\Service\CourseDateService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class GenerateCourseDatesCommandTest extends TestCase
{
    #[Test]
    public function itUsesTheProvidedMonthWindow(): void
    {
        $courseRepository = $this->createMock(CourseRepository::class);
        $courseRepository
            ->expects(self::once())
            ->method('findNonArchived')
            ->willReturn([]);

        $tester = new CommandTester($this->createCommand($courseRepository));
        $from = new \DateTimeImmutable('today');
        $until = $from->modify('+5 months');

        $exitCode = $tester->execute(['--months' => '5']);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString(
            sprintf(
                'Generating course dates from %s to %s ...',
                $from->format('Y-m-d'),
                $until->format('Y-m-d'),
            ),
            $tester->getDisplay(),
        );
        self::assertStringContainsString('Created 0 new course date(s).', $tester->getDisplay());
    }

    #[Test]
    public function itFallsBackToThreeMonthsForInvalidInput(): void
    {
        $courseRepository = $this->createMock(CourseRepository::class);
        $courseRepository
            ->expects(self::once())
            ->method('findNonArchived')
            ->willReturn([]);

        $tester = new CommandTester($this->createCommand($courseRepository));
        $from = new \DateTimeImmutable('today');
        $until = $from->modify('+3 months');

        $exitCode = $tester->execute(['--months' => '0']);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString(
            sprintf(
                'Generating course dates from %s to %s ...',
                $from->format('Y-m-d'),
                $until->format('Y-m-d'),
            ),
            $tester->getDisplay(),
        );
        self::assertStringContainsString('Created 0 new course date(s).', $tester->getDisplay());
    }

    private function createCommand(CourseRepository&MockObject $courseRepository): GenerateCourseDatesCommand
    {
        $service = new CourseDateService(
            $courseRepository,
            $this->createMock(CourseDateRepository::class),
            $this->createMock(EntityManagerInterface::class),
        );

        return new GenerateCourseDatesCommand($service);
    }
}
