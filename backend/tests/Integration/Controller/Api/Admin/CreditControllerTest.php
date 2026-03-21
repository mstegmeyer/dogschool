<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Admin;

use App\Tests\Helper\ApiTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CreditControllerTest extends WebTestCase
{
    public function testListCreditsRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/api/admin/credits');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListCreditsRequiresCustomerId(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/credits', $token);
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testListCreditsReturns404ForUnknownCustomer(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/credits?customerId=00000000-0000-0000-0000-000000000000', $token);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testListCreditsForCustomer(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $adminToken] = $helper->createAdminAndLogin();
        ['customer' => $customer] = $helper->createCustomerAndLogin('credit-list-'.uniqid('', true).'@example.com');

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/credits?customerId='.$customer->getId(), $adminToken);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertArrayHasKey('balance', $data);
        self::assertArrayHasKey('items', $data);
        self::assertSame($customer->getId(), $data['customerId']);
    }

    public function testAdjustCreditsAddsManualTransaction(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $adminToken] = $helper->createAdminAndLogin();
        ['customer' => $customer] = $helper->createCustomerAndLogin('credit-adjust-'.uniqid('', true).'@example.com');

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/credits/adjust', $adminToken, json_encode([
            'customerId' => $customer->getId(),
            'amount' => 5,
            'description' => 'Bonus credits for loyalty',
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame(5, $data['amount']);
        self::assertSame('MANUAL_ADJUSTMENT', $data['type']);
        self::assertSame('Bonus credits for loyalty', $data['description']);

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/credits?customerId='.$customer->getId(), $adminToken);
        self::assertResponseIsSuccessful();
        $listData = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame(5, $listData['balance']);
    }

    public function testAdjustCreditsRejectsInvalidBody(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/credits/adjust', $token, json_encode([
            'customerId' => '',
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testAdjustCreditsRejectsMissingAmount(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $adminToken] = $helper->createAdminAndLogin();
        ['customer' => $customer] = $helper->createCustomerAndLogin('credit-miss-'.uniqid('', true).'@example.com');

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/credits/adjust', $adminToken, json_encode([
            'customerId' => $customer->getId(),
            'description' => 'Test',
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
