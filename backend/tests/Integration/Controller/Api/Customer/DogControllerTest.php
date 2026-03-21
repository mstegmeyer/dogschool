<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Customer;

use App\Repository\DogRepository;
use App\Tests\Helper\ApiTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class DogControllerTest extends WebTestCase
{
    public function testListDogsRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/api/customer/dogs');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListDogsReturnsEmpty(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createCustomerAndLogin();

        $helper->customerRequest(Request::METHOD_GET, '/api/customer/dogs', $token);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertArrayHasKey('items', $data);
        self::assertSame([], $data['items']);
    }

    public function testCreateDog(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $helper->customerRequest(Request::METHOD_POST, '/api/customer/dogs', $token, json_encode([
            'name' => 'Rex',
            'color' => 'brown',
            'gender' => 'male',
            'race' => 'Labrador',
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertArrayHasKey('id', $data);
        self::assertSame('Rex', $data['name']);
        self::assertSame('brown', $data['color']);
        self::assertSame('Labrador', $data['race']);

        $container = static::getContainer();
        $dogRepo = $container->get(DogRepository::class);
        $dogs = $dogRepo->findByCustomer($customer);
        self::assertCount(1, $dogs);
        self::assertSame('Rex', $dogs[0]->getName());
    }

    public function testCreateDogFailsWithoutName(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createCustomerAndLogin();

        $helper->customerRequest(Request::METHOD_POST, '/api/customer/dogs', $token, json_encode([
            'color' => 'black',
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
