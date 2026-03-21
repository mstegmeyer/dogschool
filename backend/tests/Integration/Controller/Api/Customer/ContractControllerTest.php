<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Customer;

use App\Entity\Dog;
use App\Repository\ContractRepository;
use App\Repository\DogRepository;
use App\Tests\Helper\ApiTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ContractControllerTest extends WebTestCase
{
    public function testListContractsRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/api/customer/contracts');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListContractsReturnsEmpty(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createCustomerAndLogin();

        $helper->customerRequest(Request::METHOD_GET, '/api/customer/contracts', $token);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertArrayHasKey('items', $data);
        self::assertSame([], $data['items']);
    }

    public function testRequestContract(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $container = static::getContainer();
        $dogRepo = $container->get(DogRepository::class);
        $dog = new Dog();
        $dog->setCustomer($customer);
        $dog->setName('Contract Dog');
        $dogRepo->save($dog);

        $helper->customerRequest(Request::METHOD_POST, '/api/customer/contracts', $token, json_encode([
            'dogId' => $dog->getId(),
            'startDate' => '2025-06-01',
            'endDate' => '2026-06-01',
            'price' => '120.00',
            'coursesPerWeek' => 2,
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertArrayHasKey('id', $data);
        self::assertSame('REQUESTED', $data['state']);
        self::assertSame('120.00', $data['price']);

        $contractRepo = $container->get(ContractRepository::class);
        $contracts = $contractRepo->findByCustomer($customer);
        self::assertCount(1, $contracts);
    }

    public function testRequestContractFailsWithWrongDogId(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createCustomerAndLogin();

        $helper->customerRequest(Request::METHOD_POST, '/api/customer/contracts', $token, json_encode([
            'dogId' => '00000000-0000-0000-0000-000000000000',
            'startDate' => '2025-06-01',
            'endDate' => '2026-06-01',
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertArrayHasKey('errors', $data);
        self::assertArrayHasKey('dogId', $data['errors']);
    }
}
