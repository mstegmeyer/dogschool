<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support;

use App\Support\AppClock;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AppClockTest extends TestCase
{
    private mixed $serverFixedNow;
    private mixed $envFixedNow;
    private bool $hadServerFixedNow;
    private bool $hadEnvFixedNow;

    protected function setUp(): void
    {
        $this->hadServerFixedNow = array_key_exists('APP_FIXED_NOW', $_SERVER);
        $this->serverFixedNow = $_SERVER['APP_FIXED_NOW'] ?? null;
        $this->hadEnvFixedNow = array_key_exists('APP_FIXED_NOW', $_ENV);
        $this->envFixedNow = $_ENV['APP_FIXED_NOW'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->hadServerFixedNow) {
            $_SERVER['APP_FIXED_NOW'] = $this->serverFixedNow;
        } else {
            unset($_SERVER['APP_FIXED_NOW']);
        }

        if ($this->hadEnvFixedNow) {
            $_ENV['APP_FIXED_NOW'] = $this->envFixedNow;
        } else {
            unset($_ENV['APP_FIXED_NOW']);
        }
    }

    #[Test]
    public function nowUsesTheFixedServerTimeAndAppliesTimezoneConversion(): void
    {
        $_SERVER['APP_FIXED_NOW'] = '2026-04-04T12:34:56+00:00';
        unset($_ENV['APP_FIXED_NOW']);

        $value = AppClock::now(new \DateTimeZone('Europe/Berlin'));

        self::assertSame('2026-04-04T14:34:56+02:00', $value->format(\DateTimeInterface::ATOM));
    }

    #[Test]
    public function todayUsesTheFixedEnvTimeAndResetsToMidnight(): void
    {
        unset($_SERVER['APP_FIXED_NOW']);
        $_ENV['APP_FIXED_NOW'] = '2026-04-04T23:59:59+02:00';

        $value = AppClock::today(new \DateTimeZone('Europe/Berlin'));

        self::assertSame('2026-04-04T00:00:00+02:00', $value->format(\DateTimeInterface::ATOM));
    }
}
