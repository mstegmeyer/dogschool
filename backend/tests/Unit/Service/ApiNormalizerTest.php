<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Booking;
use App\Entity\Contract;
use App\Entity\Course;
use App\Entity\CourseDate;
use App\Entity\CourseType;
use App\Entity\Customer;
use App\Entity\Dog;
use App\Entity\Notification;
use App\Entity\User;
use App\Enum\ContractState;
use App\Enum\ContractType;
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
        $data = $this->normalizer->normalizeDog($dog);
        self::assertArrayHasKey('id', $data);
        self::assertSame('Rex', $data['name']);
        self::assertSame('brown', $data['color']);
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
    public function normalizeCourseReturnsTypeAndLevel(): void
    {
        $courseType = new CourseType();
        $courseType->setCode('MH');
        $courseType->setName('Mensch-Hund');
        $course = new Course();
        $course->setDayOfWeek(1);
        $course->setStartTime('09:00');
        $course->setEndTime('10:00');
        $course->setCourseType($courseType);
        $course->setLevel(2);
        $data = $this->normalizer->normalizeCourse($course);
        self::assertSame(1, $data['dayOfWeek']);
        self::assertSame('MH', $data['type']['code']);
        self::assertSame('Mensch-Hund', $data['type']['name']);
        self::assertSame('RECURRING', $data['type']['recurrenceKind']);
        self::assertSame(2, $data['level']);
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
    public function normalizeCourseDateForAdminIncludesBookings(): void
    {
        $courseType = new CourseType();
        $courseType->setCode('AGI');
        $courseType->setName('Agility');
        $course = new Course();
        $course->setDayOfWeek(1);
        $course->setStartTime('18:00');
        $course->setEndTime('19:00');
        $course->setCourseType($courseType);

        $cd = new CourseDate();
        $cd->setCourse($course);
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
