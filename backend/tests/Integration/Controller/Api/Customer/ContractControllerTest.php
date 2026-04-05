<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Customer;

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
            'price' => '120.00',
            'coursesPerWeek' => 2,
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertArrayHasKey('id', $data);
        self::assertSame('REQUESTED', $data['state']);
        self::assertSame('160.00', $data['price']);
        self::assertSame('160.00', $data['quotedMonthlyPrice']);
        self::assertSame('149.00', $data['registrationFee']);
        self::assertNull($data['endDate']);

        $contractRepo = $container->get(ContractRepository::class);
        $contracts = $contractRepo->findByCustomer($customer);
        self::assertCount(1, $contracts);
        self::assertNull($contracts[0]->getEndDate());
    }

    public function testRequestContractWithoutEndDateKeepsItOpenEnded(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $container = static::getContainer();
        $dogRepo = $container->get(DogRepository::class);
        $dog = new Dog();
        $dog->setCustomer($customer);
        $dog->setName('Open Contract Dog');
        $dogRepo->save($dog);

        $helper->customerRequest(Request::METHOD_POST, '/api/customer/contracts', $token, json_encode([
            'dogId' => $dog->getId(),
            'startDate' => '2025-06-01',
            'price' => '120.00',
            'coursesPerWeek' => 2,
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('endDate', $data);
        self::assertNull($data['endDate']);

        $contractRepo = $container->get(ContractRepository::class);
        $contracts = $contractRepo->findByCustomer($customer);
        self::assertCount(1, $contracts);
        self::assertNull($contracts[0]->getEndDate());
    }

    public function testRequestContractRejectsExplicitEndDate(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $container = static::getContainer();
        $dogRepo = $container->get(DogRepository::class);
        $dog = new Dog();
        $dog->setCustomer($customer);
        $dog->setName('Rejected End Date Dog');
        $dogRepo->save($dog);

        $helper->customerRequest(Request::METHOD_POST, '/api/customer/contracts', $token, json_encode([
            'dogId' => $dog->getId(),
            'startDate' => '2025-06-01',
            'endDate' => '2025-12-31',
            'price' => '120.00',
            'coursesPerWeek' => 2,
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('Enddatum darf bei Vertragsanfragen nicht gesetzt werden.', $data['errors']['endDate'] ?? null);
    }

    public function testRequestContractRejectsNonMonthStartDate(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $container = static::getContainer();
        $dogRepo = $container->get(DogRepository::class);
        $dog = new Dog();
        $dog->setCustomer($customer);
        $dog->setName('Mid Month Dog');
        $dogRepo->save($dog);

        $helper->customerRequest(Request::METHOD_POST, '/api/customer/contracts', $token, json_encode([
            'dogId' => $dog->getId(),
            'startDate' => '2025-06-15',
            'price' => '120.00',
            'coursesPerWeek' => 2,
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('Startdatum muss der erste Tag eines Monats sein.', $data['errors']['startDate'] ?? null);
    }

    public function testPreviewContractReturnsCalculatedPricing(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $container = static::getContainer();
        $dogRepo = $container->get(DogRepository::class);
        $dog = new Dog();
        $dog->setCustomer($customer);
        $dog->setName('Preview Dog');
        $dogRepo->save($dog);

        $helper->customerRequest(Request::METHOD_POST, '/api/customer/contracts/preview', $token, json_encode([
            'dogId' => $dog->getId(),
            'startDate' => '2025-06-01',
            'coursesPerWeek' => 3,
        ]));
        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('228.00', $data['monthlyPrice']);
        self::assertSame('149.00', $data['registrationFee']);
        self::assertSame('377.00', $data['firstInvoiceTotal']);
        self::assertSame('76.00', $data['monthlyUnitPrice']);
        self::assertCount(2, $data['snapshot']['lineItems'] ?? []);
    }

    public function testCustomerCanAcceptAndResubmitRevisedContract(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $container = static::getContainer();
        /** @var \Doctrine\ORM\EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $customerRepository = $container->get(CustomerRepository::class);
        $dogRepo = $container->get(DogRepository::class);
        $contractRepo = $container->get(ContractRepository::class);

        $dog = new Dog();
        $dog->setCustomer($customer);
        $dog->setName('Review Dog');
        $dogRepo->save($dog);
        $entityManager->clear();
        $managedCustomer = $customerRepository->find($customer->getId());
        $managedDog = $dogRepo->find($dog->getId());
        self::assertNotNull($managedCustomer);
        self::assertNotNull($managedDog);

        $acceptContract = new Contract();
        $acceptContract->setCustomer($managedCustomer);
        $acceptContract->setDog($managedDog);
        $acceptContract->setState(ContractState::PENDING_CUSTOMER_APPROVAL);
        $acceptContract->setStartDate(new \DateTimeImmutable('2025-06-01'));
        $acceptContract->setCoursesPerWeek(2);
        $acceptContract->setQuotedMonthlyPrice('160.00');
        $acceptContract->setPrice('184.00');
        $acceptContract->setRegistrationFee('149.00');
        $acceptContract->setAdminComment('Preis erhöht wegen Zusatzwünschen.');
        $contractRepo->save($acceptContract);

        $helper->customerRequest(Request::METHOD_POST, sprintf('/api/customer/contracts/%s/accept-price', $acceptContract->getId()), $token);
        self::assertResponseIsSuccessful();
        $container = static::getContainer();
        $customerRepository = $container->get(CustomerRepository::class);
        $dogRepo = $container->get(DogRepository::class);
        $contractRepo = $container->get(ContractRepository::class);
        $accepted = $contractRepo->find($acceptContract->getId());
        self::assertNotNull($accepted);
        self::assertSame(ContractState::ACTIVE, $accepted->getState());

        $managedCustomer = $customerRepository->find($customer->getId());
        $managedDog = $dogRepo->find($dog->getId());
        self::assertNotNull($managedCustomer);
        self::assertNotNull($managedDog);

        $resubmitContract = new Contract();
        $resubmitContract->setCustomer($managedCustomer);
        $resubmitContract->setDog($managedDog);
        $resubmitContract->setState(ContractState::PENDING_CUSTOMER_APPROVAL);
        $resubmitContract->setStartDate(new \DateTimeImmutable('2025-07-01'));
        $resubmitContract->setCoursesPerWeek(2);
        $resubmitContract->setQuotedMonthlyPrice('160.00');
        $resubmitContract->setPrice('184.00');
        $resubmitContract->setRegistrationFee('149.00');
        $resubmitContract->setAdminComment('Bitte bestätigen.');
        $contractRepo->save($resubmitContract);

        $helper->customerRequest(Request::METHOD_POST, sprintf('/api/customer/contracts/%s/resubmit', $resubmitContract->getId()), $token, json_encode([
            'customerComment' => 'Bitte doch ohne Zusatzleistung prüfen.',
        ]));
        self::assertResponseIsSuccessful();

        $reloaded = $contractRepo->find($resubmitContract->getId());
        self::assertNotNull($reloaded);
        self::assertSame(ContractState::REQUESTED, $reloaded->getState());
        self::assertSame('160.00', $reloaded->getPrice());
        self::assertSame('160.00', $reloaded->getQuotedMonthlyPrice());
        self::assertNull($reloaded->getAdminComment());
        self::assertSame('Bitte doch ohne Zusatzleistung prüfen.', $reloaded->getCustomerComment());
    }

    public function testRequestContractFailsWithWrongDogId(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createCustomerAndLogin();

        $helper->customerRequest(Request::METHOD_POST, '/api/customer/contracts', $token, json_encode([
            'dogId' => '00000000-0000-0000-0000-000000000000',
            'startDate' => '2025-06-01',
            'coursesPerWeek' => 2,
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertArrayHasKey('errors', $data);
        self::assertArrayHasKey('dogId', $data['errors']);
    }
}
