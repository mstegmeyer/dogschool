<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Entity\Customer;
use App\Entity\User;
use App\Repository\CustomerRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ApiTestHelper
{
    public function __construct(
        private readonly KernelBrowser $client,
        private readonly CustomerRepository $customerRepository,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public static function create(KernelBrowser $client): self
    {
        $container = $client->getContainer();
        if ($container === null) {
            throw new \RuntimeException('Container not available');
        }

        return new self(
            $client,
            $container->get(CustomerRepository::class),
            $container->get(UserRepository::class),
            $container->get(UserPasswordHasherInterface::class),
        );
    }

    public function createCustomerAndLogin(
        ?string $email = null,
        string $password = 'secret',
        string $name = 'Test Customer',
    ): array {
        $email = $email ?? 'customer-'.uniqid('', true).'@example.com';
        $customer = new Customer();
        $customer->setEmail($email);
        $customer->setName($name);
        $customer->setPassword($this->passwordHasher->hashPassword($customer, $password));
        $this->customerRepository->save($customer);

        $this->client->request(
            Request::METHOD_POST,
            '/api/customer/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password])
        );
        if ($this->client->getResponse()->getStatusCode() !== Response::HTTP_OK) {
            throw new \RuntimeException('Customer login failed: '.$this->client->getResponse()->getContent());
        }
        $token = json_decode($this->client->getResponse()->getContent() ?: '{}', true)['token'] ?? null;
        if ($token === null) {
            throw new \RuntimeException('No token in login response');
        }

        return ['customer' => $customer, 'token' => $token];
    }

    public function createAdminAndLogin(
        ?string $username = null,
        string $password = 'adminsecret',
        string $fullName = 'Test Admin',
    ): array {
        $username = $username ?? 'admin-'.uniqid('', true);
        $user = new User();
        $user->setUsername($username);
        $user->setFullName($fullName);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $this->userRepository->save($user);

        $this->client->request(
            Request::METHOD_POST,
            '/api/admin/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['username' => $username, 'password' => $password])
        );
        if ($this->client->getResponse()->getStatusCode() !== Response::HTTP_OK) {
            throw new \RuntimeException('Admin login failed: '.$this->client->getResponse()->getContent());
        }
        $token = json_decode($this->client->getResponse()->getContent() ?: '{}', true)['token'] ?? null;
        if ($token === null) {
            throw new \RuntimeException('No token in login response');
        }

        return ['user' => $user, 'token' => $token];
    }

    public function customerRequest(string $method, string $uri, string $token, ?string $body = null): void
    {
        $server = ['HTTP_AUTHORIZATION' => 'Bearer '.$token];
        if ($body !== null) {
            $server['CONTENT_TYPE'] = 'application/json';
        }
        $this->client->request($method, $uri, [], [], $server, $body);
    }

    public function adminRequest(string $method, string $uri, string $token, ?string $body = null): void
    {
        $server = ['HTTP_AUTHORIZATION' => 'Bearer '.$token];
        if ($body !== null) {
            $server['CONTENT_TYPE'] = 'application/json';
        }
        $this->client->request($method, $uri, [], [], $server, $body);
    }
}
