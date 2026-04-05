<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Pricing\ContractPricingSnapshot;
use App\Dto\Pricing\HotelBookingPricingSnapshot;
use App\Entity\Booking;
use App\Entity\Contract;
use App\Entity\Course;
use App\Entity\CourseDate;
use App\Entity\CourseType;
use App\Entity\CreditTransaction;
use App\Entity\Customer;
use App\Entity\Dog;
use App\Entity\HotelBooking;
use App\Entity\Notification;
use App\Entity\PricingConfig;
use App\Entity\PushDevice;
use App\Entity\Room;
use App\Entity\User;
use App\Enum\ContractType;
use App\Support\LocalDateTime;
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
            'shoulderHeightCm' => $dog->getShoulderHeightCm(),
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
            'quotedMonthlyPrice' => $contract->getQuotedMonthlyPrice(),
            'priceMonthly' => match ($type) {
                ContractType::PERPETUAL => $contract->getPrice(),
            },
            'registrationFee' => $contract->getRegistrationFee(),
            'firstInvoiceTotal' => PricingEngine::formatAmount(
                PricingEngine::amountToCents($contract->getPrice()) + PricingEngine::amountToCents($contract->getRegistrationFee())
            ),
            'type' => $type->value,
            'coursesPerWeek' => $contract->getCoursesPerWeek(),
            'state' => $contract->getState()->value,
            'customerComment' => $contract->getCustomerComment(),
            'adminComment' => $contract->getAdminComment(),
            'pricingSnapshot' => $this->normalizePricingSnapshot($contract->getPricingSnapshot(), 'contract'),
            'createdAt' => $contract->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /** @return array<string, mixed> */
    public function normalizeCourseType(CourseType $ct): array
    {
        return [
            'id' => $ct->getId(),
            'code' => $ct->getCode(),
            'name' => $ct->getName(),
            'recurrenceKind' => $ct->getRecurrenceKind()->value,
        ];
    }

    /** @return array<string, mixed> */
    public function normalizeCourse(Course $course): array
    {
        $courseType = $course->getCourseType();

        $subscribers = [];
        foreach ($course->getSubscribedCustomers() as $customer) {
            $subscribers[] = [
                'id' => $customer->getId(),
                'name' => $customer->getName(),
            ];
        }

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
            'trainer' => $this->normalizeTrainer($course->getTrainer()),
            'comment' => $course->getComment(),
            'archived' => $course->isArchived(),
            'subscriberCount' => count($subscribers),
            'subscribers' => $subscribers,
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
            'pinnedUntil' => $notification->getPinnedUntil()?->format(\DateTimeInterface::ATOM),
            'isPinned' => $notification->isPinned(),
            'createdAt' => $notification->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /** @return array<string, mixed> */
    public function normalizePushDevice(PushDevice $pushDevice): array
    {
        return [
            'id' => $pushDevice->getId(),
            'token' => $pushDevice->getToken(),
            'platform' => $pushDevice->getPlatform(),
            'provider' => $pushDevice->getProvider(),
            'deviceName' => $pushDevice->getDeviceName(),
            'createdAt' => $pushDevice->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $pushDevice->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            'lastSeenAt' => $pushDevice->getLastSeenAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /** @return array<string, mixed> */
    public function normalizeCourseDate(CourseDate $cd): array
    {
        $course = $cd->getCourse();
        $courseType = $course?->getCourseType();
        $courseTrainer = $course?->getTrainer();
        $effectiveTrainer = $cd->getTrainer() ?? $courseTrainer;

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
            'trainer' => $this->normalizeTrainer($effectiveTrainer),
            'courseTrainer' => $this->normalizeTrainer($courseTrainer),
            'trainerOverridden' => ($cd->getTrainer()?->getId() ?? null) !== null
                && $cd->getTrainer()?->getId() !== $courseTrainer?->getId(),
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

        $course = $cd->getCourse();
        $subscribers = [];
        if ($course !== null) {
            foreach ($course->getSubscribedCustomers() as $customer) {
                $subscribers[] = [
                    'id' => $customer->getId(),
                    'name' => $customer->getName(),
                ];
            }
        }
        $data['subscriberCount'] = count($subscribers);
        $data['subscribers'] = $subscribers;

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

    /** @return array<string, mixed> */
    public function normalizeRoom(Room $room): array
    {
        return [
            'id' => $room->getId(),
            'name' => $room->getName(),
            'squareMeters' => $room->getSquareMeters(),
            'createdAt' => $room->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /** @return array<string, mixed> */
    public function normalizeHotelBooking(HotelBooking $booking): array
    {
        return [
            'id' => $booking->getId(),
            'customerId' => $booking->getCustomer()?->getId(),
            'customerName' => $booking->getCustomer()?->getName(),
            'dogId' => $booking->getDog()?->getId(),
            'dogName' => $booking->getDog()?->getName(),
            'dogShoulderHeightCm' => $booking->getDog()?->getShoulderHeightCm(),
            'roomId' => $booking->getRoom()?->getId(),
            'roomName' => $booking->getRoom()?->getName(),
            'startAt' => LocalDateTime::formatWallTime($booking->getStartAt()),
            'endAt' => LocalDateTime::formatWallTime($booking->getEndAt()),
            'pricingKind' => $booking->getPricingKind()->value,
            'billableDays' => $booking->getBillableDays(),
            'includesTravelProtection' => $booking->includesTravelProtection(),
            'totalPrice' => $booking->getTotalPrice(),
            'quotedTotalPrice' => $booking->getQuotedTotalPrice(),
            'serviceFee' => $booking->getServiceFee(),
            'travelProtectionPrice' => $booking->getTravelProtectionPrice(),
            'state' => $booking->getState()->value,
            'customerComment' => $booking->getCustomerComment(),
            'adminComment' => $booking->getAdminComment(),
            'pricingSnapshot' => $this->normalizePricingSnapshot($booking->getPricingSnapshot(), 'hotelBooking'),
            'createdAt' => $booking->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /** @return array<string, mixed> */
    public function normalizePricingConfig(PricingConfig $pricingConfig): array
    {
        return [
            'id' => $pricingConfig->getId(),
            'schoolOneCoursePrice' => $pricingConfig->getSchoolOneCoursePrice(),
            'schoolTwoCoursesUnitPrice' => $pricingConfig->getSchoolTwoCoursesUnitPrice(),
            'schoolThreeCoursesUnitPrice' => $pricingConfig->getSchoolThreeCoursesUnitPrice(),
            'schoolFourCoursesUnitPrice' => $pricingConfig->getSchoolFourCoursesUnitPrice(),
            'schoolAdditionalCoursesUnitPrice' => $pricingConfig->getSchoolAdditionalCoursesUnitPrice(),
            'schoolRegistrationFee' => $pricingConfig->getSchoolRegistrationFee(),
            'daycareOffSeasonDailyPrice' => $pricingConfig->getDaycareOffSeasonDailyPrice(),
            'daycarePeakSeasonDailyPrice' => $pricingConfig->getDaycarePeakSeasonDailyPrice(),
            'hotelDailyPrice' => $pricingConfig->getHotelDailyPrice(),
            'hotelServiceFee' => $pricingConfig->getHotelServiceFee(),
            'hotelTravelProtectionBaseFee' => $pricingConfig->getHotelTravelProtectionBaseFee(),
            'hotelTravelProtectionAdditionalDailyFee' => $pricingConfig->getHotelTravelProtectionAdditionalDailyFee(),
            'hotelSingleRoomDaycareDailyPrice' => $pricingConfig->getHotelSingleRoomDaycareDailyPrice(),
            'hotelSingleRoomHotelDailyPrice' => $pricingConfig->getHotelSingleRoomHotelDailyPrice(),
            'hotelHeatCycleDailyPrice' => $pricingConfig->getHotelHeatCycleDailyPrice(),
            'hotelMedicationPerAdministrationPrice' => $pricingConfig->getHotelMedicationPerAdministrationPrice(),
            'hotelSupplementPerAdministrationPrice' => $pricingConfig->getHotelSupplementPerAdministrationPrice(),
            'hotelPeakSeasons' => array_map(static fn (\App\Entity\HotelPeakSeason $season): array => [
                'id' => $season->getId(),
                'startDate' => $season->getStartDate()?->format('Y-m-d'),
                'endDate' => $season->getEndDate()?->format('Y-m-d'),
            ], $pricingConfig->getHotelPeakSeasons()->toArray()),
            'createdAt' => $pricingConfig->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $pricingConfig->getUpdatedAt()->format(\DateTimeInterface::ATOM),
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

    /** @return array{id: string, username: string, fullName: string, phone: ?string}|null */
    public function normalizeTrainer(?User $trainer): ?array
    {
        if ($trainer === null) {
            return null;
        }

        return [
            'id' => $trainer->getId(),
            'username' => $trainer->getUsername(),
            'fullName' => $trainer->getFullName(),
            'phone' => $trainer->getPhone(),
        ];
    }

    /**
     * @param array<string, mixed> $snapshot
     *
     * @return array<string, mixed>
     */
    private function normalizePricingSnapshot(array $snapshot, string $type): array
    {
        return match ($type) {
            'contract' => ContractPricingSnapshot::fromArray($snapshot)->toArray(),
            'hotelBooking' => HotelBookingPricingSnapshot::fromArray($snapshot)->toArray(),
            default => $snapshot,
        };
    }
}
