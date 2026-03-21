<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Customer;

use App\Entity\Contract;
use App\Entity\Course;
use App\Entity\CourseDate;
use App\Entity\CourseType;
use App\Entity\CreditTransaction;
use App\Entity\Dog;
use App\Enum\ContractState;
use App\Enum\CreditTransactionType;
use App\Repository\CourseDateRepository;
use App\Repository\CourseRepository;
use App\Repository\CourseTypeRepository;
use App\Repository\CreditTransactionRepository;
use App\Repository\DogRepository;
use App\Tests\Helper\ApiTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CalendarControllerTest extends WebTestCase
{
    private function ensureCourseType(): CourseType
    {
        $container = static::getContainer();
        $courseTypeRepo = $container->get(CourseTypeRepository::class);
        $courseType = $courseTypeRepo->findByCode('MH');
        if ($courseType === null) {
            $courseType = new CourseType();
            $courseType->setCode('MH');
            $courseType->setName('Mensch-Hund');
            $courseTypeRepo->save($courseType);
        }

        return $courseType;
    }

    private function seedCourseDateForBooking(string $dateStr): CourseDate
    {
        $courseType = $this->ensureCourseType();
        $container = static::getContainer();

        $courseRepo = $container->get(CourseRepository::class);
        $course = new Course();
        $course->setDayOfWeek((int) (new \DateTimeImmutable($dateStr))->format('N'));
        $course->setStartTime('10:00');
        $course->setEndTime('11:00');
        $course->setCourseType($courseType);
        $courseRepo->save($course);

        $cdRepo = $container->get(CourseDateRepository::class);
        $cd = new CourseDate();
        $cd->setCourse($course);
        $cd->setDate(new \DateTimeImmutable($dateStr));
        $cd->setStartTime('10:00');
        $cd->setEndTime('11:00');
        $cdRepo->save($cd);

        return $cd;
    }

    public function testListCalendarRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/api/customer/calendar');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListCalendarReturnsCourseDates(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createCustomerAndLogin();

        $helper->customerRequest(Request::METHOD_GET, '/api/customer/calendar', $token);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('from', $data);
        self::assertArrayHasKey('to', $data);
    }

    public function testListCalendarWithFromParameter(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createCustomerAndLogin();

        $from = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $helper->customerRequest(Request::METHOD_GET, '/api/customer/calendar?from=' . $from . '&days=7', $token);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame($from, $data['from']);
    }

    public function testBookCourseDateSuccess(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $container = static::getContainer();
        $dogRepo = $container->get(DogRepository::class);
        $dog = new Dog();
        $dog->setCustomer($customer);
        $dog->setName('BookDog');
        $dogRepo->save($dog);

        $txRepo = $container->get(CreditTransactionRepository::class);
        $tx = new CreditTransaction();
        $tx->setCustomer($customer);
        $tx->setAmount(5);
        $tx->setType(CreditTransactionType::MANUAL_ADJUSTMENT);
        $tx->setDescription('Seed credits for test');
        $txRepo->save($tx);

        $cd = $this->seedCourseDateForBooking((new \DateTimeImmutable('+2 days'))->format('Y-m-d'));

        $helper->customerRequest(Request::METHOD_POST, '/api/customer/calendar/course-dates/' . $cd->getId() . '/book', $token, json_encode([
            'dogId' => $dog->getId(),
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertArrayHasKey('booking', $data);
        self::assertArrayHasKey('creditBalance', $data);
        self::assertSame(4, $data['creditBalance']);
    }

    public function testBookCourseDateFailsWithoutCredits(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $container = static::getContainer();
        $dogRepo = $container->get(DogRepository::class);
        $dog = new Dog();
        $dog->setCustomer($customer);
        $dog->setName('NoCreditDog');
        $dogRepo->save($dog);

        $cd = $this->seedCourseDateForBooking((new \DateTimeImmutable('+2 days'))->format('Y-m-d'));

        $helper->customerRequest(Request::METHOD_POST, '/api/customer/calendar/course-dates/' . $cd->getId() . '/book', $token, json_encode([
            'dogId' => $dog->getId(),
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertStringContainsString('Insufficient', $data['error']);
    }

    public function testCancelBookingSuccess(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $container = static::getContainer();
        $dogRepo = $container->get(DogRepository::class);
        $dog = new Dog();
        $dog->setCustomer($customer);
        $dog->setName('CancelDog');
        $dogRepo->save($dog);

        $txRepo = $container->get(CreditTransactionRepository::class);
        $tx = new CreditTransaction();
        $tx->setCustomer($customer);
        $tx->setAmount(5);
        $tx->setType(CreditTransactionType::MANUAL_ADJUSTMENT);
        $tx->setDescription('Seed');
        $txRepo->save($tx);

        $cd = $this->seedCourseDateForBooking((new \DateTimeImmutable('+2 days'))->format('Y-m-d'));

        $helper->customerRequest(Request::METHOD_POST, '/api/customer/calendar/course-dates/' . $cd->getId() . '/book', $token, json_encode([
            'dogId' => $dog->getId(),
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $helper->customerRequest(Request::METHOD_DELETE, '/api/customer/calendar/course-dates/' . $cd->getId() . '/book?dogId=' . $dog->getId(), $token);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame(5, $data['creditBalance']);
    }

    public function testCancelBookingMissingDogIdParam(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createCustomerAndLogin();

        $cd = $this->seedCourseDateForBooking((new \DateTimeImmutable('+2 days'))->format('Y-m-d'));

        $helper->customerRequest(Request::METHOD_DELETE, '/api/customer/calendar/course-dates/' . $cd->getId() . '/book', $token);
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testBookCourseDateNotFound(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createCustomerAndLogin();

        $helper->customerRequest(Request::METHOD_POST, '/api/customer/calendar/course-dates/f47ac10b-58cc-4372-a567-0e02b2c3d479/book', $token, json_encode([
            'dogId' => 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d',
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
