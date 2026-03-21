<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Customer;

use App\Entity\CreditTransaction;
use App\Enum\CreditTransactionType;
use App\Repository\CreditTransactionRepository;
use App\Tests\Helper\ApiTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CreditControllerTest extends WebTestCase
{
    public function testListCreditsRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/api/customer/credits');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListCreditsReturnsBalanceAndHistory(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $container = static::getContainer();
        $txRepo = $container->get(CreditTransactionRepository::class);
        $tx = new CreditTransaction();
        $tx->setCustomer($customer);
        $tx->setAmount(10);
        $tx->setType(CreditTransactionType::MANUAL_ADJUSTMENT);
        $tx->setDescription('Welcome bonus');
        $txRepo->save($tx);

        $helper->customerRequest(Request::METHOD_GET, '/api/customer/credits', $token);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame(10, $data['balance']);
        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('nextWeeklyGrants', $data);
        self::assertGreaterThanOrEqual(1, count($data['items']));
    }

    public function testListCreditsReturnsZeroBalanceForNewCustomer(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createCustomerAndLogin();

        $helper->customerRequest(Request::METHOD_GET, '/api/customer/credits', $token);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame(0, $data['balance']);
        self::assertSame([], $data['items']);
    }
}
