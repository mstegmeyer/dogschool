<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Customer;

use App\Repository\CustomerRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class RegisterControllerTest extends WebTestCase
{
    public function testRegisterCreatesCustomer(): void
    {
        $email = 'register-'.uniqid('', true).'@example.com';
        $client = static::createClient();
        $client->request(
            Request::METHOD_POST,
            '/api/customer/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $email,
                'password' => 'securepass123',
                'name' => 'New Customer',
            ])
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertArrayHasKey('id', $data);
        self::assertSame($email, $data['email']);
        self::assertSame('New Customer', $data['name']);
        self::assertArrayNotHasKey('password', $data);

        $container = static::getContainer();
        $repo = $container->get(CustomerRepository::class);
        $customer = $repo->findByEmail($email);
        self::assertNotNull($customer);
        self::assertSame('New Customer', $customer->getName());
    }

    public function testRegisterFailsWithDuplicateEmail(): void
    {
        $email = 'duplicate-'.uniqid('', true).'@example.com';
        $client = static::createClient();
        $payload = [
            'email' => $email,
            'password' => 'pass',
            'name' => 'First',
        ];
        $client->request(Request::METHOD_POST, '/api/customer/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $client->request(Request::METHOD_POST, '/api/customer/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testRegisterFailsWithoutEmail(): void
    {
        $client = static::createClient();
        $client->request(
            Request::METHOD_POST,
            '/api/customer/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['password' => 'pass', 'name' => 'No Email'])
        );
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
