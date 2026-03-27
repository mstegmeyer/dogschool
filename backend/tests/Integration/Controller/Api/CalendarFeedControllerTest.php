<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api;

use App\Entity\Booking;
use App\Entity\Course;
use App\Entity\CourseDate;
use App\Entity\CourseType;
use App\Entity\CreditTransaction;
use App\Entity\Customer;
use App\Entity\Dog;
use App\Enum\CreditTransactionType;
use App\Repository\BookingRepository;
use App\Repository\CourseDateRepository;
use App\Repository\CourseRepository;
use App\Repository\CourseTypeRepository;
use App\Repository\CreditTransactionRepository;
use App\Repository\DogRepository;
use App\Tests\Helper\ApiTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CalendarFeedControllerTest extends WebTestCase
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

    private function seedCourseDate(string $dateStr): CourseDate
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

        $courseDateRepo = $container->get(CourseDateRepository::class);
        $courseDate = new CourseDate();
        $courseDate->setCourse($course);
        $courseDate->setDate(new \DateTimeImmutable($dateStr));
        $courseDate->setStartTime('10:00');
        $courseDate->setEndTime('11:00');
        $courseDateRepo->save($courseDate);

        return $courseDate;
    }

    private function seedDogAndCredits(Customer $customer): Dog
    {
        $container = static::getContainer();

        $dogRepo = $container->get(DogRepository::class);
        $dog = new Dog();
        $dog->setCustomer($customer);
        $dog->setName('Luna');
        $dogRepo->save($dog);

        $txRepo = $container->get(CreditTransactionRepository::class);
        $tx = new CreditTransaction();
        $tx->setCustomer($customer);
        $tx->setAmount(5);
        $tx->setType(CreditTransactionType::MANUAL_ADJUSTMENT);
        $tx->setDescription('Seed credits for calendar feed test');
        $txRepo->save($tx);

        return $dog;
    }

    public function testCalendarFeedReturnsNotFoundForUnknownToken(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/api/calendar/customer/00000000-0000-0000-0000-000000000000.ics');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCalendarFeedReturnsBookedCourseAsIcsEvent(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $dog = $this->seedDogAndCredits($customer);
        $courseDate = $this->seedCourseDate((new \DateTimeImmutable('+2 days'))->format('Y-m-d'));

        $helper->customerRequest(
            Request::METHOD_POST,
            '/api/customer/calendar/course-dates/'.$courseDate->getId().'/book',
            $token,
            json_encode(['dogId' => $dog->getId()]),
        );
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $client->request(Request::METHOD_GET, '/api/calendar/customer/'.$customer->getCalendarFeedToken().'.ics');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'text/calendar; charset=utf-8');

        $content = $client->getResponse()->getContent() ?: '';
        self::assertStringContainsString('BEGIN:VCALENDAR', $content);
        self::assertStringContainsString('BEGIN:VEVENT', $content);
        self::assertStringContainsString('SUMMARY:Mensch-Hund (Luna)', $content);
        self::assertStringContainsString('STATUS:CONFIRMED', $content);
    }

    public function testCalendarFeedIncludesPastActiveBookings(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['customer' => $customer] = $helper->createCustomerAndLogin();

        $dog = $this->seedDogAndCredits($customer);
        $courseDate = $this->seedCourseDate((new \DateTimeImmutable('-3 days'))->format('Y-m-d'));

        $container = static::getContainer();
        $bookingRepo = $container->get(BookingRepository::class);
        $booking = new Booking();
        $booking->setCustomer($customer);
        $booking->setDog($dog);
        $booking->setCourseDate($courseDate);
        $bookingRepo->save($booking);

        $client->request(Request::METHOD_GET, '/api/calendar/customer/'.$customer->getCalendarFeedToken().'.ics');

        self::assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent() ?: '';
        self::assertStringContainsString('SUMMARY:Mensch-Hund (Luna)', $content);
        self::assertStringContainsString($courseDate->getDate()->format('Ymd'), $content);
    }

    public function testCalendarFeedMarksCancelledCourseDatesAsCancelled(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $dog = $this->seedDogAndCredits($customer);
        $courseDate = $this->seedCourseDate((new \DateTimeImmutable('+3 days'))->format('Y-m-d'));

        $helper->customerRequest(
            Request::METHOD_POST,
            '/api/customer/calendar/course-dates/'.$courseDate->getId().'/book',
            $token,
            json_encode(['dogId' => $dog->getId()]),
        );
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $container = static::getContainer();
        $courseDateRepo = $container->get(CourseDateRepository::class);
        $managedCourseDate = $courseDateRepo->find($courseDate->getId());
        self::assertInstanceOf(CourseDate::class, $managedCourseDate);
        $managedCourseDate->setCancelled(true);
        $courseDateRepo->save($managedCourseDate);

        $client->request(Request::METHOD_GET, '/api/calendar/customer/'.$customer->getCalendarFeedToken().'.ics');

        self::assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent() ?: '';
        self::assertStringContainsString('STATUS:CANCELLED', $content);
        self::assertStringContainsString('SUMMARY:Abgesagt: Mensch-Hund (Luna)', $content);
    }
}
