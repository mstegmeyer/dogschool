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
use App\Entity\HotelBooking;
use App\Entity\Notification;
use App\Entity\PushDevice;
use App\Entity\User;
use App\Enum\ContractState;
use App\Enum\ContractType;
use App\Enum\CreditTransactionType;
use App\Service\ApiNormalizer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ApiNormalizerTest extends TestCase
{
    private ApiNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new ApiNormalizer();
    }

    #[Test]
    public function normalizeCustomerReturnsExpectedKeys(): void
    {
        $customer = new Customer();
        $customer->setName('Test');
        $customer->setEmail('test@example.com');
        $customer->setPassword('hashed');
        $data = $this->normalizer->normalizeCustomer($customer);
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('name', $data);
        self::assertArrayHasKey('email', $data);
        self::assertArrayHasKey('createdAt', $data);
        self::assertArrayHasKey('address', $data);
        self::assertArrayHasKey('bankAccount', $data);
        self::assertSame('Test', $data['name']);
        self::assertSame('test@example.com', $data['email']);
    }

    #[Test]
    public function normalizeDogReturnsExpectedKeys(): void
    {
        $dog = new Dog();
        $dog->setName('Rex');
        $dog->setColor('brown');
        $dog->setShoulderHeightCm(47);
        $data = $this->normalizer->normalizeDog($dog);
        self::assertArrayHasKey('id', $data);
        self::assertSame('Rex', $data['name']);
        self::assertSame('brown', $data['color']);
        self::assertSame(47, $data['shoulderHeightCm']);
    }

    #[Test]
    public function normalizeContractReturnsStateAndType(): void
    {
        $contract = new Contract();
        $contract->setState(ContractState::REQUESTED);
        $contract->setType(ContractType::PERPETUAL);
        $contract->setPrice('99.00');
        $contract->setStartDate(new \DateTimeImmutable('2025-01-01'));
        $contract->setEndDate(new \DateTimeImmutable('2026-01-01'));
        $data = $this->normalizer->normalizeContract($contract);
        self::assertSame('REQUESTED', $data['state']);
        self::assertSame('PERPETUAL', $data['type']);
        self::assertSame('99.00', $data['price']);
        self::assertSame('99.00', $data['priceMonthly']);
    }

    #[Test]
    public function normalizeContractBackfillsEmptyPricingSnapshot(): void
    {
        $contract = new Contract();
        $contract->setState(ContractState::REQUESTED);
        $contract->setType(ContractType::PERPETUAL);
        $contract->setPrice('99.00');
        $contract->setStartDate(new \DateTimeImmutable('2025-01-01'));
        $contract->setEndDate(new \DateTimeImmutable('2026-01-01'));

        $data = $this->normalizer->normalizeContract($contract);

        self::assertSame('contract', $data['pricingSnapshot']['type']);
        self::assertSame([], $data['pricingSnapshot']['lineItems']);
    }

    #[Test]
    public function normalizeHotelBookingBackfillsEmptyPricingSnapshot(): void
    {
        $booking = new HotelBooking();
        $booking->setStartAt(new \DateTimeImmutable('2026-04-01 08:00'));
        $booking->setEndAt(new \DateTimeImmutable('2026-04-01 18:00'));

        $data = $this->normalizer->normalizeHotelBooking($booking);

        self::assertSame('hotelBooking', $data['pricingSnapshot']['type']);
        self::assertSame([], $data['pricingSnapshot']['lineItems']);
        self::assertFalse($data['includesSingleRoom']);
        self::assertSame('0.00', $data['singleRoomPrice']);
    }

    #[Test]
    public function normalizeCourseReturnsTypeAndLevel(): void
    {
        $courseType = new CourseType();
        $courseType->setCode('MH');
        $courseType->setName('Mensch-Hund');
        $trainer = new User();
        $trainer->setUsername('lea');
        $trainer->setFullName('Lea');
        $course = new Course();
        $course->setDayOfWeek(1);
        $course->setStartTime('09:00');
        $course->setEndTime('10:00');
        $course->setCourseType($courseType);
        $course->setLevel(2);
        $course->setTrainer($trainer);
        $data = $this->normalizer->normalizeCourse($course);
        self::assertSame(1, $data['dayOfWeek']);
        self::assertSame('MH', $data['type']['code']);
        self::assertSame('Mensch-Hund', $data['type']['name']);
        self::assertSame('RECURRING', $data['type']['recurrenceKind']);
        self::assertSame(2, $data['level']);
        self::assertSame('Lea', $data['trainer']['fullName']);
    }

    #[Test]
    public function normalizeNotificationReturnsTitleAndMessage(): void
    {
        $notification = new Notification();
        $notification->setTitle('Title');
        $notification->setMessage('Body');
        $author = new User();
        $author->setFullName('Admin');
        $notification->setAuthor($author);
        $data = $this->normalizer->normalizeNotification($notification);
        self::assertSame('Title', $data['title']);
        self::assertSame('Body', $data['message']);
        self::assertSame('Admin', $data['authorName']);
        self::assertNull($data['pinnedUntil']);
        self::assertFalse($data['isPinned']);
    }

    #[Test]
    public function normalizeNotificationWithPinnedUntilInFuture(): void
    {
        $notification = new Notification();
        $notification->setTitle('Vacation');
        $notification->setMessage('We are closed next week');
        $notification->setPinnedUntil(new \DateTimeImmutable('+30 days'));
        $data = $this->normalizer->normalizeNotification($notification);
        self::assertNotNull($data['pinnedUntil']);
        self::assertTrue($data['isPinned']);
    }

    #[Test]
    public function normalizeNotificationWithExpiredPin(): void
    {
        $notification = new Notification();
        $notification->setTitle('Old');
        $notification->setMessage('Expired pin');
        $notification->setPinnedUntil(new \DateTimeImmutable('-1 day'));
        $data = $this->normalizer->normalizeNotification($notification);
        self::assertNotNull($data['pinnedUntil']);
        self::assertFalse($data['isPinned']);
    }

    #[Test]
    public function normalizeNotificationWithNullAuthor(): void
    {
        $notification = new Notification();
        $notification->setTitle('Title');
        $notification->setMessage('Body');
        $data = $this->normalizer->normalizeNotification($notification);
        self::assertSame('Title', $data['title']);
        self::assertNull($data['authorName']);
        self::assertNull($data['authorId']);
    }

    #[Test]
    public function normalizeContractWithNullDogAndCustomer(): void
    {
        $contract = new Contract();
        $contract->setState(ContractState::REQUESTED);
        $contract->setType(ContractType::PERPETUAL);
        $contract->setPrice('0');
        $contract->setStartDate(new \DateTimeImmutable('2025-01-01'));
        $contract->setEndDate(new \DateTimeImmutable('2026-01-01'));
        $data = $this->normalizer->normalizeContract($contract);
        self::assertArrayHasKey('dogId', $data);
        self::assertArrayHasKey('customerId', $data);
        self::assertNull($data['dogId']);
        self::assertNull($data['customerId']);
        self::assertNull($data['dogName']);
        self::assertNull($data['customerName']);
    }

    #[Test]
    public function normalizeContractIncludesDogAndCustomerNames(): void
    {
        $customer = new Customer();
        $customer->setName('Max Mustermann');
        $customer->setEmail('max@example.com');
        $customer->setPassword('x');
        $dog = new Dog();
        $dog->setName('Bello');
        $dog->setCustomer($customer);

        $contract = new Contract();
        $contract->setCustomer($customer);
        $contract->setDog($dog);
        $contract->setState(ContractState::ACTIVE);
        $contract->setType(ContractType::PERPETUAL);
        $contract->setPrice('79.00');
        $contract->setStartDate(new \DateTimeImmutable('2025-01-01'));
        $contract->setEndDate(new \DateTimeImmutable('2026-01-01'));

        $data = $this->normalizer->normalizeContract($contract);
        self::assertSame('Bello', $data['dogName']);
        self::assertSame('Max Mustermann', $data['customerName']);
    }

    #[Test]
    public function normalizeNotificationIncludesCourseSummary(): void
    {
        $courseType = new CourseType();
        $courseType->setCode('MH');
        $courseType->setName('Mensch & Hund');
        $course = new Course();
        $course->setDayOfWeek(3);
        $course->setStartTime('10:00');
        $course->setEndTime('11:00');
        $course->setCourseType($courseType);

        $notification = new Notification();
        $notification->setTitle('Info');
        $notification->setMessage('Text');
        $notification->addCourse($course);
        $author = new User();
        $author->setFullName('Trainer');
        $notification->setAuthor($author);

        $data = $this->normalizer->normalizeNotification($notification);
        self::assertCount(1, $data['courses']);
        self::assertSame('MH', $data['courses'][0]['typeCode']);
        self::assertSame('Mensch & Hund', $data['courses'][0]['typeName']);
        self::assertSame(3, $data['courses'][0]['dayOfWeek']);
        self::assertSame('10:00', $data['courses'][0]['startTime']);
    }

    #[Test]
    public function normalizePushDeviceReturnsExpectedKeys(): void
    {
        $device = (new PushDevice())
            ->setToken('device-token')
            ->setPlatform('web')
            ->setProvider('webpush')
            ->setDeviceName('Safari on Mac');

        $data = $this->normalizer->normalizePushDevice($device);

        self::assertSame($device->getId(), $data['id']);
        self::assertSame('device-token', $data['token']);
        self::assertSame('web', $data['platform']);
        self::assertSame('webpush', $data['provider']);
        self::assertSame('Safari on Mac', $data['deviceName']);
        self::assertSame($device->getCreatedAt()->format(\DateTimeInterface::ATOM), $data['createdAt']);
        self::assertSame($device->getUpdatedAt()->format(\DateTimeInterface::ATOM), $data['updatedAt']);
        self::assertSame($device->getLastSeenAt()->format(\DateTimeInterface::ATOM), $data['lastSeenAt']);
    }

    #[Test]
    public function normalizeCourseDateForAdminIncludesBookings(): void
    {
        $courseType = new CourseType();
        $courseType->setCode('AGI');
        $courseType->setName('Agility');
        $courseTrainer = new User();
        $courseTrainer->setUsername('manuela');
        $courseTrainer->setFullName('Manuela');
        $overrideTrainer = new User();
        $overrideTrainer->setUsername('caro');
        $overrideTrainer->setFullName('Caro');
        $course = new Course();
        $course->setDayOfWeek(1);
        $course->setStartTime('18:00');
        $course->setEndTime('19:00');
        $course->setCourseType($courseType);
        $course->setComment('Nachholstunde');
        $course->setTrainer($courseTrainer);

        $cd = new CourseDate();
        $cd->setCourse($course);
        $cd->setTrainer($overrideTrainer);
        $cd->setDate(new \DateTimeImmutable('2026-03-23'));
        $cd->setStartTime('18:00');
        $cd->setEndTime('19:00');

        $customer = new Customer();
        $customer->setName('Anna');
        $customer->setEmail('anna@example.com');
        $customer->setPassword('x');
        $dog = new Dog();
        $dog->setName('Bella');
        $dog->setCustomer($customer);

        $booking = new Booking();
        $booking->setCustomer($customer);
        $booking->setDog($dog);
        $booking->setCourseDate($cd);
        $cd->getBookings()->add($booking);

        $data = $this->normalizer->normalizeCourseDateForAdmin($cd);
        self::assertCount(1, $data['bookings']);
        self::assertSame('Bella', $data['bookings'][0]['dogName']);
        self::assertSame('Anna', $data['bookings'][0]['customerName']);
        self::assertSame('Nachholstunde', $data['comment']);
        self::assertSame('Caro', $data['trainer']['fullName']);
        self::assertSame('Manuela', $data['courseTrainer']['fullName']);
        self::assertTrue($data['trainerOverridden']);
    }

    #[Test]
    public function normalizeCourseDateForCustomerIncludesOwnBookingsAndSubscriptionState(): void
    {
        $courseType = (new CourseType())
            ->setCode('AGI')
            ->setName('Agility');

        $course = (new Course())
            ->setDayOfWeek(6)
            ->setStartTime('09:00')
            ->setEndTime('10:00')
            ->setCourseType($courseType)
            ->setComment('Nachholstunde');

        $courseDate = (new CourseDate())
            ->setCourse($course)
            ->setDate(new \DateTimeImmutable('+2 days', new \DateTimeZone('Europe/Berlin')))
            ->setStartTime('09:00')
            ->setEndTime('10:00');

        $customer = (new Customer())
            ->setName('Anna')
            ->setEmail('anna@example.com')
            ->setPassword('x')
            ->addSubscribedCourse($course);
        $otherCustomer = (new Customer())
            ->setName('Bob')
            ->setEmail('bob@example.com')
            ->setPassword('x');

        $dog = (new Dog())
            ->setName('Bella')
            ->setCustomer($customer);
        $otherDog = (new Dog())
            ->setName('Rex')
            ->setCustomer($otherCustomer);

        $myBooking = (new Booking())
            ->setCustomer($customer)
            ->setDog($dog)
            ->setCourseDate($courseDate);
        $otherBooking = (new Booking())
            ->setCustomer($otherCustomer)
            ->setDog($otherDog)
            ->setCourseDate($courseDate);

        $courseDate->getBookings()->add($myBooking);
        $courseDate->getBookings()->add($otherBooking);

        $data = $this->normalizer->normalizeCourseDateForCustomer($courseDate, $customer);

        self::assertTrue($data['booked']);
        self::assertTrue($data['subscribed']);
        self::assertFalse($data['bookingWindowClosed']);
        self::assertCount(1, $data['bookings']);
        self::assertSame($myBooking->getId(), $data['bookings'][0]['id']);
        self::assertSame('Bella', $data['bookings'][0]['dogName']);
        self::assertSame('Nachholstunde', $data['comment']);
    }

    #[Test]
    public function normalizeCreditTransactionReturnsExpectedKeys(): void
    {
        $courseDate = new CourseDate();
        $contract = new Contract();
        $transaction = (new CreditTransaction())
            ->setAmount(3)
            ->setType(CreditTransactionType::WEEKLY_GRANT)
            ->setDescription('Weekly grant')
            ->setCourseDate($courseDate)
            ->setContract($contract)
            ->setWeekRef('2026-W14');

        $data = $this->normalizer->normalizeCreditTransaction($transaction);

        self::assertSame($transaction->getId(), $data['id']);
        self::assertSame(3, $data['amount']);
        self::assertSame('WEEKLY_GRANT', $data['type']);
        self::assertSame('Weekly grant', $data['description']);
        self::assertSame($courseDate->getId(), $data['courseDateId']);
        self::assertSame($contract->getId(), $data['contractId']);
        self::assertSame('2026-W14', $data['weekRef']);
        self::assertSame($transaction->getCreatedAt()->format(\DateTimeInterface::ATOM), $data['createdAt']);
    }

    #[Test]
    public function normalizeBookingIncludesNestedCourseDateAndCancellationState(): void
    {
        $courseType = (new CourseType())
            ->setCode('MH')
            ->setName('Mensch-Hund');
        $course = (new Course())
            ->setDayOfWeek(3)
            ->setStartTime('18:00')
            ->setEndTime('19:00')
            ->setCourseType($courseType);
        $courseDate = (new CourseDate())
            ->setCourse($course)
            ->setDate(new \DateTimeImmutable('2026-04-15'))
            ->setStartTime('18:00')
            ->setEndTime('19:00');
        $customer = (new Customer())
            ->setName('Anna')
            ->setEmail('anna@example.com')
            ->setPassword('x');
        $dog = (new Dog())
            ->setName('Bella')
            ->setCustomer($customer);

        $booking = (new Booking())
            ->setCustomer($customer)
            ->setDog($dog)
            ->setCourseDate($courseDate)
            ->setCancelledAt(new \DateTimeImmutable('2026-04-14T10:00:00+02:00'));

        $data = $this->normalizer->normalizeBooking($booking);

        self::assertSame($booking->getId(), $data['id']);
        self::assertSame($customer->getId(), $data['customerId']);
        self::assertSame($dog->getId(), $data['dogId']);
        self::assertSame($courseDate->getId(), $data['courseDateId']);
        self::assertFalse($data['active']);
        self::assertSame('MH', $data['courseDate']['courseType']['code']);
        self::assertSame($booking->getCancelledAt()?->format(\DateTimeInterface::ATOM), $data['cancelledAt']);
    }

    #[Test]
    public function violationsToArrayMapsPropertyPathToMessage(): void
    {
        $violation = $this->createMock(\Symfony\Component\Validator\ConstraintViolationInterface::class);
        $violation->method('getPropertyPath')->willReturn('email');
        $violation->method('getMessage')->willReturn('This value is not valid.');
        $list = new \Symfony\Component\Validator\ConstraintViolationList([$violation]);
        $data = $this->normalizer->violationsToArray($list);
        self::assertSame(['email' => 'This value is not valid.'], $data);
    }
}
