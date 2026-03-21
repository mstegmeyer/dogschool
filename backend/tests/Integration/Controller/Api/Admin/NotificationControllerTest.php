<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Admin;

use App\Entity\Course;
use App\Entity\CourseType;
use App\Repository\CourseRepository;
use App\Repository\CourseTypeRepository;
use App\Repository\NotificationRepository;
use App\Tests\Helper\ApiTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class NotificationControllerTest extends WebTestCase
{
    public function testListNotificationsRequiresAdminAuth(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/api/admin/notifications');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testCreateGetUpdateDeleteNotification(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'user' => $user] = $helper->createAdminAndLogin();

        $container = static::getContainer();
        $courseTypeRepo = $container->get(CourseTypeRepository::class);
        $courseType = $courseTypeRepo->findByCode('MH');
        if ($courseType === null) {
            $courseType = new CourseType();
            $courseType->setCode('MH');
            $courseType->setName('Mensch-Hund');
            $courseTypeRepo->save($courseType);
        }
        $courseRepo = $container->get(CourseRepository::class);
        $course = new Course();
        $course->setDayOfWeek(2);
        $course->setStartTime('14:00');
        $course->setEndTime('15:00');
        $course->setCourseType($courseType);
        $course->setLevel(1);
        $courseRepo->save($course);

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/notifications', $token, json_encode([
            'courseIds' => [$course->getId()],
            'title' => 'Test Notification',
            'message' => 'Body text',
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertArrayHasKey('id', $data);
        self::assertSame('Test Notification', $data['title']);
        self::assertSame('Body text', $data['message']);
        self::assertFalse($data['isGlobal']);
        self::assertCount(1, $data['courses']);
        $id = $data['id'];

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/notifications/'.$id, $token);
        self::assertResponseIsSuccessful();
        $one = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame($id, $one['id']);

        $helper->adminRequest(Request::METHOD_PATCH, '/api/admin/notifications/'.$id, $token, json_encode([
            'title' => 'Updated Title',
            'message' => 'Updated body',
        ]));
        self::assertResponseIsSuccessful();
        $updated = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('Updated Title', $updated['title']);
        self::assertSame('Updated body', $updated['message']);

        $helper->adminRequest(Request::METHOD_DELETE, '/api/admin/notifications/'.$id, $token);
        self::assertResponseIsSuccessful();

        $notifRepo = $container->get(NotificationRepository::class);
        self::assertNull($notifRepo->find($id));
    }

    public function testCreateNotificationFailsForUnknownCourse(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/notifications', $token, json_encode([
            'courseIds' => ['f47ac10b-58cc-4372-a567-0e02b2c3d479'],
            'title' => 'Title',
            'message' => 'Message',
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testGetNotificationReturns404ForUnknownId(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/notifications/00000000-0000-0000-0000-000000000000', $token);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
