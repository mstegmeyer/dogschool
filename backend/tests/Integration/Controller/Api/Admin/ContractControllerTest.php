<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Admin;

use App\Entity\Contract;
use App\Entity\CreditTransaction;
use App\Entity\Dog;
use App\Enum\ContractState;
use App\Enum\CreditTransactionType;
use App\Repository\ContractRepository;
use App\Repository\CreditTransactionRepository;
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

    public function testApproveWithHigherPriceRequiresCustomerApproval(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();
        ['customer' => $customer] = $helper->createCustomerAndLogin('contract-review-'.uniqid('', true).'@example.com');

        $container = static::getContainer();
        $customerRepo = $container->get(CustomerRepository::class);
        $customer = $customerRepo->find($customer->getId());
        self::assertNotNull($customer);
        $dogRepo = $container->get(DogRepository::class);
        $contractRepo = $container->get(ContractRepository::class);

        $dog = new Dog();
        $dog->setCustomer($customer);
        $dog->setName('Review Dog');
        $dogRepo->save($dog);

        $contract = new Contract();
        $contract->setCustomer($customer);
        $contract->setDog($dog);
        $contract->setState(ContractState::REQUESTED);
        $contract->setStartDate(new \DateTimeImmutable('2025-01-01'));
        $contract->setCoursesPerWeek(2);
        $contract->setPrice('160.00');
        $contract->setQuotedMonthlyPrice('160.00');
        $contractRepo->save($contract);

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/contracts/'.$contract->getId().'/approve', $token, json_encode([
            'price' => '184.00',
            'adminComment' => 'Zusatzwünsche erhöhen den Preis.',
        ]));
        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('PENDING_CUSTOMER_APPROVAL', $data['state']);
        self::assertSame('184.00', $data['price']);
        self::assertSame('160.00', $data['quotedMonthlyPrice']);
        self::assertSame('Zusatzwünsche erhöhen den Preis.', $data['adminComment']);
        self::assertSame('184.00', $data['pricingSnapshot']['monthlyPrice'] ?? null);
        self::assertSame('333.00', $data['pricingSnapshot']['firstInvoiceTotal'] ?? null);
        self::assertContainsEquals([
            'key' => 'manual_adjustment',
            'label' => 'Manuelle Preisanpassung',
            'quantity' => 1,
            'unitPrice' => '24.00',
            'amount' => '24.00',
            'billingPeriod' => 'ONCE',
        ], $data['pricingSnapshot']['lineItems'] ?? []);
    }

    public function testApproveWithHigherRegistrationFeeRequiresCustomerApproval(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();
        ['customer' => $customer] = $helper->createCustomerAndLogin('contract-registration-review-'.uniqid('', true).'@example.com');

        $container = static::getContainer();
        $customerRepo = $container->get(CustomerRepository::class);
        $customer = $customerRepo->find($customer->getId());
        self::assertNotNull($customer);
        $dogRepo = $container->get(DogRepository::class);
        $contractRepo = $container->get(ContractRepository::class);

        $dog = new Dog();
        $dog->setCustomer($customer);
        $dog->setName('Registration Review Dog');
        $dogRepo->save($dog);

        $contract = new Contract();
        $contract->setCustomer($customer);
        $contract->setDog($dog);
        $contract->setState(ContractState::REQUESTED);
        $contract->setStartDate(new \DateTimeImmutable('2025-01-01'));
        $contract->setCoursesPerWeek(2);
        $contract->setPrice('160.00');
        $contract->setQuotedMonthlyPrice('160.00');
        $contract->setRegistrationFee('149.00');
        $contractRepo->save($contract);

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/contracts/'.$contract->getId().'/approve', $token, json_encode([
            'price' => '160.00',
            'registrationFee' => '169.00',
            'adminComment' => 'Anmeldegebühr wegen Zusatzaufwand erhöht.',
        ]));
        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('PENDING_CUSTOMER_APPROVAL', $data['state']);
        self::assertSame('160.00', $data['price']);
        self::assertSame('169.00', $data['registrationFee']);
        self::assertSame('329.00', $data['firstInvoiceTotal']);
        self::assertSame('149.00', $data['pricingSnapshot']['quotedRegistrationFee'] ?? null);
        self::assertSame('169.00', $data['pricingSnapshot']['registrationFee'] ?? null);
        self::assertContainsEquals([
            'key' => 'school_registration_fee',
            'label' => 'Anmeldegebühr',
            'quantity' => 1,
            'unitPrice' => '169.00',
            'amount' => '169.00',
            'billingPeriod' => 'ONCE',
        ], $data['pricingSnapshot']['lineItems'] ?? []);
    }

    public function testApproveAllowsNullOverridesAndClearsAdminComment(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();
        ['customer' => $customer] = $helper->createCustomerAndLogin('contract-null-override-'.uniqid('', true).'@example.com');

        $container = static::getContainer();
        $customerRepo = $container->get(CustomerRepository::class);
        $customer = $customerRepo->find($customer->getId());
        self::assertNotNull($customer);
        $dogRepo = $container->get(DogRepository::class);
        $contractRepo = $container->get(ContractRepository::class);

        $dog = new Dog();
        $dog->setCustomer($customer);
        $dog->setName('Null Override Dog');
        $dogRepo->save($dog);

        $contract = new Contract();
        $contract->setCustomer($customer);
        $contract->setDog($dog);
        $contract->setState(ContractState::REQUESTED);
        $contract->setStartDate(new \DateTimeImmutable('2025-01-01'));
        $contract->setCoursesPerWeek(2);
        $contract->setPrice('160.00');
        $contract->setQuotedMonthlyPrice('160.00');
        $contract->setRegistrationFee('149.00');
        $contract->setAdminComment('Vorhandener Kommentar');
        $contractRepo->save($contract);

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/contracts/'.$contract->getId().'/approve', $token, json_encode([
            'price' => null,
            'registrationFee' => null,
            'adminComment' => null,
        ]));
        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('ACTIVE', $data['state']);
        self::assertSame('160.00', $data['price']);
        self::assertSame('149.00', $data['registrationFee']);
        self::assertNull($data['adminComment']);
    }

    public function testApproveRejectsNegativePrice(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();
        ['customer' => $customer] = $helper->createCustomerAndLogin('contract-negative-price-'.uniqid('', true).'@example.com');

        $container = static::getContainer();
        $customerRepo = $container->get(CustomerRepository::class);
        $customer = $customerRepo->find($customer->getId());
        self::assertNotNull($customer);
        $dogRepo = $container->get(DogRepository::class);
        $contractRepo = $container->get(ContractRepository::class);

        $dog = new Dog();
        $dog->setCustomer($customer);
        $dog->setName('Negative Price Dog');
        $dogRepo->save($dog);

        $contract = new Contract();
        $contract->setCustomer($customer);
        $contract->setDog($dog);
        $contract->setState(ContractState::REQUESTED);
        $contract->setStartDate(new \DateTimeImmutable('2025-01-01'));
        $contract->setCoursesPerWeek(1);
        $contract->setPrice('89.00');
        $contract->setQuotedMonthlyPrice('89.00');
        $contractRepo->save($contract);

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/contracts/'.$contract->getId().'/approve', $token, json_encode([
            'price' => '-10.00',
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('Der Preis darf nicht negativ sein.', $data['errors']['price'] ?? null);
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

    public function testListContractsSupportsCombinedOpenFilter(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();
        ['customer' => $customer] = $helper->createCustomerAndLogin('contract-open-filter-'.uniqid('', true).'@example.com');

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/contracts?page=1&limit=100&state=open', $token);
        self::assertResponseIsSuccessful();
        $before = json_decode($client->getResponse()->getContent() ?: '{}', true);
        $openTotalBefore = (int) ($before['pagination']['total'] ?? 0);

        $container = static::getContainer();
        $customerRepo = $container->get(CustomerRepository::class);
        $customer = $customerRepo->find($customer->getId());
        self::assertNotNull($customer);
        $dogRepo = $container->get(DogRepository::class);
        $contractRepo = $container->get(ContractRepository::class);

        $dog = new Dog();
        $dog->setCustomer($customer);
        $dog->setName('Open Filter Dog');
        $dogRepo->save($dog);

        foreach ([ContractState::REQUESTED, ContractState::PENDING_CUSTOMER_APPROVAL, ContractState::ACTIVE] as $index => $state) {
            $contract = new Contract();
            $contract->setCustomer($customer);
            $contract->setDog($dog);
            $contract->setState($state);
            $contract->setStartDate(new \DateTimeImmutable('2025-02-0'.($index + 1)));
            $contract->setPrice('59.00');
            $contract->setCoursesPerWeek(1);
            $contractRepo->save($contract);
        }

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/contracts?page=1&limit=100&state=open', $token);
        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        $states = array_map(
            static fn (array $item): string => (string) ($item['state'] ?? ''),
            $data['items'] ?? [],
        );
        self::assertSame($openTotalBefore + 2, $data['pagination']['total']);
        self::assertGreaterThanOrEqual(2, count($data['items']));
        self::assertContains('REQUESTED', $states);
        self::assertContains('PENDING_CUSTOMER_APPROVAL', $states);
        foreach ($states as $state) {
            self::assertContains($state, ['REQUESTED', 'PENDING_CUSTOMER_APPROVAL']);
        }
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

    public function testCancelContractSetsEndDateAndCancelledState(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();
        ['customer' => $customer] = $helper->createCustomerAndLogin('contract-cancel-'.uniqid('', true).'@example.com');

        $container = static::getContainer();
        $customerRepo = $container->get(CustomerRepository::class);
        $customer = $customerRepo->find($customer->getId());
        self::assertNotNull($customer);
        $dogRepo = $container->get(DogRepository::class);
        $contractRepo = $container->get(ContractRepository::class);

        $dog = new Dog();
        $dog->setCustomer($customer);
        $dog->setName('Cancel Dog');
        $dogRepo->save($dog);

        $contract = new Contract();
        $contract->setCustomer($customer);
        $contract->setDog($dog);
        $contract->setState(ContractState::ACTIVE);
        $contract->setStartDate(new \DateTimeImmutable('2025-01-01'));
        $contract->setEndDate(null);
        $contract->setPrice('50.00');
        $contract->setCoursesPerWeek(1);
        $contractRepo->save($contract);

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/contracts/'.$contract->getId().'/cancel', $token, json_encode([
            'endDate' => '2025-03-31',
        ]));
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('CANCELLED', $data['state']);
        self::assertSame('2025-03-31', $data['endDate']);

        $reloaded = $contractRepo->find($contract->getId());
        self::assertNotNull($reloaded);
        self::assertSame(ContractState::CANCELLED, $reloaded->getState());
        self::assertSame('2025-03-31', $reloaded->getEndDate()?->format('Y-m-d'));
    }

    public function testCancelContractRequiresEndDate(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();
        ['customer' => $customer] = $helper->createCustomerAndLogin('contract-cancel-empty-'.uniqid('', true).'@example.com');

        $container = static::getContainer();
        $customerRepo = $container->get(CustomerRepository::class);
        $customer = $customerRepo->find($customer->getId());
        self::assertNotNull($customer);
        $dogRepo = $container->get(DogRepository::class);
        $contractRepo = $container->get(ContractRepository::class);

        $dog = new Dog();
        $dog->setCustomer($customer);
        $dog->setName('Missing End Date Dog');
        $dogRepo->save($dog);

        $contract = new Contract();
        $contract->setCustomer($customer);
        $contract->setDog($dog);
        $contract->setState(ContractState::ACTIVE);
        $contract->setStartDate(new \DateTimeImmutable('2025-01-01'));
        $contract->setEndDate(null);
        $contract->setPrice('50.00');
        $contract->setCoursesPerWeek(1);
        $contractRepo->save($contract);

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/contracts/'.$contract->getId().'/cancel', $token, json_encode([]));
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('Enddatum ist erforderlich.', $data['errors']['endDate'] ?? null);
    }

    public function testCancelContractRejectsNonMonthEndDate(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();
        ['customer' => $customer] = $helper->createCustomerAndLogin('contract-cancel-midmonth-'.uniqid('', true).'@example.com');

        $container = static::getContainer();
        $customerRepo = $container->get(CustomerRepository::class);
        $customer = $customerRepo->find($customer->getId());
        self::assertNotNull($customer);
        $dogRepo = $container->get(DogRepository::class);
        $contractRepo = $container->get(ContractRepository::class);

        $dog = new Dog();
        $dog->setCustomer($customer);
        $dog->setName('Mid Month Cancellation Dog');
        $dogRepo->save($dog);

        $contract = new Contract();
        $contract->setCustomer($customer);
        $contract->setDog($dog);
        $contract->setState(ContractState::ACTIVE);
        $contract->setStartDate(new \DateTimeImmutable('2025-01-01'));
        $contract->setEndDate(null);
        $contract->setPrice('50.00');
        $contract->setCoursesPerWeek(1);
        $contractRepo->save($contract);

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/contracts/'.$contract->getId().'/cancel', $token, json_encode([
            'endDate' => '2025-03-15',
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('Enddatum muss der letzte Tag eines Monats sein.', $data['errors']['endDate'] ?? null);
    }

    public function testCancelContractReversesFutureCreditsAndKeepsBalanceNonNegative(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();
        ['customer' => $customer] = $helper->createCustomerAndLogin('contract-cancel-credits-'.uniqid('', true).'@example.com');

        $container = static::getContainer();
        $customerRepo = $container->get(CustomerRepository::class);
        $customer = $customerRepo->find($customer->getId());
        self::assertNotNull($customer);
        $dogRepo = $container->get(DogRepository::class);
        $contractRepo = $container->get(ContractRepository::class);
        $creditTransactionRepo = $container->get(CreditTransactionRepository::class);

        $dog = new Dog();
        $dog->setCustomer($customer);
        $dog->setName('Future Credits Dog');
        $dogRepo->save($dog);

        $contract = new Contract();
        $contract->setCustomer($customer);
        $contract->setDog($dog);
        $contract->setState(ContractState::ACTIVE);
        $contract->setStartDate(new \DateTimeImmutable('2025-01-01'));
        $contract->setEndDate(null);
        $contract->setPrice('89.00');
        $contract->setCoursesPerWeek(2);
        $contractRepo->save($contract);

        foreach (['2025-W15', '2025-W16'] as $weekRef) {
            $grant = new CreditTransaction();
            $grant->setCustomer($customer);
            $grant->setAmount(2);
            $grant->setType(CreditTransactionType::WEEKLY_GRANT);
            $grant->setContract($contract);
            $grant->setWeekRef($weekRef);
            $grant->setDescription('Future weekly grant '.$weekRef);
            $creditTransactionRepo->save($grant);
        }

        foreach (range(1, 4) as $bookingNumber) {
            $spent = new CreditTransaction();
            $spent->setCustomer($customer);
            $spent->setAmount(-1);
            $spent->setType(CreditTransactionType::BOOKING);
            $spent->setDescription('Spent future credit #'.$bookingNumber);
            $creditTransactionRepo->save($spent);
        }

        self::assertSame(0, $creditTransactionRepo->getBalance($customer));

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/contracts/'.$contract->getId().'/cancel', $token, json_encode([
            'endDate' => '2025-03-31',
        ]));
        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('CANCELLED', $data['state']);
        self::assertSame('2025-03-31', $data['endDate']);

        $reloaded = $contractRepo->find($contract->getId());
        self::assertNotNull($reloaded);
        self::assertSame(ContractState::CANCELLED, $reloaded->getState());
        self::assertSame('2025-03-31', $reloaded->getEndDate()?->format('Y-m-d'));
        self::assertSame(0, $creditTransactionRepo->getBalance($customer));

        $transactions = $creditTransactionRepo->findByCustomer($customer, 20);
        $reversalTransactions = array_values(array_filter(
            $transactions,
            static fn (CreditTransaction $tx): bool => $tx->getContract()?->getId() === $contract->getId()
                && $tx->getAmount() === -4
                && str_contains($tx->getDescription(), 'Vertragsende 31.03.2025'),
        ));
        self::assertCount(1, $reversalTransactions);

        $fixTransactions = array_values(array_filter(
            $transactions,
            static fn (CreditTransaction $tx): bool => $tx->getAmount() === 4
                && $tx->getType() === CreditTransactionType::MANUAL_ADJUSTMENT
                && $tx->getDescription() === 'Korrektur nach Vertragsende: Guthaben nicht negativ.',
        ));
        self::assertCount(1, $fixTransactions);
    }

    public function testCancelContractRejectsNonActiveContract(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();
        ['customer' => $customer] = $helper->createCustomerAndLogin('contract-cancel-requested-'.uniqid('', true).'@example.com');

        $container = static::getContainer();
        $customerRepo = $container->get(CustomerRepository::class);
        $customer = $customerRepo->find($customer->getId());
        self::assertNotNull($customer);
        $dogRepo = $container->get(DogRepository::class);
        $contractRepo = $container->get(ContractRepository::class);

        $dog = new Dog();
        $dog->setCustomer($customer);
        $dog->setName('Requested Dog');
        $dogRepo->save($dog);

        $contract = new Contract();
        $contract->setCustomer($customer);
        $contract->setDog($dog);
        $contract->setState(ContractState::REQUESTED);
        $contract->setStartDate(new \DateTimeImmutable('2025-01-01'));
        $contract->setEndDate(null);
        $contract->setPrice('50.00');
        $contract->setCoursesPerWeek(1);
        $contractRepo->save($contract);

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/contracts/'.$contract->getId().'/cancel', $token, json_encode([
            'endDate' => '2025-03-31',
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('Nur aktive Verträge können gekündigt werden', $data['error'] ?? null);

        $reloaded = $contractRepo->find($contract->getId());
        self::assertNotNull($reloaded);
        self::assertSame(ContractState::REQUESTED, $reloaded->getState());
        self::assertNull($reloaded->getEndDate());
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
