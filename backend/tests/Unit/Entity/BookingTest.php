<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Booking;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BookingTest extends TestCase
{
    #[Test]
    public function isActiveReturnsTrueByDefault(): void
    {
        $booking = new Booking();
        self::assertTrue($booking->isActive());
        self::assertNull($booking->getCancelledAt());
    }

    #[Test]
    public function isActiveReturnsFalseAfterCancellation(): void
    {
        $booking = new Booking();
        $booking->setCancelledAt(new \DateTimeImmutable());

        self::assertFalse($booking->isActive());
        self::assertNotNull($booking->getCancelledAt());
    }

    #[Test]
    public function idIsUuidOnConstruction(): void
    {
        $booking = new Booking();
        self::assertNotEmpty($booking->getId());
        self::assertSame(36, strlen($booking->getId()));
    }

    #[Test]
    public function createdAtIsSetOnConstruction(): void
    {
        $before = new \DateTimeImmutable();
        $booking = new Booking();
        $after = new \DateTimeImmutable();

        self::assertGreaterThanOrEqual($before, $booking->getCreatedAt());
        self::assertLessThanOrEqual($after, $booking->getCreatedAt());
    }
}
