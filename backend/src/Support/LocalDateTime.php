<?php

declare(strict_types=1);

namespace App\Support;

use App\Entity\CourseDate;

final class LocalDateTime
{
    private const STORAGE_FORMAT = 'Y-m-d H:i:s';

    /**
     * Hotel datetimes are stored without timezone information and are treated as local wall time.
     * Re-attach the app timezone before serializing them for the browser.
     */
    public static function formatWallTime(\DateTimeImmutable $value): string
    {
        $local = \DateTimeImmutable::createFromFormat(
            self::STORAGE_FORMAT,
            $value->format(self::STORAGE_FORMAT),
            new \DateTimeZone(CourseDate::TIMEZONE),
        );

        if ($local === false) {
            throw new \LogicException('Could not format local date time.');
        }

        return $local->format(\DateTimeInterface::ATOM);
    }

    public static function fromTimestamp(int $timestamp): \DateTimeImmutable
    {
        return (new \DateTimeImmutable(sprintf('@%d', $timestamp)))
            ->setTimezone(new \DateTimeZone(CourseDate::TIMEZONE));
    }
}
