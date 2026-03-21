<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Admin;

use App\Repository\CustomerRepository;
use App\Tests\Helper\ApiTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CustomerControllerTest extends WebTestCase
{
    public function testListCustomersRequiresAdminAuth(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/api/admin/customers');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListAndGetCustomer(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();
        ['customer' => $customer] = $helper->createCustomerAndLogin('admin-list-'.uniqid('', true).'@example.com');

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/customers', $token);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertArrayHasKey('items', $data);
        self::assertGreaterThanOrEqual(1, count($data['items']));

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/customers/'.$customer->getId(), $token);
        self::assertResponseIsSuccessful();
        $one = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame($customer->getId(), $one['id']);
        self::assertSame($customer->getEmail(), $one['email']);
    }

    public function testGetCustomerReturns404ForUnknownId(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/customers/00000000-0000-0000-0000-000000000000', $token);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateCustomer(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();
        ['customer' => $customer] = $helper->createCustomerAndLogin('admin-update-'.uniqid('', true).'@example.com', 'pass', 'Before');

        $helper->adminRequest(Request::METHOD_PATCH, '/api/admin/customers/'.$customer->getId(), $token, json_encode([
            'name' => 'Updated By Admin',
        ]));
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('Updated By Admin', $data['name']);

        $container = static::getContainer();
        $repo = $container->get(CustomerRepository::class);
        $reloaded = $repo->find($customer->getId());
        self::assertNotNull($reloaded);
        self::assertSame('Updated By Admin', $reloaded->getName());
    }
}
