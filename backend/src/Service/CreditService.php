<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Contract;
use App\Entity\CourseDate;
use App\Entity\CreditTransaction;
use App\Entity\Customer;
use App\Entity\Dog;
use App\Enum\CreditTransactionType;
use App\Enum\RecurrenceKind;
use App\Repository\BookingRepository;
use App\Repository\ContractRepository;
use App\Repository\CreditTransactionRepository;
use Doctrine\ORM\EntityManagerInterface;

final class CreditService
{
    public function __construct(
        private readonly CreditTransactionRepository $creditTransactionRepository,
        private readonly BookingRepository $bookingRepository,
        private readonly ContractRepository $contractRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function getBalance(Customer $customer): int
    {
        return $this->creditTransactionRepository->getBalance($customer);
    }

    /**
     * @return array<int, CreditTransaction>
     */
    public function getHistory(Customer $customer, int $limit = 100): array
    {
        return $this->creditTransactionRepository->findByCustomer($customer, $limit);
    }

    /**
     * After a contract is cancelled with an effective end date, reverse weekly grants
     * that fall after the final effective contract week and keep the balance non-negative.
     */
    public function applyContractCancellationCredits(Contract $contract): void
    {
        $customer = $contract->getCustomer();
        if ($customer === null) {
            return;
        }

        $endDate = $contract->getEndDate();
        if ($endDate === null) {
            return;
        }

        $lastEligibleWeekRef = $endDate->format('o-\WW');
        $futureGrants = $this->creditTransactionRepository->findWeeklyGrantsAfterWeek($contract, $lastEligibleWeekRef);
        $sum = array_sum(array_map(static fn (CreditTransaction $tx): int => $tx->getAmount(), $futureGrants));
        if ($sum <= 0) {
            return;
        }

        $tx = new CreditTransaction();
        $tx->setCustomer($customer);
        $tx->setAmount(-$sum);
        $tx->setType(CreditTransactionType::MANUAL_ADJUSTMENT);
        $tx->setContract($contract);
        $tx->setDescription(sprintf(
            'Vertragsende %s: Rückbuchung der Wochen-Gutschriften nach Vertragsende (%d Credits).',
            $endDate->format('d.m.Y'),
            $sum,
        ));
        $this->em->persist($tx);
        $this->em->flush();

        $balance = $this->getBalance($customer);
        if ($balance < 0) {
            $fix = new CreditTransaction();
            $fix->setCustomer($customer);
            $fix->setAmount(-$balance);
            $fix->setType(CreditTransactionType::MANUAL_ADJUSTMENT);
            $fix->setDescription('Korrektur nach Vertragsende: Guthaben nicht negativ.');
            $this->em->persist($fix);
            $this->em->flush();
        }
    }

    /**
     * @return list<array{contractId: string, amount: int, dogName: ?string, nextGrantAt: string, currentWeekRef: string, pendingGrantThisWeek: bool}>
     */
    public function getNextWeeklyCreditHints(Customer $customer): array
    {
        $tz = new \DateTimeZone(CourseDate::TIMEZONE);
        $now = new \DateTimeImmutable('now', $tz);
        $currentWeekRef = $now->format('o-\WW');

        $n = (int) $now->format('N');
        $daysToNextMonday = $n === 1 ? 7 : (8 - $n);
        $nextMonday = $now->setTime(0, 0)->modify(sprintf('+%d days', $daysToNextMonday));

        $hints = [];
        foreach ($this->contractRepository->findCreditEligiblePerpetualByCustomer($customer) as $contract) {
            if ($contract->getCoursesPerWeek() <= 0) {
                continue;
            }
            $startDate = $contract->getStartDate();
            $endDate = $contract->getEndDate();
            if ($startDate !== null && $startDate > $now) {
                continue;
            }
            if ($endDate !== null && $endDate < $now) {
                continue;
            }

            $pending = !$this->creditTransactionRepository->weeklyGrantExists($contract, $currentWeekRef);
            $hints[] = [
                'contractId' => $contract->getId(),
                'amount' => $contract->getCoursesPerWeek(),
                'dogName' => $contract->getDog()?->getName(),
                'nextGrantAt' => $nextMonday->setTimezone($tz)->format(\DateTimeInterface::ATOM),
                'currentWeekRef' => $currentWeekRef,
                'pendingGrantThisWeek' => $pending,
            ];
        }

        return $hints;
    }

    /**
     * Grant weekly credits for a contract if not already granted this week.
     *
     * @return CreditTransaction|null the created transaction, or null if already granted
     */
    public function grantWeeklyCredits(Contract $contract, string $weekRef): ?CreditTransaction
    {
        if ($this->creditTransactionRepository->weeklyGrantExists($contract, $weekRef)) {
            return null;
        }

        $customer = $contract->getCustomer();
        if ($customer === null) {
            return null;
        }

        $tx = new CreditTransaction();
        $tx->setCustomer($customer);
        $tx->setAmount($contract->getCoursesPerWeek());
        $tx->setType(CreditTransactionType::WEEKLY_GRANT);
        $tx->setContract($contract);
        $tx->setWeekRef($weekRef);
        $tx->setDescription(sprintf(
            'Weekly credit grant (%d) for week %s from contract %s',
            $contract->getCoursesPerWeek(),
            $weekRef,
            $contract->getId(),
        ));

        $this->creditTransactionRepository->save($tx);

        return $tx;
    }

    /**
     * Book a course date for a customer's dog, spending 1 credit.
     *
     * @return Booking|string the booking on success, or an error message
     */
    public function bookCourseDate(Customer $customer, CourseDate $courseDate, Dog $dog): Booking|string
    {
        if ($dog->getCustomer()?->getId() !== $customer->getId()) {
            return 'Dog does not belong to this customer.';
        }

        if ($courseDate->isCancelled()) {
            return 'This course date has been cancelled.';
        }

        $course = $courseDate->getCourse();
        $courseType = $course?->getCourseType();
        if ($course === null || $courseType === null || $courseType->getRecurrenceKind() !== RecurrenceKind::RECURRING) {
            return 'Only recurring courses can be booked with credits.';
        }

        $existing = $this->bookingRepository->findActiveByDogAndCourseDate($dog, $courseDate);
        if ($existing !== null) {
            return 'This dog already has an active booking for this date.';
        }

        $balance = $this->getBalance($customer);
        if ($balance < 1) {
            return 'Insufficient credits.';
        }

        if ($courseDate->isBookingWindowClosed()) {
            return 'This course date can no longer be booked (more than 24 hours in the past).';
        }

        $tx = new CreditTransaction();
        $tx->setCustomer($customer);
        $tx->setAmount(-1);
        $tx->setType(CreditTransactionType::BOOKING);
        $tx->setCourseDate($courseDate);
        $tx->setDescription(sprintf(
            'Booked %s on %s (%s)',
            $courseType->getName(),
            $courseDate->getDate()->format('Y-m-d'),
            $dog->getName(),
        ));

        $booking = new Booking();
        $booking->setCustomer($customer);
        $booking->setCourseDate($courseDate);
        $booking->setDog($dog);
        $booking->setCreditTransaction($tx);

        $this->em->persist($tx);
        $this->em->persist($booking);
        $this->em->flush();

        return $booking;
    }

    /**
     * Refund credits for all active bookings on a cancelled course date.
     *
     * @return int the number of bookings refunded
     */
    public function refundBookingsForCancelledCourseDate(CourseDate $courseDate): int
    {
        $activeBookings = $courseDate->getActiveBookings();
        $course = $courseDate->getCourse();
        $courseType = $course?->getCourseType();
        $courseName = $courseType !== null ? $courseType->getName() : 'course';

        foreach ($activeBookings as $booking) {
            $customer = $booking->getCustomer();
            if ($customer === null) {
                continue;
            }

            $tx = new CreditTransaction();
            $tx->setCustomer($customer);
            $tx->setAmount(1);
            $tx->setType(CreditTransactionType::CANCELLATION);
            $tx->setCourseDate($courseDate);
            $tx->setDescription(sprintf(
                'Cancelled by dog school: %s on %s (%s)',
                $courseName,
                $courseDate->getDate()->format('Y-m-d'),
                $booking->getDog()?->getName() ?? 'unknown',
            ));

            $this->em->persist($tx);
        }

        if (count($activeBookings) > 0) {
            $this->em->flush();
        }

        return count($activeBookings);
    }

    /**
     * Cancel a booking and refund the credit.
     *
     * @return Booking|string the updated booking on success, or an error message
     */
    public function cancelBooking(Customer $customer, CourseDate $courseDate, Dog $dog): Booking|string
    {
        if ($dog->getCustomer()?->getId() !== $customer->getId()) {
            return 'Dog does not belong to this customer.';
        }

        $booking = $this->bookingRepository->findActiveByDogAndCourseDate($dog, $courseDate);
        if ($booking === null) {
            return 'No active booking found for this dog on this course date.';
        }

        if ($courseDate->isBookingWindowClosed()) {
            return 'This booking can no longer be cancelled (more than 24 hours in the past).';
        }

        $booking->setCancelledAt(new \DateTimeImmutable());

        $tx = new CreditTransaction();
        $tx->setCustomer($customer);
        $tx->setAmount(1);
        $tx->setType(CreditTransactionType::CANCELLATION);
        $tx->setCourseDate($courseDate);
        $course = $courseDate->getCourse();
        $courseType = $course?->getCourseType();
        $tx->setDescription(sprintf(
            'Cancelled booking for %s on %s (%s)',
            $courseType !== null ? $courseType->getName() : 'course',
            $courseDate->getDate()->format('Y-m-d'),
            $dog->getName(),
        ));

        $this->em->persist($tx);
        $this->em->persist($booking);
        $this->em->flush();

        return $booking;
    }
}
