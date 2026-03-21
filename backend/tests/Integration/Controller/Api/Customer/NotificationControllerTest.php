<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Customer;

use App\Entity\Course;
use App\Entity\CourseType;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Repository\CourseTypeRepository;
use App\Repository\CustomerRepository;
use App\Repository\NotificationRepository;
use App\Tests\Helper\ApiTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class NotificationControllerTest extends WebTestCase
{
    public function testListNotificationsRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/api/customer/notifications');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListNotificationsReturnsGlobalNotifications(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createCustomerAndLogin();

        $container = static::getContainer();
        $notifRepo = $container->get(NotificationRepository::class);
        $userRepo = $container->get(\App\Repository\UserRepository::class);

        $author = new User();
        $author->setUsername('notif-author-'.uniqid('', true));
        $author->setFullName('Author');
        $author->setPassword('x');
        $userRepo->save($author);

        $notification = new Notification();
        $notification->setTitle('Global Announcement');
        $notification->setMessage('All customers should see this');
        $notification->setAuthor($author);
        $notifRepo->save($notification);

        $helper->customerRequest(Request::METHOD_GET, '/api/customer/notifications', $token);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertArrayHasKey('items', $data);

        $found = false;
        foreach ($data['items'] as $item) {
            if ($item['title'] === 'Global Announcement') {
                $found = true;
                self::assertTrue($item['isGlobal']);
            }
        }
        self::assertTrue($found, 'Global notification should appear in customer list');
    }

    public function testListNotificationsForSubscribedCourses(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $container = static::getContainer();
        $courseTypeRepo = $container->get(CourseTypeRepository::class);
        $courseType = $courseTypeRepo->findByCode('AGI');
        if ($courseType === null) {
            $courseType = new CourseType();
            $courseType->setCode('AGI');
            $courseType->setName('Agility');
            $courseTypeRepo->save($courseType);
        }

        $courseRepo = $container->get(CourseRepository::class);
        $course = new Course();
        $course->setDayOfWeek(4);
        $course->setStartTime('17:00');
        $course->setEndTime('18:00');
        $course->setCourseType($courseType);
        $courseRepo->save($course);

        $customerRepo = $container->get(CustomerRepository::class);
        $customer = $customerRepo->find($customer->getId());
        self::assertNotNull($customer);
        $customer->addSubscribedCourse($course);
        $customerRepo->save($customer);

        $userRepo = $container->get(\App\Repository\UserRepository::class);
        $author = new User();
        $author->setUsername('notif-course-'.uniqid('', true));
        $author->setFullName('Trainer');
        $author->setPassword('x');
        $userRepo->save($author);

        $notifRepo = $container->get(NotificationRepository::class);
        $notification = new Notification();
        $notification->setTitle('Agility Update');
        $notification->setMessage('New schedule');
        $notification->setAuthor($author);
        $notification->addCourse($course);
        $notifRepo->save($notification);

        $helper->customerRequest(Request::METHOD_GET, '/api/customer/notifications', $token);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);

        $found = false;
        foreach ($data['items'] as $item) {
            if ($item['title'] === 'Agility Update') {
                $found = true;
                self::assertFalse($item['isGlobal']);
            }
        }
        self::assertTrue($found, 'Course-specific notification should appear for subscribed customer');
    }
}
