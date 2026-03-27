<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Booking;
use App\Entity\CourseDate;
use App\Entity\Customer;

final class CustomerCalendarFeedBuilder
{
    /**
     * @param iterable<Booking> $bookings
     */
    public function build(Customer $customer, iterable $bookings): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Komm! Hundeschule//Gebuchte Kurse//DE',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.$this->escapeText('Komm! Kurse - '.$customer->getName()),
            'X-WR-TIMEZONE:'.CourseDate::TIMEZONE,
        ];

        foreach ($bookings as $booking) {
            $courseDate = $booking->getCourseDate();
            if ($courseDate === null) {
                continue;
            }

            $status = $courseDate->isCancelled() ? 'CANCELLED' : 'CONFIRMED';
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:booking-'.$booking->getId().'@komm-hundeschule';
            $lines[] = 'DTSTAMP:'.$booking->getCreatedAt()->setTimezone(new \DateTimeZone('UTC'))->format('Ymd\THis\Z');
            $lines[] = 'DTSTART;TZID='.CourseDate::TIMEZONE.':'.$this->formatDateTime($courseDate, $courseDate->getStartTime());
            $lines[] = 'DTEND;TZID='.CourseDate::TIMEZONE.':'.$this->formatDateTime($courseDate, $courseDate->getEndTime());
            $lines[] = 'SUMMARY:'.$this->escapeText($this->buildSummary($booking));
            $lines[] = 'DESCRIPTION:'.$this->escapeText($this->buildDescription($booking));
            $lines[] = 'STATUS:'.$status;
            $lines[] = 'TRANSP:OPAQUE';
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines)."\r\n";
    }

    private function buildSummary(Booking $booking): string
    {
        $courseName = $booking->getCourseDate()?->getCourse()?->getCourseType()?->getName() ?? 'Kurs';
        $dogName = $booking->getDog()?->getName();
        $prefix = $booking->getCourseDate()?->isCancelled() ? 'Abgesagt: ' : '';

        return $dogName !== null && $dogName !== ''
            ? sprintf('%s%s (%s)', $prefix, $courseName, $dogName)
            : $prefix.$courseName;
    }

    private function buildDescription(Booking $booking): string
    {
        $dogName = $booking->getDog()?->getName();
        $courseName = $booking->getCourseDate()?->getCourse()?->getCourseType()?->getName() ?? 'Kurs';

        if ($booking->getCourseDate()?->isCancelled()) {
            return $dogName !== null && $dogName !== ''
                ? sprintf('Der gebuchte Termin fuer %s im Kurs %s wurde abgesagt.', $dogName, $courseName)
                : sprintf('Der gebuchte Termin im Kurs %s wurde abgesagt.', $courseName);
        }

        return $dogName !== null && $dogName !== ''
            ? sprintf('Gebuchter Kurs fuer %s: %s', $dogName, $courseName)
            : sprintf('Gebuchter Kurs: %s', $courseName);
    }

    private function formatDateTime(CourseDate $courseDate, string $time): string
    {
        $dateTime = \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i',
            $courseDate->getDate()->format('Y-m-d').' '.$time,
            new \DateTimeZone(CourseDate::TIMEZONE),
        );

        if ($dateTime === false) {
            throw new \LogicException('Invalid course date time for calendar feed.');
        }

        return $dateTime->format('Ymd\THis');
    }

    private function escapeText(string $value): string
    {
        return str_replace(
            ["\\", ';', ',', "\r\n", "\r", "\n"],
            ['\\\\', '\;', '\,', '\n', '\n', '\n'],
            $value,
        );
    }
}
