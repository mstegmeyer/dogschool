<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Admin;

use App\Repository\PushDeviceRepository;
use App\Tests\Helper\ApiTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PushDeviceControllerTest extends WebTestCase
{
    public function testAdminCanRegisterPushDevice(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['user' => $user, 'token' => $token] = $helper->createAdminAndLogin();

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/me/push-devices', $token, json_encode([
            'token' => '{"endpoint":"https://example.com/push/admin"}',
            'platform' => 'web',
            'provider' => 'webpush',
            'deviceName' => 'Safari Home Screen App',
        ]));
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('{"endpoint":"https://example.com/push/admin"}', $data['token']);
        self::assertSame('web', $data['platform']);
        self::assertSame('webpush', $data['provider']);

        $repo = static::getContainer()->get(PushDeviceRepository::class);
        $stored = $repo->findOneByToken('{"endpoint":"https://example.com/push/admin"}');
        self::assertNotNull($stored);
        self::assertSame($user->getId(), $stored->getUser()?->getId());
    }

    public function testAdminPushDeviceRouteRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_POST, '/api/admin/me/push-devices');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
