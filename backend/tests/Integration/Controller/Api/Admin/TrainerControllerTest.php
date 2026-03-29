<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Admin;

use App\Tests\Helper\ApiTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class TrainerControllerTest extends WebTestCase
{
    public function testListTrainersRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/api/admin/trainers');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListTrainersReturnsAdminAccounts(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin(fullName: 'Viewer Admin');
        $helper->createAdminAndLogin(fullName: 'Trainer Alpha');
        $helper->createAdminAndLogin(fullName: 'Trainer Beta');

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/trainers', $token);
        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertArrayHasKey('items', $data);
        self::assertGreaterThanOrEqual(3, count($data['items']));
        self::assertContains('Trainer Alpha', array_column($data['items'], 'fullName'));
        self::assertContains('Trainer Beta', array_column($data['items'], 'fullName'));
    }
}
