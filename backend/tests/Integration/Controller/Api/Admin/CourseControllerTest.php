<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Admin;

use App\Entity\CourseType;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Repository\CourseTypeRepository;
use App\Repository\UserRepository;
use App\Tests\Helper\ApiTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CourseControllerTest extends WebTestCase
{
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
        $courseTypeRepo = $container->get(CourseTypeRepository::class);
        $courseType = $courseTypeRepo->findByCode('JUHU');
        if ($courseType === null) {
            $courseType = new CourseType();
            $courseType->setCode('JUHU');
            $courseType->setName('Junghunde');
            $courseTypeRepo->save($courseType);
        }
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

        $client->request(Request::METHOD_POST, '/api/admin/courses/'.$courseId.'/archive', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);
        self::assertResponseIsSuccessful();
        $archived = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertTrue($archived['archived']);

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
}
