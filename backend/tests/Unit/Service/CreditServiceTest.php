<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Booking;
use App\Entity\Contract;
use App\Entity\Course;
use App\Entity\CourseDate;
use App\Entity\CourseType;
use App\Entity\CreditTransaction;
use App\Entity\Customer;
use App\Entity\Dog;
use App\Enum\ContractType;
use App\Enum\CreditTransactionType;
use App\Enum\RecurrenceKind;
use App\Repository\BookingRepository;
use App\Repository\ContractRepository;
use App\Repository\CreditTransactionRepository;
use App\Service\CreditService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CreditServiceTest extends TestCase
{
    private CreditTransactionRepository&MockObject $creditTransactionRepo;
    private BookingRepository&MockObject $bookingRepo;
    private ContractRepository&MockObject $contractRepo;
    private EntityManagerInterface&MockObject $em;
    private CreditService $service;

    protected function setUp(): void
    {
        $this->creditTransactionRepo = $this->createMock(CreditTransactionRepository::class);
        $this->bookingRepo = $this->createMock(BookingRepository::class);
        $this->contractRepo = $this->createMock(ContractRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->service = new CreditService(
            $this->creditTransactionRepo,
            $this->bookingRepo,
            $this->contractRepo,
            $this->em,
        );
    }

    private function makeCustomerWithDog(): array
    {
        $customer = new Customer();
        $customer->setName('Test');
        $customer->setEmail('test@example.com');
        $customer->setPassword('hashed');

        $dog = new Dog();
        $dog->setName('Rex');
        $dog->setCustomer($customer);

        return [$customer, $dog];
    }

    private function makeRecurringCourseDate(bool $cancelled = false, string $dateStr = '+1 day'): CourseDate
    {
        $courseType = new CourseType();
        $courseType->setCode('MH');
        $courseType->setName('Mensch-Hund');
        $courseType->setRecurrenceKind(RecurrenceKind::RECURRING);

        $course = new Course();
        $course->setDayOfWeek(1);
        $course->setStartTime('10:00');
        $course->setEndTime('11:00');
        $course->setCourseType($courseType);

        $cd = new CourseDate();
        $cd->setCourse($course);
        $cd->setDate(new \DateTimeImmutable($dateStr, new \DateTimeZone('Europe/Berlin')));
        $cd->setStartTime('10:00');
        $cd->setEndTime('11:00');
        $cd->setCancelled($cancelled);

        return $cd;
    }

    #[Test]
    public function bookCourseDateRejectsDogNotBelongingToCustomer(): void
    {
        [$customer] = $this->makeCustomerWithDog();
        $otherCustomer = new Customer();
        $otherCustomer->setName('Other');
        $otherCustomer->setEmail('other@example.com');
        $otherCustomer->setPassword('x');
        $otherDog = new Dog();
        $otherDog->setName('Fido');
        $otherDog->setCustomer($otherCustomer);

        $cd = $this->makeRecurringCourseDate();

        $result = $this->service->bookCourseDate($customer, $cd, $otherDog);
        self::assertIsString($result);
        self::assertStringContainsString('does not belong', $result);
    }

    #[Test]
    public function bookCourseDateRejectsCancelledDate(): void
    {
        [$customer, $dog] = $this->makeCustomerWithDog();
        $cd = $this->makeRecurringCourseDate(cancelled: true);

        $result = $this->service->bookCourseDate($customer, $cd, $dog);
        self::assertIsString($result);
        self::assertStringContainsString('cancelled', $result);
    }

    #[Test]
    public function bookCourseDateRejectsNonRecurringCourse(): void
    {
        [$customer, $dog] = $this->makeCustomerWithDog();

        $courseType = new CourseType();
        $courseType->setCode('SEM');
        $courseType->setName('Seminar');
        $courseType->setRecurrenceKind(RecurrenceKind::ONE_TIME);

        $course = new Course();
        $course->setDayOfWeek(1);
        $course->setStartTime('10:00');
        $course->setEndTime('11:00');
        $course->setCourseType($courseType);

        $cd = new CourseDate();
        $cd->setCourse($course);
        $cd->setDate(new \DateTimeImmutable('+1 day'));
        $cd->setStartTime('10:00');
        $cd->setEndTime('11:00');

        $result = $this->service->bookCourseDate($customer, $cd, $dog);
        self::assertIsString($result);
        self::assertStringContainsString('recurring', $result);
    }

    #[Test]
    public function bookCourseDateRejectsAlreadyBookedDog(): void
    {
        [$customer, $dog] = $this->makeCustomerWithDog();
        $cd = $this->makeRecurringCourseDate();

        $existingBooking = new Booking();
        $this->bookingRepo->method('findActiveByDogAndCourseDate')->willReturn($existingBooking);

        $result = $this->service->bookCourseDate($customer, $cd, $dog);
        self::assertIsString($result);
        self::assertStringContainsString('already has an active booking', $result);
    }

    #[Test]
    public function bookCourseDateRejectsInsufficientCredits(): void
    {
        [$customer, $dog] = $this->makeCustomerWithDog();
        $cd = $this->makeRecurringCourseDate();

        $this->bookingRepo->method('findActiveByDogAndCourseDate')->willReturn(null);
        $this->creditTransactionRepo->method('getBalance')->willReturn(0);

        $result = $this->service->bookCourseDate($customer, $cd, $dog);
        self::assertIsString($result);
        self::assertStringContainsString('Insufficient credits', $result);
    }

    #[Test]
    public function bookCourseDateRejectsClosedBookingWindow(): void
    {
        [$customer, $dog] = $this->makeCustomerWithDog();
        $cd = $this->makeRecurringCourseDate(dateStr: '-3 days');

        $this->bookingRepo->method('findActiveByDogAndCourseDate')->willReturn(null);
        $this->creditTransactionRepo->method('getBalance')->willReturn(5);

        $result = $this->service->bookCourseDate($customer, $cd, $dog);
        self::assertIsString($result);
        self::assertStringContainsString('no longer be booked', $result);
    }

    #[Test]
    public function bookCourseDateSucceedsAndCreatesBookingAndTransaction(): void
    {
        [$customer, $dog] = $this->makeCustomerWithDog();
        $cd = $this->makeRecurringCourseDate(dateStr: '+1 day');

        $this->bookingRepo->method('findActiveByDogAndCourseDate')->willReturn(null);
        $this->creditTransactionRepo->method('getBalance')->willReturn(3);

        $persisted = [];
        $this->em->expects(self::exactly(2))
            ->method('persist')
            ->willReturnCallback(function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            });
        $this->em->expects(self::once())->method('flush');

        $result = $this->service->bookCourseDate($customer, $cd, $dog);
        self::assertInstanceOf(Booking::class, $result);
        self::assertSame($customer, $result->getCustomer());
        self::assertSame($dog, $result->getDog());
        self::assertSame($cd, $result->getCourseDate());

        self::assertCount(2, $persisted);
        $tx = $persisted[0];
        self::assertInstanceOf(CreditTransaction::class, $tx);
        self::assertSame(-1, $tx->getAmount());
        self::assertSame(CreditTransactionType::BOOKING, $tx->getType());
    }

    #[Test]
    public function refundBookingsForCancelledCourseDateRefundsAllActiveBookings(): void
    {
        [$customer, $dog] = $this->makeCustomerWithDog();
        $cd = $this->makeRecurringCourseDate();

        $booking1 = new Booking();
        $booking1->setCustomer($customer);
        $booking1->setDog($dog);
        $booking1->setCourseDate($cd);
        $cd->getBookings()->add($booking1);

        $dog2 = new Dog();
        $dog2->setName('Bella');
        $dog2->setCustomer($customer);
        $booking2 = new Booking();
        $booking2->setCustomer($customer);
        $booking2->setDog($dog2);
        $booking2->setCourseDate($cd);
        $cd->getBookings()->add($booking2);

        $alreadyCancelled = new Booking();
        $alreadyCancelled->setCustomer($customer);
        $alreadyCancelled->setDog($dog);
        $alreadyCancelled->setCourseDate($cd);
        $alreadyCancelled->setCancelledAt(new \DateTimeImmutable());
        $cd->getBookings()->add($alreadyCancelled);

        $persisted = [];
        $this->em->expects(self::exactly(2))
            ->method('persist')
            ->willReturnCallback(function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            });
        $this->em->expects(self::once())->method('flush');

        $count = $this->service->refundBookingsForCancelledCourseDate($cd);

        self::assertSame(2, $count);
        self::assertNull($booking1->getCancelledAt(), 'Booking should remain active for traceability');
        self::assertNull($booking2->getCancelledAt(), 'Booking should remain active for traceability');

        self::assertCount(2, $persisted);
        foreach ($persisted as $tx) {
            self::assertInstanceOf(CreditTransaction::class, $tx);
            self::assertSame(1, $tx->getAmount());
            self::assertSame(CreditTransactionType::CANCELLATION, $tx->getType());
            self::assertStringContainsString('Cancelled by dog school', $tx->getDescription());
        }
    }

    #[Test]
    public function refundBookingsForCancelledCourseDateReturnsZeroWhenNoActiveBookings(): void
    {
        $cd = $this->makeRecurringCourseDate();

        $this->em->expects(self::never())->method('persist');
        $this->em->expects(self::never())->method('flush');

        $count = $this->service->refundBookingsForCancelledCourseDate($cd);
        self::assertSame(0, $count);
    }

    #[Test]
    public function cancelBookingRejectsDogNotBelongingToCustomer(): void
    {
        [$customer] = $this->makeCustomerWithDog();
        $otherCustomer = new Customer();
        $otherCustomer->setName('Other');
        $otherCustomer->setEmail('other@example.com');
        $otherCustomer->setPassword('x');
        $otherDog = new Dog();
        $otherDog->setName('Fido');
        $otherDog->setCustomer($otherCustomer);

        $cd = $this->makeRecurringCourseDate();

        $result = $this->service->cancelBooking($customer, $cd, $otherDog);
        self::assertIsString($result);
        self::assertStringContainsString('does not belong', $result);
    }

    #[Test]
    public function cancelBookingRejectsWhenNoActiveBooking(): void
    {
        [$customer, $dog] = $this->makeCustomerWithDog();
        $cd = $this->makeRecurringCourseDate();

        $this->bookingRepo->method('findActiveByDogAndCourseDate')->willReturn(null);

        $result = $this->service->cancelBooking($customer, $cd, $dog);
        self::assertIsString($result);
        self::assertStringContainsString('No active booking', $result);
    }

    #[Test]
    public function cancelBookingRejectsClosedBookingWindow(): void
    {
        [$customer, $dog] = $this->makeCustomerWithDog();
        $cd = $this->makeRecurringCourseDate(dateStr: '-3 days');

        $existingBooking = new Booking();
        $existingBooking->setCustomer($customer);
        $existingBooking->setDog($dog);
        $existingBooking->setCourseDate($cd);
        $this->bookingRepo->method('findActiveByDogAndCourseDate')->willReturn($existingBooking);

        $result = $this->service->cancelBooking($customer, $cd, $dog);
        self::assertIsString($result);
        self::assertStringContainsString('no longer be cancelled', $result);
    }

    #[Test]
    public function cancelBookingSucceedsAndRefundsCredit(): void
    {
        [$customer, $dog] = $this->makeCustomerWithDog();
        $cd = $this->makeRecurringCourseDate(dateStr: '+1 day');

        $existingBooking = new Booking();
        $existingBooking->setCustomer($customer);
        $existingBooking->setDog($dog);
        $existingBooking->setCourseDate($cd);
        $this->bookingRepo->method('findActiveByDogAndCourseDate')->willReturn($existingBooking);

        $persisted = [];
        $this->em->expects(self::exactly(2))
            ->method('persist')
            ->willReturnCallback(function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            });
        $this->em->expects(self::once())->method('flush');

        $result = $this->service->cancelBooking($customer, $cd, $dog);
        self::assertInstanceOf(Booking::class, $result);
        self::assertNotNull($result->getCancelledAt());

        $tx = $persisted[0];
        self::assertInstanceOf(CreditTransaction::class, $tx);
        self::assertSame(1, $tx->getAmount());
        self::assertSame(CreditTransactionType::CANCELLATION, $tx->getType());
    }

    #[Test]
    public function grantWeeklyCreditsSkipsIfAlreadyGranted(): void
    {
        $contract = new Contract();
        $contract->setCoursesPerWeek(2);

        $customer = new Customer();
        $customer->setName('C');
        $customer->setEmail('c@example.com');
        $customer->setPassword('x');
        $contract->setCustomer($customer);

        $this->creditTransactionRepo->method('weeklyGrantExists')->willReturn(true);

        $result = $this->service->grantWeeklyCredits($contract, '2026-W12');
        self::assertNull($result);
    }

    #[Test]
    public function grantWeeklyCreditsReturnsNullWhenNoCustomer(): void
    {
        $contract = new Contract();
        $contract->setCoursesPerWeek(2);

        $this->creditTransactionRepo->method('weeklyGrantExists')->willReturn(false);

        $result = $this->service->grantWeeklyCredits($contract, '2026-W12');
        self::assertNull($result);
    }

    #[Test]
    public function grantWeeklyCreditsCreatesTransaction(): void
    {
        $customer = new Customer();
        $customer->setName('C');
        $customer->setEmail('c@example.com');
        $customer->setPassword('x');

        $contract = new Contract();
        $contract->setCustomer($customer);
        $contract->setCoursesPerWeek(3);
        $contract->setPrice('100');
        $contract->setStartDate(new \DateTimeImmutable('2026-01-01'));
        $contract->setEndDate(new \DateTimeImmutable('2027-01-01'));

        $this->creditTransactionRepo->method('weeklyGrantExists')->willReturn(false);
        $this->creditTransactionRepo->expects(self::once())->method('save');

        $result = $this->service->grantWeeklyCredits($contract, '2026-W12');
        self::assertInstanceOf(CreditTransaction::class, $result);
        self::assertSame(3, $result->getAmount());
        self::assertSame(CreditTransactionType::WEEKLY_GRANT, $result->getType());
        self::assertSame('2026-W12', $result->getWeekRef());
    }

    #[Test]
    public function applyContractCancellationCreditsReversesPreviousGrants(): void
    {
        $customer = new Customer();
        $customer->setName('C');
        $customer->setEmail('c@example.com');
        $customer->setPassword('x');

        $contract = new Contract();
        $contract->setCustomer($customer);
        $contract->setCoursesPerWeek(2);
        $contract->setPrice('99');
        $contract->setStartDate(new \DateTimeImmutable('2025-01-01'));
        $contract->setEndDate(new \DateTimeImmutable('2026-01-01'));

        $this->creditTransactionRepo->method('sumWeeklyGrantAmountForContract')->willReturn(10);
        $this->creditTransactionRepo->method('getBalance')->willReturn(5);

        $persisted = [];
        $this->em->expects(self::once())
            ->method('persist')
            ->willReturnCallback(function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            });
        $this->em->expects(self::once())->method('flush');

        $this->service->applyContractCancellationCredits($contract);

        self::assertCount(1, $persisted);
        $tx = $persisted[0];
        self::assertInstanceOf(CreditTransaction::class, $tx);
        self::assertSame(-10, $tx->getAmount());
        self::assertSame(CreditTransactionType::MANUAL_ADJUSTMENT, $tx->getType());
    }

    #[Test]
    public function applyContractCancellationCreditsFixesNegativeBalance(): void
    {
        $customer = new Customer();
        $customer->setName('C');
        $customer->setEmail('c@example.com');
        $customer->setPassword('x');

        $contract = new Contract();
        $contract->setCustomer($customer);
        $contract->setCoursesPerWeek(2);
        $contract->setPrice('99');
        $contract->setStartDate(new \DateTimeImmutable('2025-01-01'));
        $contract->setEndDate(new \DateTimeImmutable('2026-01-01'));

        $this->creditTransactionRepo->method('sumWeeklyGrantAmountForContract')->willReturn(10);
        $this->creditTransactionRepo->method('getBalance')->willReturn(-3);

        $persisted = [];
        $this->em->expects(self::exactly(2))
            ->method('persist')
            ->willReturnCallback(function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            });
        $this->em->expects(self::exactly(2))->method('flush');

        $this->service->applyContractCancellationCredits($contract);

        self::assertCount(2, $persisted);
        $correctionTx = $persisted[1];
        self::assertInstanceOf(CreditTransaction::class, $correctionTx);
        self::assertSame(3, $correctionTx->getAmount());
    }

    #[Test]
    public function applyContractCancellationCreditsSkipsNonPerpetualContract(): void
    {
        $customer = new Customer();
        $customer->setName('C');
        $customer->setEmail('c@example.com');
        $customer->setPassword('x');

        $contract = $this->createMock(Contract::class);
        $contract->method('getCustomer')->willReturn($customer);
        $contract->method('getType')->willReturn(ContractType::PERPETUAL);

        $this->creditTransactionRepo->method('sumWeeklyGrantAmountForContract')->willReturn(0);
        $this->em->expects(self::never())->method('persist');

        $this->service->applyContractCancellationCredits($contract);
    }

    #[Test]
    public function getBalanceDelegatesToRepository(): void
    {
        $customer = new Customer();
        $customer->setName('C');
        $customer->setEmail('c@example.com');
        $customer->setPassword('x');

        $this->creditTransactionRepo->method('getBalance')->willReturn(42);
        self::assertSame(42, $this->service->getBalance($customer));
    }

    #[Test]
    public function getHistoryDelegatesToRepository(): void
    {
        $customer = new Customer();
        $customer->setName('C');
        $customer->setEmail('c@example.com');
        $customer->setPassword('x');

        $tx = new CreditTransaction();
        $this->creditTransactionRepo->method('findByCustomer')->willReturn([$tx]);
        self::assertCount(1, $this->service->getHistory($customer));
    }
}
