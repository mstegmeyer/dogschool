<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Admin;

use App\Entity\Course;
use App\Entity\CourseType;
use App\Entity\Notification;
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

        $pinnedUntil = (new \DateTimeImmutable('+14 days'))->format('Y-m-d\\TH:i:s');
        $helper->adminRequest(Request::METHOD_POST, '/api/admin/notifications', $token, $this->encodeJson([
            'courseIds' => [$course->getId()],
            'title' => 'Test Notification',
            'message' => 'Body text',
            'pinnedUntil' => $pinnedUntil,
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        /** @var array{id: string, title: string, message: string, isGlobal: bool, courses: list<array<string, mixed>>, pinnedUntil: string|null, isPinned: bool} $data */
        $data = $this->decodeJsonResponse($client->getResponse()->getContent());
        self::assertSame('Test Notification', $data['title']);
        self::assertSame('Body text', $data['message']);
        self::assertFalse($data['isGlobal']);
        self::assertCount(1, $data['courses']);
        self::assertNotNull($data['pinnedUntil']);
        self::assertTrue($data['isPinned']);
        $id = $data['id'];

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/notifications/'.$id, $token);
        self::assertResponseIsSuccessful();
        /** @var array{id: string} $one */
        $one = $this->decodeJsonResponse($client->getResponse()->getContent());
        self::assertSame($id, $one['id']);

        $helper->adminRequest(Request::METHOD_PATCH, '/api/admin/notifications/'.$id, $token, $this->encodeJson([
            'title' => 'Updated Title',
            'message' => 'Updated body',
            'pinnedUntil' => '',
        ]));
        self::assertResponseIsSuccessful();
        /** @var array{title: string, message: string, pinnedUntil: string|null, isPinned: bool} $updated */
        $updated = $this->decodeJsonResponse($client->getResponse()->getContent());
        self::assertSame('Updated Title', $updated['title']);
        self::assertSame('Updated body', $updated['message']);
        self::assertNull($updated['pinnedUntil']);
        self::assertFalse($updated['isPinned']);

        $helper->adminRequest(Request::METHOD_DELETE, '/api/admin/notifications/'.$id, $token);
        self::assertResponseIsSuccessful();

        $notifRepo = $container->get(NotificationRepository::class);
        self::assertNull($notifRepo->find($id));
    }

    public function testListNotificationsSupportsPagination(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();

        for ($i = 0; $i < 3; ++$i) {
            $helper->adminRequest(Request::METHOD_POST, '/api/admin/notifications', $token, $this->encodeJson([
                'courseIds' => [],
                'title' => 'Paged Notification '.$i,
                'message' => 'Body '.$i,
            ]));
            self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        }

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/notifications?page=1&limit=2', $token);
        self::assertResponseIsSuccessful();
        /** @var array{items: list<array<string, mixed>>, pagination: array{limit: int, total: int}} $data */
        $data = $this->decodeJsonResponse($client->getResponse()->getContent());
        self::assertCount(2, $data['items']);
        self::assertSame(2, $data['pagination']['limit']);
        self::assertGreaterThanOrEqual(3, $data['pagination']['total']);
    }

    public function testListNotificationsPaginationReturnsDistinctNotificationsWithManyCourses(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'user' => $user] = $helper->createAdminAndLogin();

        $container = static::getContainer();
        $courseTypeRepo = $container->get(CourseTypeRepository::class);
        $courseType = $courseTypeRepo->findByCode('AG');
        if ($courseType === null) {
            $courseType = new CourseType();
            $courseType->setCode('AG');
            $courseType->setName('Agility');
            $courseTypeRepo->save($courseType);
        }

        $courseRepo = $container->get(CourseRepository::class);
        $courses = [];
        for ($i = 0; $i < 10; ++$i) {
            $course = new Course();
            $course->setDayOfWeek(($i % 7) + 1);
            $course->setStartTime(sprintf('%02d:00', 8 + $i));
            $course->setEndTime(sprintf('%02d:00', 9 + $i));
            $course->setCourseType($courseType);
            $course->setLevel(1);
            $courseRepo->save($course);
            $courses[] = $course;
        }

        $notificationRepo = $container->get(NotificationRepository::class);
        for ($i = 0; $i < 4; ++$i) {
            $notification = new Notification();
            $notification->setAuthor($user);
            $notification->setTitle('Joined Notification '.$i);
            $notification->setMessage('Body '.$i);

            foreach ($courses as $course) {
                $notification->addCourse($course);
            }

            $notificationRepo->save($notification);
        }

        $expectedTotal = $notificationRepo->countForAdminList();

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/notifications?page=1&limit=20', $token);
        self::assertResponseIsSuccessful();
        /** @var array{items: list<array{title: string}>, pagination: array{total: int}} $data */
        $data = $this->decodeJsonResponse($client->getResponse()->getContent());

        self::assertSame($expectedTotal, $data['pagination']['total']);
        self::assertCount($expectedTotal, $data['items']);
        $titles = array_column($data['items'], 'title');
        sort($titles);
        self::assertContains('Joined Notification 0', $titles);
        self::assertContains('Joined Notification 1', $titles);
        self::assertContains('Joined Notification 2', $titles);
        self::assertContains('Joined Notification 3', $titles);
    }

    public function testCreateNotificationFailsForUnknownCourse(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/notifications', $token, $this->encodeJson([
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

    /**
     * @param array<string, mixed> $payload
     */
    private function encodeJson(array $payload): string
    {
        $json = json_encode($payload);
        self::assertNotFalse($json);

        return $json;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonResponse(string|false|null $content): array
    {
        self::assertIsString($content);

        $data = json_decode($content, true);
        self::assertIsArray($data);

        /** @var array<string, mixed> $data */
        return $data;
    }
}
