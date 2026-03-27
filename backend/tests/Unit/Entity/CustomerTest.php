<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Course;
use App\Entity\CourseType;
use App\Entity\Customer;
use App\Entity\Dog;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CustomerTest extends TestCase
{
    #[Test]
    public function getUserIdentifierReturnsEmail(): void
    {
        $customer = new Customer();
        $customer->setEmail('hello@example.com');
        $customer->setName('Test');
        $customer->setPassword('x');

        self::assertSame('hello@example.com', $customer->getUserIdentifier());
    }

    #[Test]
    public function getRolesContainsRoleCustomer(): void
    {
        $customer = new Customer();
        self::assertContains('ROLE_CUSTOMER', $customer->getRoles());
    }

    #[Test]
    public function addDogAddsAndSetsBidirectionalRelation(): void
    {
        $customer = new Customer();
        $customer->setName('Test');
        $customer->setEmail('t@example.com');
        $customer->setPassword('x');

        $dog = new Dog();
        $dog->setName('Rex');

        $customer->addDog($dog);

        self::assertCount(1, $customer->getDogs());
        self::assertSame($customer, $dog->getCustomer());
    }

    #[Test]
    public function removeDogRemovesAndClearsRelation(): void
    {
        $customer = new Customer();
        $customer->setName('Test');
        $customer->setEmail('t@example.com');
        $customer->setPassword('x');

        $dog = new Dog();
        $dog->setName('Rex');
        $customer->addDog($dog);
        self::assertCount(1, $customer->getDogs());

        $customer->removeDog($dog);
        self::assertCount(0, $customer->getDogs());
    }

    #[Test]
    public function addSubscribedCourseDoesNotDuplicate(): void
    {
        $customer = new Customer();
        $customer->setName('Test');
        $customer->setEmail('t@example.com');
        $customer->setPassword('x');

        $courseType = new CourseType();
        $courseType->setCode('MH');
        $courseType->setName('Mensch-Hund');

        $course = new Course();
        $course->setDayOfWeek(1);
        $course->setStartTime('10:00');
        $course->setEndTime('11:00');
        $course->setCourseType($courseType);

        $customer->addSubscribedCourse($course);
        $customer->addSubscribedCourse($course);

        self::assertCount(1, $customer->getSubscribedCourses());
    }

    #[Test]
    public function removeSubscribedCourseRemovesSuccessfully(): void
    {
        $customer = new Customer();
        $customer->setName('Test');
        $customer->setEmail('t@example.com');
        $customer->setPassword('x');

        $courseType = new CourseType();
        $courseType->setCode('MH');
        $courseType->setName('Mensch-Hund');

        $course = new Course();
        $course->setDayOfWeek(1);
        $course->setStartTime('10:00');
        $course->setEndTime('11:00');
        $course->setCourseType($courseType);

        $customer->addSubscribedCourse($course);
        $customer->removeSubscribedCourse($course);

        self::assertCount(0, $customer->getSubscribedCourses());
    }

    #[Test]
    public function defaultEmbeddablesAreInitialized(): void
    {
        $customer = new Customer();
        self::assertNotNull($customer->getAddress());
        self::assertNotNull($customer->getBankAccount());
        self::assertNotSame('', $customer->getCalendarFeedToken());
        self::assertNull($customer->getAddress()->getStreet());
        self::assertNull($customer->getBankAccount()->getIban());
    }
}
