<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Booking;
use App\Entity\CourseDate;
use App\Entity\Customer;
use App\Entity\Dog;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CourseDateTest extends TestCase
{
    #[Test]
    public function startsAtCombinesDateAndStartTime(): void
    {
        $cd = new CourseDate();
        $cd->setDate(new \DateTimeImmutable('2026-04-15'));
        $cd->setStartTime('14:30');
        $cd->setEndTime('15:30');

        $startsAt = $cd->startsAt();
        self::assertSame('2026-04-15', $startsAt->format('Y-m-d'));
        self::assertSame('14:30', $startsAt->format('H:i'));
        self::assertSame('Europe/Berlin', $startsAt->getTimezone()->getName());
    }

    #[Test]
    public function isBookingWindowClosedReturnsFalseForFutureDate(): void
    {
        $cd = new CourseDate();
        $cd->setDate(new \DateTimeImmutable('+2 days', new \DateTimeZone('Europe/Berlin')));
        $cd->setStartTime('10:00');
        $cd->setEndTime('11:00');

        self::assertFalse($cd->isBookingWindowClosed());
    }

    #[Test]
    public function isBookingWindowClosedReturnsTrueForOldDate(): void
    {
        $cd = new CourseDate();
        $cd->setDate(new \DateTimeImmutable('-3 days', new \DateTimeZone('Europe/Berlin')));
        $cd->setStartTime('10:00');
        $cd->setEndTime('11:00');

        self::assertTrue($cd->isBookingWindowClosed());
    }

    #[Test]
    public function getActiveBookingsFiltersCancelledBookings(): void
    {
        $cd = new CourseDate();
        $cd->setDate(new \DateTimeImmutable('+1 day'));
        $cd->setStartTime('10:00');
        $cd->setEndTime('11:00');

        $customer = new Customer();
        $customer->setName('C');
        $customer->setEmail('c@example.com');
        $customer->setPassword('x');
        $dog = new Dog();
        $dog->setName('Rex');
        $dog->setCustomer($customer);

        $active = new Booking();
        $active->setCustomer($customer);
        $active->setDog($dog);
        $active->setCourseDate($cd);

        $cancelled = new Booking();
        $cancelled->setCustomer($customer);
        $cancelled->setDog($dog);
        $cancelled->setCourseDate($cd);
        $cancelled->setCancelledAt(new \DateTimeImmutable());

        $cd->getBookings()->add($active);
        $cd->getBookings()->add($cancelled);

        $activeBookings = $cd->getActiveBookings();
        self::assertCount(1, $activeBookings);
        self::assertTrue($activeBookings[0]->isActive());
    }

    #[Test]
    public function cancelledFlagDefaultsToFalse(): void
    {
        $cd = new CourseDate();
        self::assertFalse($cd->isCancelled());
    }

    #[Test]
    public function setCancelledTogglesCancelledState(): void
    {
        $cd = new CourseDate();
        $cd->setCancelled(true);
        self::assertTrue($cd->isCancelled());
        $cd->setCancelled(false);
        self::assertFalse($cd->isCancelled());
    }
}
