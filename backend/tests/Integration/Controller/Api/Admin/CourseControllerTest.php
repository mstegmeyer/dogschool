<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Admin;

use App\Entity\Course;
use App\Entity\CourseDate;
use App\Entity\CourseType;
use App\Entity\CreditTransaction;
use App\Entity\Dog;
use App\Entity\User;
use App\Enum\CreditTransactionType;
use App\Repository\BookingRepository;
use App\Repository\CourseDateRepository;
use App\Repository\CourseRepository;
use App\Repository\CourseTypeRepository;
use App\Repository\CreditTransactionRepository;
use App\Repository\CustomerRepository;
use App\Repository\DogRepository;
use App\Repository\UserRepository;
use App\Tests\Helper\ApiTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CourseControllerTest extends WebTestCase
{
    private function ensureCourseType(string $code = 'JUHU', string $name = 'Junghunde'): CourseType
    {
        $container = static::getContainer();
        $courseTypeRepo = $container->get(CourseTypeRepository::class);
        $courseType = $courseTypeRepo->findByCode($code);
        if ($courseType === null) {
            $courseType = new CourseType();
            $courseType->setCode($code);
            $courseType->setName($name);
            $courseTypeRepo->save($courseType);
        }

        return $courseType;
    }

    private function seedCourse(CourseType $courseType, string $dateStr): Course
    {
        $course = new Course();
        $course->setDayOfWeek((int) (new \DateTimeImmutable($dateStr))->format('N'));
        $course->setStartTime('10:00');
        $course->setEndTime('11:00');
        $course->setCourseType($courseType);
        $course->setLevel(1);

        static::getContainer()->get(CourseRepository::class)->save($course);

        return $course;
    }

    private function seedCourseDate(Course $course, string $dateStr): CourseDate
    {
        $courseDate = new CourseDate();
        $courseDate->setCourse($course);
        $courseDate->setDate(new \DateTimeImmutable($dateStr));
        $courseDate->setStartTime($course->getStartTime());
        $courseDate->setEndTime($course->getEndTime());

        static::getContainer()->get(CourseDateRepository::class)->save($courseDate);

        return $courseDate;
    }

    public function testListCoursesRequiresAdminAuth(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/api/admin/courses');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testCreateCourseAsAdmin(): void
    {
        $username = 'coursetest-'.uniqid('', true);
        $client = static::createClient();
        $container = static::getContainer();
        $this->ensureCourseType();
        $userRepo = $container->get(UserRepository::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setUsername($username);
        $user->setFullName('Course Test Admin');
        $user->setPassword($hasher->hashPassword($user, 'adminsecret'));
        $userRepo->save($user);

        $client->request(
            Request::METHOD_POST,
            '/api/admin/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['username' => $username, 'password' => 'adminsecret'])
        );
        self::assertResponseIsSuccessful();
        $token = json_decode($client->getResponse()->getContent() ?: '{}', true)['token'] ?? null;
        self::assertNotNull($token);

        $client->request(
            Request::METHOD_POST,
            '/api/admin/courses',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            ],
            json_encode([
                'dayOfWeek' => 1,
                'startTime' => '10:00',
                'endTime' => '11:00',
                'typeCode' => 'JUHU',
                'level' => 1,
            ])
        );
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertArrayHasKey('id', $data);
        self::assertSame(1, $data['dayOfWeek']);
        self::assertSame('10:00', $data['startTime']);
        self::assertSame('JUHU', $data['type']['code']);
        self::assertSame('Junghunde', $data['type']['name']);
        self::assertSame('RECURRING', $data['type']['recurrenceKind']);

        $courseRepo = $container->get(CourseRepository::class);
        $course = $courseRepo->find($data['id']);
        self::assertNotNull($course);

        $courseId = $data['id'];

        $client->request(Request::METHOD_GET, '/api/admin/courses/'.$courseId, [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);
        self::assertResponseIsSuccessful();
        $getData = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame($courseId, $getData['id']);
        self::assertSame('JUHU', $getData['type']['code']);

        $client->request(
            Request::METHOD_PATCH,
            '/api/admin/courses/'.$courseId,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$token],
            json_encode(['startTime' => '11:00', 'endTime' => '12:00'])
        );
        self::assertResponseIsSuccessful();
        $updated = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('11:00', $updated['startTime']);
        self::assertSame('12:00', $updated['endTime']);

        $client->request(Request::METHOD_GET, '/api/admin/courses?archived=0', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);
        self::assertResponseIsSuccessful();
        $listData = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertArrayHasKey('items', $listData);

        $client->request(
            Request::METHOD_POST,
            '/api/admin/courses/'.$courseId.'/archive',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$token],
            json_encode(['removeFromDate' => (new \DateTimeImmutable('+1 day'))->format('Y-m-d')])
        );
        self::assertResponseIsSuccessful();
        $archived = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertTrue($archived['archived']);
        self::assertSame(0, $archived['removedCourseDates']);
        self::assertSame(0, $archived['refundedBookings']);

        $client->request(Request::METHOD_POST, '/api/admin/courses/'.$courseId.'/unarchive', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);
        self::assertResponseIsSuccessful();
        $unarchived = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertFalse($unarchived['archived']);
    }

    public function testGetCourseReturns404ForUnknownId(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/courses/00000000-0000-0000-0000-000000000000', $token);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testListCoursesSupportsPagination(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();

        $container = static::getContainer();
        $courseTypeRepo = $container->get(CourseTypeRepository::class);
        $courseType = $courseTypeRepo->findByCode('PAGE');
        if ($courseType === null) {
            $courseType = new CourseType();
            $courseType->setCode('PAGE');
            $courseType->setName('Pagination');
            $courseTypeRepo->save($courseType);
        }

        $courseRepo = $container->get(CourseRepository::class);
        foreach ([1, 2, 3] as $dayOfWeek) {
            $course = new \App\Entity\Course();
            $course->setDayOfWeek($dayOfWeek);
            $course->setStartTime('10:00');
            $course->setEndTime('11:00');
            $course->setCourseType($courseType);
            $course->setLevel(1);
            $courseRepo->save($course);
        }

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/courses?page=1&limit=2&archived=0', $token);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertCount(2, $data['items']);
        self::assertSame(2, $data['pagination']['limit']);
        self::assertGreaterThanOrEqual(3, $data['pagination']['total']);
    }

    public function testArchiveCourseRemovesFutureDatesAndRefundsBookings(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $adminToken] = $helper->createAdminAndLogin();
        ['token' => $customerToken, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $courseType = $this->ensureCourseType('ARCHIVE', 'Archiv-Test');
        $keepDate = (new \DateTimeImmutable('+3 days'))->format('Y-m-d');
        $removeFromDate = (new \DateTimeImmutable('+10 days'))->format('Y-m-d');
        $secondRemovedDate = (new \DateTimeImmutable($removeFromDate))->modify('+7 days')->format('Y-m-d');

        $course = $this->seedCourse($courseType, $keepDate);
        $keptCourseDate = $this->seedCourseDate($course, $keepDate);
        $removedCourseDate = $this->seedCourseDate($course, $removeFromDate);
        $removedCourseDateTwo = $this->seedCourseDate($course, $secondRemovedDate);

        $container = static::getContainer();
        $managedCustomer = $container->get(CustomerRepository::class)->find($customer->getId());
        self::assertNotNull($managedCustomer);
        $dogRepo = $container->get(DogRepository::class);
        $dog = new Dog();
        $dog->setCustomer($managedCustomer);
        $dog->setName('Archivhund');
        $dogRepo->save($dog);

        $creditTransactionRepo = $container->get(CreditTransactionRepository::class);
        $seedCredits = new CreditTransaction();
        $seedCredits->setCustomer($managedCustomer);
        $seedCredits->setAmount(5);
        $seedCredits->setType(CreditTransactionType::MANUAL_ADJUSTMENT);
        $seedCredits->setDescription('Seed credits for archive test');
        $creditTransactionRepo->save($seedCredits);

        $helper->customerRequest(
            Request::METHOD_POST,
            '/api/customer/calendar/course-dates/'.$removedCourseDate->getId().'/book',
            $customerToken,
            json_encode(['dogId' => $dog->getId()])
        );
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $helper->adminRequest(
            Request::METHOD_POST,
            '/api/admin/courses/'.$course->getId().'/archive',
            $adminToken,
            json_encode(['removeFromDate' => $removeFromDate])
        );
        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertTrue($data['archived']);
        self::assertSame($removeFromDate, $data['removeFromDate']);
        self::assertSame(2, $data['removedCourseDates']);
        self::assertSame(1, $data['refundedBookings']);

        $courseDateRepo = $container->get(CourseDateRepository::class);
        self::assertNotNull($courseDateRepo->find($keptCourseDate->getId()));
        self::assertNull($courseDateRepo->find($removedCourseDate->getId()));
        self::assertNull($courseDateRepo->find($removedCourseDateTwo->getId()));

        $bookingRepo = $container->get(BookingRepository::class);
        self::assertCount(0, $bookingRepo->findByCustomer($managedCustomer));

        self::assertSame(5, $creditTransactionRepo->getBalance($managedCustomer));

        $history = $creditTransactionRepo->findByCustomer($managedCustomer);
        self::assertCount(3, $history);

        $bookingTransaction = null;
        $refundTransaction = null;
        foreach ($history as $transaction) {
            if ($transaction->getType() === CreditTransactionType::BOOKING) {
                $bookingTransaction = $transaction;
            }
            if ($transaction->getType() === CreditTransactionType::CANCELLATION) {
                $refundTransaction = $transaction;
            }
        }

        self::assertNotNull($bookingTransaction);
        self::assertNull($bookingTransaction->getCourseDate());
        self::assertNotNull($refundTransaction);
        self::assertNull($refundTransaction->getCourseDate());
        self::assertStringContainsString('Kurs archiviert', $refundTransaction->getDescription());
    }

    public function testUpdateCourseReschedulesUpcomingCourseDates(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();

        $timezone = new \DateTimeZone(CourseDate::TIMEZONE);
        $today = new \DateTimeImmutable('today', $timezone);
        $firstUpcomingDate = $today->modify('+7 days');
        $secondUpcomingDate = $firstUpcomingDate->modify('+7 days');
        $pastDate = $firstUpcomingDate->modify('-14 days');

        $courseType = $this->ensureCourseType('MOVE', 'Verschieben');
        $course = $this->seedCourse($courseType, $firstUpcomingDate->format('Y-m-d'));
        $pastCourseDate = $this->seedCourseDate($course, $pastDate->format('Y-m-d'));
        $upcomingCourseDate = $this->seedCourseDate($course, $firstUpcomingDate->format('Y-m-d'));
        $secondUpcomingCourseDate = $this->seedCourseDate($course, $secondUpcomingDate->format('Y-m-d'));

        $newDayOfWeek = $course->getDayOfWeek() === 7 ? 6 : $course->getDayOfWeek() + 1;
        $dayShift = $newDayOfWeek - $course->getDayOfWeek();

        $helper->adminRequest(
            Request::METHOD_PATCH,
            '/api/admin/courses/'.$course->getId(),
            $token,
            json_encode([
                'dayOfWeek' => $newDayOfWeek,
                'startTime' => '10:00',
                'endTime' => '11:00',
                'level' => $course->getLevel(),
                'typeCode' => $courseType->getCode(),
            ])
        );
        self::assertResponseIsSuccessful();

        $courseDateRepo = static::getContainer()->get(CourseDateRepository::class);
        $reloadedPastCourseDate = $courseDateRepo->find($pastCourseDate->getId());
        $reloadedUpcomingCourseDate = $courseDateRepo->find($upcomingCourseDate->getId());
        $reloadedSecondUpcomingCourseDate = $courseDateRepo->find($secondUpcomingCourseDate->getId());

        self::assertNotNull($reloadedPastCourseDate);
        self::assertNotNull($reloadedUpcomingCourseDate);
        self::assertNotNull($reloadedSecondUpcomingCourseDate);

        self::assertSame($pastDate->format('Y-m-d'), $reloadedPastCourseDate->getDate()->format('Y-m-d'));
        self::assertSame($firstUpcomingDate->modify(sprintf('%+d days', $dayShift))->format('Y-m-d'), $reloadedUpcomingCourseDate->getDate()->format('Y-m-d'));
        self::assertSame($secondUpcomingDate->modify(sprintf('%+d days', $dayShift))->format('Y-m-d'), $reloadedSecondUpcomingCourseDate->getDate()->format('Y-m-d'));
    }
}
