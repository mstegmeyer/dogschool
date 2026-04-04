<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Booking;
use App\Entity\Course;
use App\Entity\CourseDate;
use App\Entity\CourseType;
use App\Entity\Customer;
use App\Entity\Dog;
use App\Service\CustomerCalendarFeedBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CustomerCalendarFeedBuilderTest extends TestCase
{
    private CustomerCalendarFeedBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new CustomerCalendarFeedBuilder();
    }

    private function makeCustomer(string $name = 'Max Muster'): Customer
    {
        return (new Customer())
            ->setName($name)
            ->setEmail('max@example.com')
            ->setPassword('hashed');
    }

    private function makeCourseDate(
        string $courseName = 'Agility',
        bool $cancelled = false,
        string $date = '2026-04-04',
        string $startTime = '09:00',
        string $endTime = '10:15',
    ): CourseDate {
        $courseType = (new CourseType())
            ->setCode('AGI')
            ->setName($courseName);

        $course = (new Course())
            ->setDayOfWeek(6)
            ->setStartTime($startTime)
            ->setEndTime($endTime)
            ->setCourseType($courseType);

        return (new CourseDate())
            ->setCourse($course)
            ->setDate(new \DateTimeImmutable($date, new \DateTimeZone(CourseDate::TIMEZONE)))
            ->setStartTime($startTime)
            ->setEndTime($endTime)
            ->setCancelled($cancelled);
    }

    private function makeBooking(Customer $customer, ?CourseDate $courseDate, ?string $dogName = 'Luna'): Booking
    {
        $booking = (new Booking())
            ->setCustomer($customer);

        if ($courseDate !== null) {
            $booking->setCourseDate($courseDate);
        }

        if ($dogName !== null) {
            $dog = (new Dog())
                ->setName($dogName)
                ->setCustomer($customer);
            $booking->setDog($dog);
        }

        return $booking;
    }

    #[Test]
    public function buildCreatesAConfirmedCalendarEventWithEscapedSummaryAndDescription(): void
    {
        $customer = $this->makeCustomer('Max, Muster');
        $courseDate = $this->makeCourseDate('Basis;Hund');
        $booking = $this->makeBooking($customer, $courseDate, 'Luna,Alpha');

        $calendar = $this->builder->build($customer, [$booking]);

        self::assertStringContainsString('BEGIN:VCALENDAR', $calendar);
        self::assertStringContainsString('X-WR-CALNAME:Komm! Kurse - Max\, Muster', $calendar);
        self::assertStringContainsString('UID:booking-'.$booking->getId().'@komm-hundeschule', $calendar);
        self::assertStringContainsString('DTSTART;TZID=Europe/Berlin:20260404T090000', $calendar);
        self::assertStringContainsString('DTEND;TZID=Europe/Berlin:20260404T101500', $calendar);
        self::assertStringContainsString('SUMMARY:Basis\;Hund (Luna\,Alpha)', $calendar);
        self::assertStringContainsString('DESCRIPTION:Gebuchter Kurs fuer Luna\,Alpha: Basis\;Hund', $calendar);
        self::assertStringContainsString('STATUS:CONFIRMED', $calendar);
        self::assertStringEndsWith("END:VCALENDAR\r\n", $calendar);
    }

    #[Test]
    public function buildMarksCancelledEventsAndSkipsBookingsWithoutACourseDate(): void
    {
        $customer = $this->makeCustomer();
        $cancelledCourseDate = $this->makeCourseDate(
            courseName: 'Social Walk',
            cancelled: true,
            date: '2026-04-05',
            startTime: '11:00',
            endTime: '12:00',
        );

        $cancelledBooking = $this->makeBooking($customer, $cancelledCourseDate, null);
        $orphanedBooking = $this->makeBooking($customer, null, 'Ghost');

        $calendar = $this->builder->build($customer, [$cancelledBooking, $orphanedBooking]);

        self::assertSame(1, substr_count($calendar, 'BEGIN:VEVENT'));
        self::assertStringContainsString('SUMMARY:Abgesagt: Social Walk', $calendar);
        self::assertStringContainsString('DESCRIPTION:Der gebuchte Termin im Kurs Social Walk wurde abgesagt.', $calendar);
        self::assertStringContainsString('STATUS:CANCELLED', $calendar);
    }

    #[Test]
    public function buildThrowsWhenTheCourseDateUsesAnInvalidTime(): void
    {
        $customer = $this->makeCustomer();
        $courseDate = $this->makeCourseDate(
            courseName: 'Agility',
            startTime: 'invalid',
            endTime: '10:00',
        );
        $booking = $this->makeBooking($customer, $courseDate);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Invalid course date time for calendar feed.');

        $this->builder->build($customer, [$booking]);
    }
}
