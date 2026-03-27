<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Admin;

use App\Entity\Contract;
use App\Entity\Dog;
use App\Enum\ContractState;
use App\Repository\ContractRepository;
use App\Repository\CustomerRepository;
use App\Repository\DogRepository;
use App\Tests\Helper\ApiTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ContractControllerTest extends WebTestCase
{
    public function testListContractsRequiresAdminAuth(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/api/admin/contracts');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetAndApproveContract(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();
        ['customer' => $customer] = $helper->createCustomerAndLogin('contract-admin-'.uniqid('', true).'@example.com');

        $container = static::getContainer();
        $customerRepo = $container->get(CustomerRepository::class);
        $customer = $customerRepo->find($customer->getId());
        self::assertNotNull($customer);
        $dogRepo = $container->get(DogRepository::class);
        $contractRepo = $container->get(ContractRepository::class);
        $dog = new Dog();
        $dog->setCustomer($customer);
        $dog->setName('Dog');
        $dogRepo->save($dog);

        $contract = new Contract();
        $contract->setCustomer($customer);
        $contract->setDog($dog);
        $contract->setState(ContractState::REQUESTED);
        $contract->setStartDate(new \DateTimeImmutable('2025-01-01'));
        $contract->setEndDate(new \DateTimeImmutable('2026-01-01'));
        $contract->setPrice('99.00');
        $contract->setCoursesPerWeek(1);
        $contractRepo->save($contract);

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/contracts', $token);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertArrayHasKey('items', $data);
        self::assertGreaterThanOrEqual(1, count($data['items']));

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/contracts/'.$contract->getId(), $token);
        self::assertResponseIsSuccessful();
        $one = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('REQUESTED', $one['state']);

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/contracts/'.$contract->getId().'/approve', $token);
        self::assertResponseIsSuccessful();
        $updated = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('ACTIVE', $updated['state']);

        $reloaded = $contractRepo->find($contract->getId());
        self::assertNotNull($reloaded);
        self::assertSame(ContractState::ACTIVE, $reloaded->getState());
    }

    public function testListContractsSupportsPaginationAndStateFilter(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();
        ['customer' => $customer] = $helper->createCustomerAndLogin('contract-page-'.uniqid('', true).'@example.com');

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/contracts?page=1&limit=1&state=ACTIVE', $token);
        self::assertResponseIsSuccessful();
        $before = json_decode($client->getResponse()->getContent() ?: '{}', true);
        $activeTotalBefore = (int) ($before['pagination']['total'] ?? 0);

        $container = static::getContainer();
        $customerRepo = $container->get(CustomerRepository::class);
        $customer = $customerRepo->find($customer->getId());
        self::assertNotNull($customer);
        $dogRepo = $container->get(DogRepository::class);
        $contractRepo = $container->get(ContractRepository::class);

        $dog = new Dog();
        $dog->setCustomer($customer);
        $dog->setName('Paged Dog');
        $dogRepo->save($dog);

        foreach ([ContractState::ACTIVE, ContractState::ACTIVE, ContractState::REQUESTED] as $index => $state) {
            $contract = new Contract();
            $contract->setCustomer($customer);
            $contract->setDog($dog);
            $contract->setState($state);
            $contract->setStartDate(new \DateTimeImmutable('2025-01-0'.($index + 1)));
            $contract->setEndDate(new \DateTimeImmutable('2026-01-01'));
            $contract->setPrice('49.00');
            $contract->setCoursesPerWeek(1);
            $contractRepo->save($contract);
        }

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/contracts?page=1&limit=1&state=ACTIVE', $token);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertCount(1, $data['items']);
        self::assertSame('ACTIVE', $data['items'][0]['state']);
        self::assertSame($activeTotalBefore + 2, $data['pagination']['total']);
        self::assertSame((int) ceil(($activeTotalBefore + 2) / 1), $data['pagination']['pages']);
    }

    public function testDeclineContract(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();
        ['customer' => $customer] = $helper->createCustomerAndLogin('contract-decline-'.uniqid('', true).'@example.com');

        $container = static::getContainer();
        $customerRepo = $container->get(CustomerRepository::class);
        $customer = $customerRepo->find($customer->getId());
        self::assertNotNull($customer);
        $dogRepo = $container->get(DogRepository::class);
        $contractRepo = $container->get(ContractRepository::class);
        $dog = new Dog();
        $dog->setCustomer($customer);
        $dog->setName('Dog');
        $dogRepo->save($dog);

        $contract = new Contract();
        $contract->setCustomer($customer);
        $contract->setDog($dog);
        $contract->setState(ContractState::REQUESTED);
        $contract->setStartDate(new \DateTimeImmutable('2025-01-01'));
        $contract->setEndDate(new \DateTimeImmutable('2026-01-01'));
        $contract->setPrice('50.00');
        $contract->setCoursesPerWeek(1);
        $contractRepo->save($contract);

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/contracts/'.$contract->getId().'/decline', $token);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('DECLINED', $data['state']);
    }

    public function testGetContractReturns404ForUnknownId(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/contracts/00000000-0000-0000-0000-000000000000', $token);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
