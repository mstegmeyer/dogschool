<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Customer;

use App\Entity\Dog;
use App\Entity\HotelBooking;
use App\Entity\Room;
use App\Enum\HotelBookingState;
use App\Repository\CustomerRepository;
use App\Repository\DogRepository;
use App\Tests\Helper\ApiTestHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class HotelBookingControllerTest extends WebTestCase
{
    public function testListHotelBookingsReturnsEmpty(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createCustomerAndLogin();

        $helper->customerRequest(Request::METHOD_GET, '/api/customer/hotel-bookings', $token);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame([], $data['items']);
    }

    public function testCreateHotelBookingAndUpdateDogHeight(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $container = static::getContainer();
        $customerRepository = $container->get(CustomerRepository::class);
        $dogRepository = $container->get(DogRepository::class);
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');
        /** @var \App\Repository\HotelBookingRepository $hotelBookingRepository */
        $hotelBookingRepository = $entityManager->getRepository(HotelBooking::class);

        $dog = (new Dog())
            ->setCustomer($customer)
            ->setName('Puppy')
            ->setShoulderHeightCm(35);
        $dogRepository->save($dog);

        $helper->customerRequest(Request::METHOD_POST, '/api/customer/hotel-bookings', $token, json_encode([
            'dogId' => $dog->getId(),
            'startAt' => '2026-04-05T08:30',
            'endAt' => '2026-04-06T10:00',
            'currentShoulderHeightCm' => 44,
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('REQUESTED', $data['state']);
        self::assertSame('Puppy', $data['dogName']);
        self::assertSame(44, $data['dogShoulderHeightCm']);
        self::assertSame('2026-04-05T08:30:00+02:00', $data['startAt']);
        self::assertSame('2026-04-06T10:00:00+02:00', $data['endAt']);

        $bookings = $hotelBookingRepository->findByCustomer($customer);
        self::assertCount(1, $bookings);
        self::assertSame('REQUESTED', $bookings[0]->getState()->value);
        self::assertSame(44, $dogRepository->find($dog->getId())?->getShoulderHeightCm());

        $helper->customerRequest(Request::METHOD_GET, '/api/customer/hotel-bookings', $token);
        self::assertResponseIsSuccessful();
        $listData = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('2026-04-05T08:30:00+02:00', $listData['items'][0]['startAt'] ?? null);
        self::assertSame('2026-04-06T10:00:00+02:00', $listData['items'][0]['endAt'] ?? null);
    }

    public function testCreateHotelBookingRejectsInvalidStartWindow(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $container = static::getContainer();
        $dogRepository = $container->get(DogRepository::class);

        $dog = (new Dog())
            ->setCustomer($customer)
            ->setName('Early Bird')
            ->setShoulderHeightCm(52);
        $dogRepository->save($dog);

        $helper->customerRequest(Request::METHOD_POST, '/api/customer/hotel-bookings', $token, json_encode([
            'dogId' => $dog->getId(),
            'startAt' => '2026-04-05T05:30',
            'endAt' => '2026-04-05T09:00',
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        self::assertStringContainsString(
            'Beginn muss zwischen 06:00 und 22:00 Uhr liegen.',
            $client->getResponse()->getContent() ?: '',
        );
    }

    public function testCreateHotelBookingRejectsInvalidEndWindow(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $container = static::getContainer();
        $dogRepository = $container->get(DogRepository::class);

        $dog = (new Dog())
            ->setCustomer($customer)
            ->setName('Night Owl')
            ->setShoulderHeightCm(44);
        $dogRepository->save($dog);

        $helper->customerRequest(Request::METHOD_POST, '/api/customer/hotel-bookings', $token, json_encode([
            'dogId' => $dog->getId(),
            'startAt' => '2026-04-05T08:30',
            'endAt' => '2026-04-06T05:45',
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        self::assertStringContainsString(
            'Ende muss zwischen 06:00 und 22:00 Uhr liegen.',
            $client->getResponse()->getContent() ?: '',
        );
    }

    public function testCreateHotelBookingRejectsInvalidShoulderHeightUpdate(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $container = static::getContainer();
        $dogRepository = $container->get(DogRepository::class);

        $dog = (new Dog())
            ->setCustomer($customer)
            ->setName('Tiny')
            ->setShoulderHeightCm(42);
        $dogRepository->save($dog);

        $helper->customerRequest(Request::METHOD_POST, '/api/customer/hotel-bookings', $token, json_encode([
            'dogId' => $dog->getId(),
            'startAt' => '2026-04-05T08:30',
            'endAt' => '2026-04-06T10:00',
            'currentShoulderHeightCm' => -5,
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        self::assertStringContainsString(
            'Schulterhöhe muss zwischen 1 und 200 cm liegen.',
            $client->getResponse()->getContent() ?: '',
        );
        self::assertSame(42, $dogRepository->find($dog->getId())?->getShoulderHeightCm());
    }

    public function testPreviewHotelBookingIncludesServiceFeeAndTravelProtection(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $container = static::getContainer();
        $dogRepository = $container->get(DogRepository::class);

        $dog = (new Dog())
            ->setCustomer($customer)
            ->setName('Preview')
            ->setShoulderHeightCm(52);
        $dogRepository->save($dog);

        $helper->customerRequest(Request::METHOD_POST, '/api/customer/hotel-bookings/preview', $token, json_encode([
            'dogId' => $dog->getId(),
            'startAt' => '2026-04-05T08:30',
            'endAt' => '2026-04-05T18:00',
            'includesTravelProtection' => true,
        ]));
        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('DAYCARE', $data['pricingKind']);
        self::assertSame(1, $data['billableDays']);
        self::assertSame('46.00', $data['baseDailyPrice']);
        self::assertSame('7.50', $data['serviceFee']);
        self::assertSame('49.00', $data['travelProtectionPrice']);
        self::assertSame('102.50', $data['quotedTotalPrice']);
    }

    public function testCreateHotelBookingRejectsUnknownDog(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createCustomerAndLogin();
        ['customer' => $otherCustomer] = $helper->createCustomerAndLogin();

        /** @var CustomerRepository $customerRepository */
        $customerRepository = static::getContainer()->get(CustomerRepository::class);
        $managedOtherCustomer = $customerRepository->find($otherCustomer->getId());
        self::assertNotNull($managedOtherCustomer);

        $otherDog = $this->createDog($managedOtherCustomer, 'Other Customers Dog', 51);

        $helper->customerRequest(Request::METHOD_POST, '/api/customer/hotel-bookings', $token, json_encode([
            'dogId' => $otherDog->getId(),
            'startAt' => '2026-04-05T08:30',
            'endAt' => '2026-04-06T10:00',
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('Ungültiger Hund oder nicht Ihr Hund.', $data['errors']['dogId'] ?? null);
    }

    public function testCustomerCanAcceptAndResubmitRevisedHotelBooking(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $container = static::getContainer();
        $customerRepository = $container->get(CustomerRepository::class);
        $dogRepository = $container->get(DogRepository::class);
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');
        /** @var \App\Repository\HotelBookingRepository $hotelBookingRepository */
        $hotelBookingRepository = $entityManager->getRepository(HotelBooking::class);

        $dog = (new Dog())
            ->setCustomer($customer)
            ->setName('Review')
            ->setShoulderHeightCm(50);
        $dogRepository->save($dog);

        $room = new Room();
        $room->setName('Review Room');
        $room->setSquareMeters(20);
        $entityManager->persist($room);
        $entityManager->flush();
        $entityManager->clear();
        $managedCustomer = $customerRepository->find($customer->getId());
        $managedDog = $dogRepository->find($dog->getId());
        $managedRoom = $entityManager->getRepository(Room::class)->find($room->getId());
        self::assertNotNull($managedCustomer);
        self::assertNotNull($managedDog);
        self::assertNotNull($managedRoom);

        $acceptBooking = (new HotelBooking())
            ->setCustomer($managedCustomer)
            ->setDog($managedDog)
            ->setRoom($managedRoom)
            ->setState(HotelBookingState::PENDING_CUSTOMER_APPROVAL)
            ->setStartAt(new \DateTimeImmutable('2026-04-16T08:00:00+02:00'))
            ->setEndAt(new \DateTimeImmutable('2026-04-17T10:00:00+02:00'))
            ->setQuotedTotalPrice('123.50')
            ->setTotalPrice('148.50')
            ->setAdminComment('Preis erhöht wegen Zusatzwünschen.');
        $hotelBookingRepository->save($acceptBooking);

        $helper->customerRequest(Request::METHOD_POST, sprintf('/api/customer/hotel-bookings/%s/accept-price', $acceptBooking->getId()), $token);
        self::assertResponseIsSuccessful();
        $container = static::getContainer();
        $customerRepository = $container->get(CustomerRepository::class);
        $dogRepository = $container->get(DogRepository::class);
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');
        /** @var \App\Repository\HotelBookingRepository $hotelBookingRepository */
        $hotelBookingRepository = $entityManager->getRepository(HotelBooking::class);
        $accepted = $hotelBookingRepository->find($acceptBooking->getId());
        self::assertNotNull($accepted);
        self::assertSame(HotelBookingState::CONFIRMED, $accepted->getState());

        $managedCustomer = $customerRepository->find($customer->getId());
        $managedDog = $dogRepository->find($dog->getId());
        $managedRoom = $entityManager->getRepository(Room::class)->find($room->getId());
        self::assertNotNull($managedCustomer);
        self::assertNotNull($managedDog);
        self::assertNotNull($managedRoom);

        $resubmitBooking = (new HotelBooking())
            ->setCustomer($managedCustomer)
            ->setDog($managedDog)
            ->setRoom($managedRoom)
            ->setState(HotelBookingState::PENDING_CUSTOMER_APPROVAL)
            ->setStartAt(new \DateTimeImmutable('2026-04-16T08:00:00+02:00'))
            ->setEndAt(new \DateTimeImmutable('2026-04-17T10:00:00+02:00'))
            ->setIncludesTravelProtection(true)
            ->setQuotedTotalPrice('172.50')
            ->setTotalPrice('197.50')
            ->setAdminComment('Bitte bestätigen.');
        $hotelBookingRepository->save($resubmitBooking);

        $helper->customerRequest(Request::METHOD_POST, sprintf('/api/customer/hotel-bookings/%s/resubmit', $resubmitBooking->getId()), $token, json_encode([
            'customerComment' => 'Bitte ohne Einzelzimmer neu prüfen.',
        ]));
        self::assertResponseIsSuccessful();

        $reloaded = $hotelBookingRepository->find($resubmitBooking->getId());
        self::assertNotNull($reloaded);
        self::assertSame(HotelBookingState::REQUESTED, $reloaded->getState());
        self::assertSame('172.50', $reloaded->getQuotedTotalPrice());
        self::assertSame('172.50', $reloaded->getTotalPrice());
        self::assertNull($reloaded->getAdminComment());
        self::assertSame('Bitte ohne Einzelzimmer neu prüfen.', $reloaded->getCustomerComment());
        self::assertSame($managedRoom->getId(), $reloaded->getRoom()?->getId());
    }

    public function testAcceptPriceReturnsNotFoundForUnknownBooking(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createCustomerAndLogin();

        $helper->customerRequest(Request::METHOD_POST, '/api/customer/hotel-bookings/unknown-booking/accept-price', $token);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('Hotelbuchung nicht gefunden', $data['error'] ?? null);
    }

    public function testAcceptPriceRejectsBookingWithoutCustomerReviewState(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $dog = $this->createDog($customer, 'Wrong State Booking Dog', 48);
        $booking = $this->createBooking($customer, $dog, HotelBookingState::REQUESTED);

        $helper->customerRequest(Request::METHOD_POST, sprintf('/api/customer/hotel-bookings/%s/accept-price', $booking->getId()), $token);
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('Nur angepasste Buchungen können bestätigt werden', $data['error'] ?? null);
    }

    public function testAcceptPriceRejectsBookingWithoutAssignedRoom(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $dog = $this->createDog($customer, 'No Room Dog', 46);
        $booking = $this->createBooking($customer, $dog, HotelBookingState::PENDING_CUSTOMER_APPROVAL);

        $helper->customerRequest(Request::METHOD_POST, sprintf('/api/customer/hotel-bookings/%s/accept-price', $booking->getId()), $token);
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('Vor dem Bestätigen muss ein Raum zugewiesen werden.', $data['errors']['roomId'] ?? null);
    }

    public function testCustomerCanDeclineRevisedHotelBooking(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $dog = $this->createDog($customer, 'Decline Booking Dog', 55);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $room = new Room();
        $room->setName('Decline Room');
        $room->setSquareMeters(18);
        $entityManager->persist($room);
        $entityManager->flush();

        $booking = $this->createBooking($customer, $dog, HotelBookingState::PENDING_CUSTOMER_APPROVAL);
        $booking->setRoom($room);
        /** @var \App\Repository\HotelBookingRepository $hotelBookingRepository */
        $hotelBookingRepository = $entityManager->getRepository(HotelBooking::class);
        $hotelBookingRepository->save($booking);

        $helper->customerRequest(Request::METHOD_POST, sprintf('/api/customer/hotel-bookings/%s/decline-price', $booking->getId()), $token);
        self::assertResponseIsSuccessful();

        $reloaded = $hotelBookingRepository->find($booking->getId());
        self::assertNotNull($reloaded);
        self::assertSame(HotelBookingState::DECLINED, $reloaded->getState());
        self::assertNull($reloaded->getRoom());
    }

    public function testResubmitRejectsBookingWithoutCustomerReviewState(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $dog = $this->createDog($customer, 'Resubmit Wrong State Booking Dog', 42);
        $booking = $this->createBooking($customer, $dog, HotelBookingState::REQUESTED);

        $helper->customerRequest(Request::METHOD_POST, sprintf('/api/customer/hotel-bookings/%s/resubmit', $booking->getId()), $token, json_encode([
            'customerComment' => 'Bitte neu prüfen.',
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('Nur angepasste Buchungen können erneut eingereicht werden', $data['error'] ?? null);
    }

    public function testAcceptPriceRejectsBookingWhenAssignedRoomIsNoLongerAvailable(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $container = static::getContainer();
        $dogRepository = $container->get(DogRepository::class);
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');
        /** @var \App\Repository\HotelBookingRepository $hotelBookingRepository */
        $hotelBookingRepository = $entityManager->getRepository(HotelBooking::class);

        $pendingDog = (new Dog())
            ->setCustomer($customer)
            ->setName('Pending Review')
            ->setShoulderHeightCm(45);
        $dogRepository->save($pendingDog);

        $existingDog = (new Dog())
            ->setCustomer($customer)
            ->setName('Existing Occupant')
            ->setShoulderHeightCm(60);
        $dogRepository->save($existingDog);

        $room = new Room();
        $room->setName('Tight Room');
        $room->setSquareMeters(10);
        $entityManager->persist($room);
        $entityManager->flush();

        $pendingBooking = (new HotelBooking())
            ->setCustomer($customer)
            ->setDog($pendingDog)
            ->setRoom($room)
            ->setState(HotelBookingState::PENDING_CUSTOMER_APPROVAL)
            ->setStartAt(new \DateTimeImmutable('2026-04-16T08:00:00+02:00'))
            ->setEndAt(new \DateTimeImmutable('2026-04-17T10:00:00+02:00'))
            ->setQuotedTotalPrice('123.50')
            ->setTotalPrice('148.50')
            ->setAdminComment('Preis erhöht wegen Zusatzwünschen.');
        $hotelBookingRepository->save($pendingBooking);

        $existingBooking = (new HotelBooking())
            ->setCustomer($customer)
            ->setDog($existingDog)
            ->setRoom($room)
            ->setState(HotelBookingState::CONFIRMED)
            ->setStartAt(new \DateTimeImmutable('2026-04-16T09:00:00+02:00'))
            ->setEndAt(new \DateTimeImmutable('2026-04-17T09:00:00+02:00'));
        $hotelBookingRepository->save($existingBooking);

        $helper->customerRequest(Request::METHOD_POST, sprintf('/api/customer/hotel-bookings/%s/accept-price', $pendingBooking->getId()), $token);
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('Der zugewiesene Raum ist nicht mehr verfügbar.', $data['errors']['roomId'] ?? null);

        $reloaded = $hotelBookingRepository->find($pendingBooking->getId());
        self::assertNotNull($reloaded);
        self::assertSame(HotelBookingState::PENDING_CUSTOMER_APPROVAL, $reloaded->getState());
    }

    public function testCreateHotelBookingDoesNotPersistHeightWhenOverlapRejectsRequest(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

        $container = static::getContainer();
        $dogRepository = $container->get(DogRepository::class);
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');
        /** @var \App\Repository\HotelBookingRepository $hotelBookingRepository */
        $hotelBookingRepository = $entityManager->getRepository(HotelBooking::class);

        $dog = (new Dog())
            ->setCustomer($customer)
            ->setName('Overlap')
            ->setShoulderHeightCm(41);
        $dogRepository->save($dog);

        $existingBooking = (new HotelBooking())
            ->setCustomer($customer)
            ->setDog($dog)
            ->setStartAt(new \DateTimeImmutable('2026-04-05T08:00:00+02:00'))
            ->setEndAt(new \DateTimeImmutable('2026-04-06T10:00:00+02:00'))
            ->setState(HotelBookingState::REQUESTED);
        $hotelBookingRepository->save($existingBooking);

        $helper->customerRequest(Request::METHOD_POST, '/api/customer/hotel-bookings', $token, json_encode([
            'dogId' => $dog->getId(),
            'startAt' => '2026-04-05T09:00',
            'endAt' => '2026-04-06T11:00',
            'currentShoulderHeightCm' => 55,
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame(
            'Für diesen Hund existiert bereits eine überlappende Hotelbuchung.',
            $data['errors']['startAt'] ?? null,
        );
        self::assertSame(41, $dogRepository->find($dog->getId())?->getShoulderHeightCm());
    }

    private function createDog(\App\Entity\Customer $customer, string $name, int $shoulderHeightCm): Dog
    {
        /** @var DogRepository $dogRepository */
        $dogRepository = static::getContainer()->get(DogRepository::class);

        $dog = (new Dog())
            ->setCustomer($customer)
            ->setName($name)
            ->setShoulderHeightCm($shoulderHeightCm);
        $dogRepository->save($dog);

        return $dog;
    }

    private function createBooking(\App\Entity\Customer $customer, Dog $dog, HotelBookingState $state): HotelBooking
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        /** @var \App\Repository\HotelBookingRepository $hotelBookingRepository */
        $hotelBookingRepository = $entityManager->getRepository(HotelBooking::class);

        $booking = (new HotelBooking())
            ->setCustomer($customer)
            ->setDog($dog)
            ->setState($state)
            ->setStartAt(new \DateTimeImmutable('2026-04-16T08:00:00+02:00'))
            ->setEndAt(new \DateTimeImmutable('2026-04-17T10:00:00+02:00'))
            ->setQuotedTotalPrice('123.50')
            ->setTotalPrice('148.50')
            ->setAdminComment('Preis erhöht wegen Zusatzwünschen.');
        $hotelBookingRepository->save($booking);

        return $booking;
    }
}
