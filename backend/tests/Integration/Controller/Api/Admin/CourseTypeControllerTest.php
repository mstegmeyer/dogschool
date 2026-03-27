<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Admin;

use App\Repository\CourseTypeRepository;
use App\Tests\Helper\ApiTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CourseTypeControllerTest extends WebTestCase
{
    public function testListCourseTypesRequiresAdminAuth(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/api/admin/course-types');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testCreateGetUpdateDeleteCourseType(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/course-types', $token, json_encode([
            'code' => 'TST',
            'name' => 'Test Kursart',
            'recurrenceKind' => 'ONE_TIME',
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertArrayHasKey('id', $data);
        self::assertSame('TST', $data['code']);
        self::assertSame('Test Kursart', $data['name']);
        self::assertSame('ONE_TIME', $data['recurrenceKind']);
        $id = $data['id'];

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/course-types/'.$id, $token);
        self::assertResponseIsSuccessful();
        $one = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame($id, $one['id']);

        $helper->adminRequest(Request::METHOD_PATCH, '/api/admin/course-types/'.$id, $token, json_encode([
            'name' => 'Updated Name',
            'recurrenceKind' => 'DROP_IN',
        ]));
        self::assertResponseIsSuccessful();
        $updated = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('Updated Name', $updated['name']);
        self::assertSame('DROP_IN', $updated['recurrenceKind']);
        self::assertSame('TST', $updated['code']);

        $helper->adminRequest(Request::METHOD_DELETE, '/api/admin/course-types/'.$id, $token);
        self::assertResponseIsSuccessful();

        $repo = static::getContainer()->get(CourseTypeRepository::class);
        self::assertNull($repo->find($id));
    }

    public function testGetCourseTypeReturns404ForUnknownId(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/course-types/00000000-0000-0000-0000-000000000000', $token);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCreateCourseTypeValidation(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/course-types', $token, json_encode([
            'code' => '',
            'name' => '',
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
