<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Customer;

use App\Repository\PushDeviceRepository;
use App\Tests\Helper\ApiTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PushDeviceControllerTest extends WebTestCase
{
    public function testUpsertRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_POST, '/api/customer/me/push-devices');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testCustomerCanRegisterAndUnregisterPushDevice(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['customer' => $customer, 'token' => $token] = $helper->createCustomerAndLogin();

        $helper->customerRequest(Request::METHOD_POST, '/api/customer/me/push-devices', $token, json_encode([
            'token' => '{"endpoint":"https://example.com/push/customer"}',
            'platform' => 'web',
            'provider' => 'webpush',
            'deviceName' => 'Safari Home Screen App',
        ]));
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('{"endpoint":"https://example.com/push/customer"}', $data['token']);
        self::assertSame('web', $data['platform']);
        self::assertSame('webpush', $data['provider']);

        $repo = static::getContainer()->get(PushDeviceRepository::class);
        $stored = $repo->findOneByToken('{"endpoint":"https://example.com/push/customer"}');
        self::assertNotNull($stored);
        self::assertSame($customer->getId(), $stored->getCustomer()?->getId());

        $helper->customerRequest(Request::METHOD_POST, '/api/customer/me/push-devices/unregister', $token, json_encode([
            'token' => '{"endpoint":"https://example.com/push/customer"}',
        ]));
        self::assertResponseIsSuccessful();
        $unregister = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertTrue($unregister['success']);
        self::assertNull($repo->findOneByToken('{"endpoint":"https://example.com/push/customer"}'));
    }
}
