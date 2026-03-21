<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api;

use App\Repository\CustomerRepository;
use App\Tests\Helper\ApiTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class LoginControllerTest extends WebTestCase
{
    public function testCustomerLoginReturnsToken(): void
    {
        $email = 'login-cust-'.uniqid('', true).'@example.com';
        $client = static::createClient();
        $container = static::getContainer();
        $customerRepo = $container->get(CustomerRepository::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);
        $customer = new \App\Entity\Customer();
        $customer->setEmail($email);
        $customer->setName('Login Test');
        $customer->setPassword($hasher->hashPassword($customer, 'mypass'));
        $customerRepo->save($customer);

        $client->request(
            Request::METHOD_POST,
            '/api/customer/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => 'mypass'])
        );

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertArrayHasKey('token', $data);
        self::assertNotEmpty($data['token']);
    }

    public function testCustomerLoginFailsWithWrongPassword(): void
    {
        $email = 'login-fail-'.uniqid('', true).'@example.com';
        $client = static::createClient();
        $container = static::getContainer();
        $customerRepo = $container->get(CustomerRepository::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);
        $customer = new \App\Entity\Customer();
        $customer->setEmail($email);
        $customer->setName('Fail');
        $customer->setPassword($hasher->hashPassword($customer, 'right'));
        $customerRepo->save($customer);

        $client->request(
            Request::METHOD_POST,
            '/api/customer/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => 'wrong'])
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testCustomerLoginFailsWithInvalidJsonBody(): void
    {
        $client = static::createClient();
        $client->request(
            Request::METHOD_POST,
            '/api/customer/login',
            [],
            [],
            [],
            'not json'
        );
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testAdminLoginReturnsToken(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        $result = $helper->createAdminAndLogin(null, 'pass', 'Admin');

        self::assertNotEmpty($result['token']);
    }

    public function testAdminLoginFailsWithWrongPassword(): void
    {
        $username = 'admin-fail-'.uniqid('', true);
        $client = static::createClient();
        $container = static::getContainer();
        $userRepo = $container->get(\App\Repository\UserRepository::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);
        $user = new \App\Entity\User();
        $user->setUsername($username);
        $user->setFullName('Fail');
        $user->setPassword($hasher->hashPassword($user, 'right'));
        $userRepo->save($user);

        $client->request(
            Request::METHOD_POST,
            '/api/admin/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['username' => $username, 'password' => 'wrong'])
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
