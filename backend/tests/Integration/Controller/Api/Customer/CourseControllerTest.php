<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Customer;

use App\Entity\Course;
use App\Entity\CourseDate;
use App\Entity\CourseType;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Repository\CourseTypeRepository;
use App\Repository\NotificationRepository;
use App\Tests\Helper\ApiTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CourseControllerTest extends WebTestCase
{
    public function testListCoursesRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/api/customer/courses');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testSubscribedRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/api/customer/courses/subscribed');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testDetailRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/api/customer/courses/00000000-0000-0000-0000-000000000000/detail');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testSubscribeAndUnsubscribe(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $container = static::getContainer();
        $courseTypeRepo = $container->get(CourseTypeRepository::class);
        $courseType = $courseTypeRepo->findByCode('JUHU');
        if ($courseType === null) {
            $courseType = new CourseType();
            $courseType->setCode('JUHU');
            $courseType->setName('Junghunde');
            $courseTypeRepo->save($courseType);
        }
        $courseRepo = $container->get(CourseRepository::class);
        $course = new Course();
        $course->setDayOfWeek(1);
        $course->setStartTime('10:00');
        $course->setEndTime('11:00');
        $course->setCourseType($courseType);
        $course->setLevel(0);
        $course->setArchived(false);
        $courseRepo->save($course);

        $helper->customerRequest(Request::METHOD_GET, '/api/customer/courses/subscribed', $token);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertCount(0, $data['items']);

        $helper->customerRequest(Request::METHOD_POST, '/api/customer/courses/'.$course->getId().'/subscribe', $token);
        self::assertResponseIsSuccessful();

        $helper->customerRequest(Request::METHOD_GET, '/api/customer/courses/subscribed', $token);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertCount(1, $data['items']);
        self::assertSame($course->getId(), $data['items'][0]['id']);

        $helper->customerRequest(Request::METHOD_DELETE, '/api/customer/courses/'.$course->getId().'/subscribe', $token);
        self::assertResponseIsSuccessful();

        $helper->customerRequest(Request::METHOD_GET, '/api/customer/courses/subscribed', $token);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertCount(0, $data['items']);
    }

    public function testSubscribeToNonExistentCourseReturns404(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createCustomerAndLogin();

        $helper->customerRequest(Request::METHOD_POST, '/api/customer/courses/00000000-0000-0000-0000-000000000000/subscribe', $token);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDetailReturnsUpcomingDatesAndRecentNotifications(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createCustomerAndLogin();

        $container = static::getContainer();
        $courseTypeRepo = $container->get(CourseTypeRepository::class);
        $courseType = $courseTypeRepo->findByCode('DET');
        if ($courseType === null) {
            $courseType = new CourseType();
            $courseType->setCode('DET');
            $courseType->setName('Detailkurs');
            $courseTypeRepo->save($courseType);
        }

        $courseRepo = $container->get(CourseRepository::class);
        $course = new Course();
        $course->setDayOfWeek(2);
        $course->setStartTime('18:00');
        $course->setEndTime('19:00');
        $course->setCourseType($courseType);
        $course->setArchived(false);
        $courseRepo->save($course);

        $entityManager = $container->get('doctrine')->getManager();
        $timezone = new \DateTimeZone(CourseDate::TIMEZONE);
        $now = new \DateTimeImmutable('now', $timezone);

        $upcoming = new CourseDate();
        $upcoming->setCourse($course);
        $upcoming->setDate($now->modify('+7 days')->setTime(0, 0));
        $upcoming->setStartTime('18:00');
        $upcoming->setEndTime('19:00');
        $entityManager->persist($upcoming);

        $tooFar = new CourseDate();
        $tooFar->setCourse($course);
        $tooFar->setDate($now->modify('+40 days')->setTime(0, 0));
        $tooFar->setStartTime('18:00');
        $tooFar->setEndTime('19:00');
        $entityManager->persist($tooFar);

        $author = new User();
        $author->setUsername('detail-author-'.uniqid('', true));
        $author->setFullName('Detail Trainer');
        $author->setPassword('x');
        $container->get(\App\Repository\UserRepository::class)->save($author);

        $notificationRepo = $container->get(NotificationRepository::class);
        $recentNotification = new Notification();
        $recentNotification->setTitle('Aktuelle Mitteilung');
        $recentNotification->setMessage('Innerhalb des Verlaufs');
        $recentNotification->setAuthor($author);
        $recentNotification->addCourse($course);
        $notificationRepo->save($recentNotification, false);
        self::setEntityDateTime($recentNotification, 'createdAt', $now->modify('-2 months'));

        $oldNotification = new Notification();
        $oldNotification->setTitle('Alte Mitteilung');
        $oldNotification->setMessage('Außerhalb des Verlaufs');
        $oldNotification->setAuthor($author);
        $oldNotification->addCourse($course);
        $notificationRepo->save($oldNotification, false);
        self::setEntityDateTime($oldNotification, 'createdAt', $now->modify('-7 months'));

        $entityManager->flush();

        $helper->customerRequest(Request::METHOD_GET, '/api/customer/courses/'.$course->getId().'/detail', $token);
        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame($course->getId(), $data['course']['id']);
        self::assertCount(1, $data['upcomingDates']);
        self::assertSame($upcoming->getId(), $data['upcomingDates'][0]['id']);
        self::assertCount(1, $data['notifications']);
        self::assertSame($recentNotification->getId(), $data['notifications'][0]['id']);
    }

    private static function setEntityDateTime(object $entity, string $property, \DateTimeImmutable $value): void
    {
        $reflection = new \ReflectionProperty($entity, $property);
        $reflection->setAccessible(true);
        $reflection->setValue($entity, $value);
    }
}
