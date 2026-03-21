<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Contract;
use App\Enum\ContractType;
use App\Entity\Course;
use App\Entity\CourseDate;
use App\Entity\CreditTransaction;
use App\Entity\Customer;
use App\Entity\Dog;
use App\Entity\Notification;
use Symfony\Component\Validator\ConstraintViolationListInterface;

final class ApiNormalizer
{
    /** @return array<string, mixed> */
    public function normalizeCustomer(Customer $customer): array
    {
        return [
            'id' => $customer->getId(),
            'name' => $customer->getName(),
            'email' => $customer->getEmail(),
            'createdAt' => $customer->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'address' => [
                'street' => $customer->getAddress()->getStreet(),
                'postalCode' => $customer->getAddress()->getPostalCode(),
                'city' => $customer->getAddress()->getCity(),
                'country' => $customer->getAddress()->getCountry(),
            ],
            'bankAccount' => [
                'iban' => $customer->getBankAccount()->getIban(),
                'bic' => $customer->getBankAccount()->getBic(),
                'accountHolder' => $customer->getBankAccount()->getAccountHolder(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function normalizeDog(Dog $dog): array
    {
        return [
            'id' => $dog->getId(),
            'name' => $dog->getName(),
            'color' => $dog->getColor(),
            'gender' => $dog->getGender(),
            'race' => $dog->getRace(),
        ];
    }

    /** @return array<string, mixed> */
    public function normalizeContract(Contract $contract): array
    {
        $type = $contract->getType();

        return [
            'id' => $contract->getId(),
            'contractGroupId' => $contract->getContractGroupId(),
            'version' => $contract->getVersion(),
            'dogId' => $contract->getDog()?->getId(),
            'dogName' => $contract->getDog()?->getName(),
            'customerId' => $contract->getCustomer()?->getId(),
            'customerName' => $contract->getCustomer()?->getName(),
            'startDate' => $contract->getStartDate()?->format('Y-m-d'),
            'endDate' => $contract->getEndDate()?->format('Y-m-d'),
            'price' => $contract->getPrice(),
            /** For PERPETUAL contracts, this is the monthly amount (same as price). */
            'priceMonthly' => $type === ContractType::PERPETUAL ? $contract->getPrice() : null,
            'type' => $type->value,
            'coursesPerWeek' => $contract->getCoursesPerWeek(),
            'state' => $contract->getState()->value,
            'createdAt' => $contract->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /** @return array<string, mixed> */
    public function normalizeCourse(Course $course): array
    {
        $courseType = $course->getCourseType();

        return [
            'id' => $course->getId(),
            'dayOfWeek' => $course->getDayOfWeek(),
            'startTime' => $course->getStartTime(),
            'endTime' => $course->getEndTime(),
            'durationMinutes' => $course->getDurationMinutes(),
            'type' => $courseType !== null
                ? [
                    'code' => $courseType->getCode(),
                    'name' => $courseType->getName(),
                    'recurrenceKind' => $courseType->getRecurrenceKind()->value,
                ]
                : null,
            'level' => $course->getLevel(),
            'comment' => $course->getComment(),
            'archived' => $course->isArchived(),
        ];
    }

    /** @return array<string, mixed> */
    public function normalizeNotification(Notification $notification): array
    {
        $courses = [];
        foreach ($notification->getCourses() as $course) {
            $courses[] = [
                'id' => $course->getId(),
                'typeCode' => $course->getCourseType()?->getCode(),
                'typeName' => $course->getCourseType()?->getName(),
                'dayOfWeek' => $course->getDayOfWeek(),
                'startTime' => $course->getStartTime(),
                'endTime' => $course->getEndTime(),
            ];
        }

        return [
            'id' => $notification->getId(),
            'title' => $notification->getTitle(),
            'message' => $notification->getMessage(),
            'authorName' => $notification->getAuthor()?->getFullName(),
            'authorId' => $notification->getAuthor()?->getId(),
            'isGlobal' => $notification->isGlobal(),
            'courses' => $courses,
            'courseIds' => array_column($courses, 'id'),
            'createdAt' => $notification->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /** @return array<string, mixed> */
    public function normalizeCourseDate(CourseDate $cd): array
    {
        $course = $cd->getCourse();
        $courseType = $course?->getCourseType();

        return [
            'id' => $cd->getId(),
            'courseId' => $course?->getId(),
            'courseType' => $courseType !== null
                ? [
                    'code' => $courseType->getCode(),
                    'name' => $courseType->getName(),
                    'recurrenceKind' => $courseType->getRecurrenceKind()->value,
                ]
                : null,
            'level' => $course?->getLevel(),
            'date' => $cd->getDate()->format('Y-m-d'),
            'dayOfWeek' => (int) $cd->getDate()->format('N'),
            'startTime' => $cd->getStartTime(),
            'endTime' => $cd->getEndTime(),
            'cancelled' => $cd->isCancelled(),
            'bookingCount' => count($cd->getActiveBookings()),
            'createdAt' => $cd->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Course date for admin/trainer views: includes active bookings with dog and owner names.
     *
     * @return array<string, mixed>
     */
    public function normalizeCourseDateForAdmin(CourseDate $cd): array
    {
        $data = $this->normalizeCourseDate($cd);
        $bookings = [];
        foreach ($cd->getActiveBookings() as $booking) {
            $bookings[] = [
                'id' => $booking->getId(),
                'dogId' => $booking->getDog()?->getId(),
                'dogName' => $booking->getDog()?->getName(),
                'customerId' => $booking->getCustomer()?->getId(),
                'customerName' => $booking->getCustomer()?->getName(),
            ];
        }
        $data['bookings'] = $bookings;

        return $data;
    }

    /** @return array<string, mixed> */
    public function normalizeCourseDateForCustomer(CourseDate $cd, Customer $customer): array
    {
        $data = $this->normalizeCourseDate($cd);

        $myBookings = [];
        foreach ($cd->getActiveBookings() as $booking) {
            if ($booking->getCustomer()?->getId() === $customer->getId()) {
                $myBookings[] = [
                    'id' => $booking->getId(),
                    'dogId' => $booking->getDog()?->getId(),
                    'dogName' => $booking->getDog()?->getName(),
                ];
            }
        }

        $data['booked'] = $myBookings !== [];
        $data['bookings'] = $myBookings;

        $course = $cd->getCourse();
        $data['subscribed'] = $course !== null && $customer->getSubscribedCourses()->contains($course);
        $data['bookingWindowClosed'] = $cd->isBookingWindowClosed();

        return $data;
    }

    /** @return array<string, mixed> */
    public function normalizeCreditTransaction(CreditTransaction $tx): array
    {
        return [
            'id' => $tx->getId(),
            'amount' => $tx->getAmount(),
            'type' => $tx->getType()->value,
            'description' => $tx->getDescription(),
            'courseDateId' => $tx->getCourseDate()?->getId(),
            'contractId' => $tx->getContract()?->getId(),
            'weekRef' => $tx->getWeekRef(),
            'createdAt' => $tx->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /** @return array<string, mixed> */
    public function normalizeBooking(Booking $booking): array
    {
        return [
            'id' => $booking->getId(),
            'customerId' => $booking->getCustomer()?->getId(),
            'dogId' => $booking->getDog()?->getId(),
            'courseDateId' => $booking->getCourseDate()?->getId(),
            'courseDate' => $booking->getCourseDate() !== null
                ? $this->normalizeCourseDate($booking->getCourseDate())
                : null,
            'active' => $booking->isActive(),
            'createdAt' => $booking->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'cancelledAt' => $booking->getCancelledAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function violationsToArray(ConstraintViolationListInterface $errors): array
    {
        $arr = [];
        foreach ($errors as $e) {
            $arr[$e->getPropertyPath()] = (string) $e->getMessage();
        }

        return $arr;
    }
}
