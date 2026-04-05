<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataFixtures;

use App\DataFixtures\CourseTypeFixtures;
use App\DataFixtures\DemoFixtures;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DemoFixturesTest extends TestCase
{
    #[Test]
    public function courseDefinitionsMatchTheCurrentCalendarExport(): void
    {
        $definitions = $this->courseDefinitions();

        self::assertCount(64, $definitions);
        self::assertSame('2026-04-13', $definitions[0]['date']);
        self::assertSame('08:00', $definitions[0]['start']);
        self::assertSame('2026-04-18', $definitions[array_key_last($definitions)]['date']);
        self::assertSame('14:00', $definitions[array_key_last($definitions)]['start']);
    }

    #[Test]
    public function courseDefinitionsOnlyReferenceSeededCourseTypes(): void
    {
        $seededCodes = array_column($this->courseTypes(), 'code');

        foreach ($this->courseDefinitions() as $definition) {
            self::assertContains($definition['code'], $seededCodes);
        }
    }

    /**
     * @return list<array{code: string, date: string, start: string, end: string, level: int, location?: string}>
     */
    private function courseDefinitions(): array
    {
        $reflection = new \ReflectionClass(DemoFixtures::class);
        $constant = $reflection->getReflectionConstant('COURSE_DEFS');
        if ($constant === false) {
            self::fail('DemoFixtures::COURSE_DEFS is missing.');
        }

        /** @var list<array{code: string, date: string, start: string, end: string, level: int, location?: string}> $definitions */
        $definitions = $constant->getValue();

        return $definitions;
    }

    /** @return list<array{code: string, name: string}> */
    private function courseTypes(): array
    {
        $reflection = new \ReflectionClass(CourseTypeFixtures::class);
        $constant = $reflection->getReflectionConstant('COURSE_TYPES');
        if ($constant === false) {
            self::fail('CourseTypeFixtures::COURSE_TYPES is missing.');
        }

        /** @var list<array{code: string, name: string}> $courseTypes */
        $courseTypes = $constant->getValue();

        return $courseTypes;
    }
}
