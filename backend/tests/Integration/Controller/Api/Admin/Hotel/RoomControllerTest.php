<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Admin\Hotel;

use App\Entity\Room;
use App\Tests\Helper\ApiTestHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class RoomControllerTest extends WebTestCase
{
    public function testListCreateAndUpdateRoom(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/hotel/rooms', $token);
        self::assertResponseIsSuccessful();

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/hotel/rooms', $token, json_encode([
            'name' => 'Suite A',
            'squareMeters' => 18,
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $created = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('Suite A', $created['name']);
        self::assertSame(18, $created['squareMeters']);

        $helper->adminRequest(Request::METHOD_PUT, '/api/admin/hotel/rooms/'.$created['id'], $token, json_encode([
            'name' => 'Suite A1',
            'squareMeters' => 20,
        ]));
        self::assertResponseIsSuccessful();
        $updated = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('Suite A1', $updated['name']);
        self::assertSame(20, $updated['squareMeters']);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $room = $entityManager->getRepository(Room::class)->find($created['id']);
        self::assertNotNull($room);
        self::assertSame('Suite A1', $room->getName());
    }

    public function testCreateRoomRequiresSquareMeters(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/hotel/rooms', $token, json_encode([
            'name' => 'Ohne Größe',
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertStringContainsString(
            'Bitte eine gültige Raumgröße angeben.',
            $client->getResponse()->getContent() ?: '',
        );
    }

    public function testPatchRoomWithoutSquareMetersIsRejected(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/hotel/rooms', $token, json_encode([
            'name' => 'Suite B',
            'squareMeters' => 16,
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $created = json_decode($client->getResponse()->getContent() ?: '{}', true);

        $helper->adminRequest(Request::METHOD_PATCH, '/api/admin/hotel/rooms/'.$created['id'], $token, json_encode([
            'name' => 'Suite B aktualisiert',
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertStringContainsString(
            'Bitte eine gültige Raumgröße angeben.',
            $client->getResponse()->getContent() ?: '',
        );

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        /** @var Room|null $room */
        $room = $entityManager->getRepository(Room::class)->find($created['id']);
        self::assertNotNull($room);
        self::assertSame('Suite B', $room->getName());
        self::assertSame(16, $room->getSquareMeters());
    }

    public function testRoomsRequireAdminAuth(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/api/admin/hotel/rooms');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
