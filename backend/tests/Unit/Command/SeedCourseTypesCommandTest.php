<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\SeedCourseTypesCommand;
use App\Entity\CourseType;
use App\Repository\CourseTypeRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class SeedCourseTypesCommandTest extends TestCase
{
    #[Test]
    public function itCreatesOnlyMissingCourseTypes(): void
    {
        $defaults = $this->defaults();
        $skippedCode = $defaults[1]['code'];
        $saved = [];

        $repository = $this->createMock(CourseTypeRepository::class);
        $repository
            ->expects(self::exactly(count($defaults)))
            ->method('findByCode')
            ->willReturnCallback(static function (string $code) use ($skippedCode): ?CourseType {
                return $code === $skippedCode ? new CourseType() : null;
            });

        $repository
            ->expects(self::exactly(count($defaults) - 1))
            ->method('save')
            ->willReturnCallback(static function (CourseType $courseType) use (&$saved): void {
                $saved[$courseType->getCode()] = $courseType->getName();
            });

        $tester = new CommandTester(new SeedCourseTypesCommand($repository));
        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertCount(count($defaults) - 1, $saved);
        self::assertArrayNotHasKey($skippedCode, $saved);

        foreach ($defaults as ['code' => $code, 'name' => $name]) {
            if ($code === $skippedCode) {
                continue;
            }

            self::assertSame($name, $saved[$code]);
        }

        self::assertStringNotContainsString(sprintf('Created course type: %s', $skippedCode), $tester->getDisplay());
        self::assertStringContainsString('Course types seeded.', $tester->getDisplay());
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    private function defaults(): array
    {
        $reflection = new \ReflectionClass(SeedCourseTypesCommand::class);
        $constant = $reflection->getReflectionConstant('DEFAULTS');

        self::assertNotNull($constant);

        /** @var list<array{code: string, name: string}> $defaults */
        $defaults = $constant->getValue();

        return $defaults;
    }
}
