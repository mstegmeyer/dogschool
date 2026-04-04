<?php

declare(strict_types=1);

namespace App\Support;

final class AppClock
{
    public static function now(?\DateTimeZone $timezone = null): \DateTimeImmutable
    {
        $fixed = $_SERVER['APP_FIXED_NOW'] ?? $_ENV['APP_FIXED_NOW'] ?? null;

        $value = is_string($fixed) && $fixed !== ''
            ? new \DateTimeImmutable($fixed)
            : new \DateTimeImmutable();

        return $timezone !== null ? $value->setTimezone($timezone) : $value;
    }

    public static function today(?\DateTimeZone $timezone = null): \DateTimeImmutable
    {
        return self::now($timezone)->setTime(0, 0, 0);
    }
}
