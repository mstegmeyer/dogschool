<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Customer;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use App\Tests\Helper\ApiTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ProfileControllerTest extends WebTestCase
{
    public function testMeRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/api/customer/me');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testMeReturnsCustomerAfterLogin(): void
    {
        $email = 'profile-'.uniqid('', true).'@example.com';
        $client = static::createClient();
        $container = static::getContainer();
        $customerRepo = $container->get(CustomerRepository::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $customer = new Customer();
        $customer->setEmail($email);
        $customer->setName('Profile Test');
        $customer->setPassword($hasher->hashPassword($customer, 'secret'));
        $customerRepo->save($customer);

        $client->request(
            Request::METHOD_POST,
            '/api/customer/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => 'secret'])
        );
        self::assertResponseIsSuccessful();
        $token = json_decode($client->getResponse()->getContent() ?: '{}', true)['token'] ?? null;
        self::assertNotNull($token);

        $client->request(Request::METHOD_GET, '/api/customer/me', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame($email, $data['email']);
        self::assertSame('Profile Test', $data['name']);
    }

    public function testUpdateMePartially(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createCustomerAndLogin(null, 'secret', 'Original Name');

        $helper->customerRequest(Request::METHOD_PATCH, '/api/customer/me', $token, json_encode([
            'name' => 'Updated Name',
        ]));
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('Updated Name', $data['name']);
    }

    public function testUpdateMeRejectsInvalidEmail(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createCustomerAndLogin();

        $helper->customerRequest(Request::METHOD_PUT, '/api/customer/me', $token, json_encode([
            'email' => 'not-an-email',
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
