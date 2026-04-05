<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Admin\Hotel;

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

final class BookingControllerTest extends WebTestCase
{
    public function testListAssignAndConfirmHotelBooking(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();
        ['customer' => $customer] = $helper->createCustomerAndLogin('hotel-booking-'.uniqid('', true).'@example.com');

        $container = static::getContainer();
        $customerRepository = $container->get(CustomerRepository::class);
        $dogRepository = $container->get(DogRepository::class);
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');
        /** @var \App\Repository\RoomRepository $roomRepository */
        $roomRepository = $entityManager->getRepository(Room::class);
        /** @var \App\Repository\HotelBookingRepository $hotelBookingRepository */
        $hotelBookingRepository = $entityManager->getRepository(HotelBooking::class);

        $managedCustomer = $customerRepository->find($customer->getId());
        self::assertNotNull($managedCustomer);

        $dog = (new Dog())
            ->setCustomer($managedCustomer)
            ->setName('Hotel Dog')
            ->setShoulderHeightCm(62);
        $dogRepository->save($dog);

        $room = (new Room())
            ->setName('Blue Room')
            ->setSquareMeters(12);
        $roomRepository->save($room);

        $booking = (new HotelBooking())
            ->setCustomer($managedCustomer)
            ->setDog($dog)
            ->setStartAt(new \DateTimeImmutable('2026-05-05 08:00'))
            ->setEndAt(new \DateTimeImmutable('2026-05-06 10:00'))
            ->setState(HotelBookingState::REQUESTED);
        $hotelBookingRepository->save($booking);

        $otherBooking = (new HotelBooking())
            ->setCustomer($managedCustomer)
            ->setDog($dog)
            ->setStartAt(new \DateTimeImmutable('2026-05-10 08:00'))
            ->setEndAt(new \DateTimeImmutable('2026-05-11 10:00'))
            ->setState(HotelBookingState::REQUESTED);
        $hotelBookingRepository->save($otherBooking);

        $helper->adminRequest(
            Request::METHOD_GET,
            '/api/admin/hotel/bookings?state=REQUESTED&page=1&limit=20&from=2026-05-05T00:00&to=2026-05-07T00:00',
            $token,
        );
        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame($booking->getId(), $list['items'][0]['id']);
        self::assertSame(1, $list['pagination']['total'] ?? null);
        self::assertSame(1, $list['pagination']['page'] ?? null);
        self::assertSame(20, $list['pagination']['limit'] ?? null);

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/hotel/bookings/'.$booking->getId(), $token);
        self::assertResponseIsSuccessful();
        $detail = json_decode($client->getResponse()->getContent() ?: '{}', true);
        $matchingRoom = array_values(array_filter(
            $detail['availableRooms'],
            static fn (array $roomOption): bool => ($roomOption['roomId'] ?? null) === $room->getId(),
        ));
        self::assertCount(1, $matchingRoom);
        self::assertTrue($matchingRoom[0]['available']);

        $helper->adminRequest(Request::METHOD_PUT, '/api/admin/hotel/bookings/'.$booking->getId().'/room', $token, json_encode([
            'roomId' => $room->getId(),
        ]));
        self::assertResponseIsSuccessful();
        $assigned = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame($room->getId(), $assigned['roomId']);

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/hotel/bookings/'.$booking->getId().'/confirm', $token);
        self::assertResponseIsSuccessful();
        $confirmed = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('CONFIRMED', $confirmed['state']);
        self::assertSame('Blue Room', $confirmed['roomName']);
    }

    public function testConfirmWithHigherPriceRequiresCustomerApproval(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();
        ['customer' => $customer] = $helper->createCustomerAndLogin('hotel-review-'.uniqid('', true).'@example.com');

        $container = static::getContainer();
        $customerRepository = $container->get(CustomerRepository::class);
        $dogRepository = $container->get(DogRepository::class);
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');
        /** @var \App\Repository\RoomRepository $roomRepository */
        $roomRepository = $entityManager->getRepository(Room::class);
        /** @var \App\Repository\HotelBookingRepository $hotelBookingRepository */
        $hotelBookingRepository = $entityManager->getRepository(HotelBooking::class);

        $managedCustomer = $customerRepository->find($customer->getId());
        self::assertNotNull($managedCustomer);

        $dog = (new Dog())
            ->setCustomer($managedCustomer)
            ->setName('Review Dog')
            ->setShoulderHeightCm(58);
        $dogRepository->save($dog);

        $room = (new Room())
            ->setName('Review Room '.uniqid('', true))
            ->setSquareMeters(16);
        $roomRepository->save($room);

        $booking = (new HotelBooking())
            ->setCustomer($managedCustomer)
            ->setDog($dog)
            ->setRoom($room)
            ->setStartAt(new \DateTimeImmutable('2026-05-05 08:00'))
            ->setEndAt(new \DateTimeImmutable('2026-05-06 10:00'))
            ->setState(HotelBookingState::REQUESTED);
        $hotelBookingRepository->save($booking);

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/hotel/bookings/'.$booking->getId().'/confirm', $token, json_encode([
            'totalPrice' => '148.50',
            'adminComment' => 'Zusatzleistungen manuell eingepreist.',
        ]));
        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('PENDING_CUSTOMER_APPROVAL', $data['state']);
        self::assertSame('148.50', $data['totalPrice']);
        self::assertSame('123.50', $data['quotedTotalPrice']);
        self::assertSame('Zusatzleistungen manuell eingepreist.', $data['adminComment']);
    }

    public function testDeclineHotelBookingClearsRoom(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();
        ['customer' => $customer] = $helper->createCustomerAndLogin('hotel-decline-'.uniqid('', true).'@example.com');

        $container = static::getContainer();
        $customerRepository = $container->get(CustomerRepository::class);
        $dogRepository = $container->get(DogRepository::class);
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');
        /** @var \App\Repository\RoomRepository $roomRepository */
        $roomRepository = $entityManager->getRepository(Room::class);
        /** @var \App\Repository\HotelBookingRepository $hotelBookingRepository */
        $hotelBookingRepository = $entityManager->getRepository(HotelBooking::class);

        $managedCustomer = $customerRepository->find($customer->getId());
        self::assertNotNull($managedCustomer);

        $dog = (new Dog())
            ->setCustomer($managedCustomer)
            ->setName('Decline Dog')
            ->setShoulderHeightCm(40);
        $dogRepository->save($dog);

        $room = (new Room())
            ->setName('Red Room')
            ->setSquareMeters(9);
        $roomRepository->save($room);

        $booking = (new HotelBooking())
            ->setCustomer($managedCustomer)
            ->setDog($dog)
            ->setRoom($room)
            ->setStartAt(new \DateTimeImmutable('2026-04-07 08:00'))
            ->setEndAt(new \DateTimeImmutable('2026-04-07 18:00'))
            ->setState(HotelBookingState::REQUESTED);
        $hotelBookingRepository->save($booking);

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/hotel/bookings/'.$booking->getId().'/decline', $token);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('DECLINED', $data['state']);
        self::assertNull($data['roomId']);
    }

    public function testConfirmRequiresAssignedRoom(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();
        ['customer' => $customer] = $helper->createCustomerAndLogin('hotel-no-room-'.uniqid('', true).'@example.com');

        $container = static::getContainer();
        $customerRepository = $container->get(CustomerRepository::class);
        $dogRepository = $container->get(DogRepository::class);
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');
        /** @var \App\Repository\HotelBookingRepository $hotelBookingRepository */
        $hotelBookingRepository = $entityManager->getRepository(HotelBooking::class);

        $managedCustomer = $customerRepository->find($customer->getId());
        self::assertNotNull($managedCustomer);

        $dog = (new Dog())
            ->setCustomer($managedCustomer)
            ->setName('No Room Dog')
            ->setShoulderHeightCm(42);
        $dogRepository->save($dog);

        $booking = (new HotelBooking())
            ->setCustomer($managedCustomer)
            ->setDog($dog)
            ->setStartAt(new \DateTimeImmutable('2026-04-08 08:00'))
            ->setEndAt(new \DateTimeImmutable('2026-04-09 08:00'))
            ->setState(HotelBookingState::REQUESTED);
        $hotelBookingRepository->save($booking);

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/hotel/bookings/'.$booking->getId().'/confirm', $token);
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testListRejectsInvalidRangeWithGermanError(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();

        $helper->adminRequest(
            Request::METHOD_GET,
            '/api/admin/hotel/bookings?from=ungueltig&to=2026-05-07T00:00',
            $token,
        );
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('Ungültiger Buchungszeitraum', $data['error'] ?? null);
    }

    public function testDeclinedBookingCannotBeAssignedToRoom(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();
        ['customer' => $customer] = $helper->createCustomerAndLogin('hotel-declined-assign-'.uniqid('', true).'@example.com');

        $container = static::getContainer();
        $customerRepository = $container->get(CustomerRepository::class);
        $dogRepository = $container->get(DogRepository::class);
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');
        /** @var \App\Repository\RoomRepository $roomRepository */
        $roomRepository = $entityManager->getRepository(Room::class);
        /** @var \App\Repository\HotelBookingRepository $hotelBookingRepository */
        $hotelBookingRepository = $entityManager->getRepository(HotelBooking::class);

        $managedCustomer = $customerRepository->find($customer->getId());
        self::assertNotNull($managedCustomer);

        $dog = (new Dog())
            ->setCustomer($managedCustomer)
            ->setName('Declined Assign Dog')
            ->setShoulderHeightCm(44);
        $dogRepository->save($dog);

        $room = (new Room())
            ->setName('Declined Room')
            ->setSquareMeters(14);
        $roomRepository->save($room);

        $booking = (new HotelBooking())
            ->setCustomer($managedCustomer)
            ->setDog($dog)
            ->setStartAt(new \DateTimeImmutable('2026-05-12 08:00'))
            ->setEndAt(new \DateTimeImmutable('2026-05-13 09:00'))
            ->setState(HotelBookingState::DECLINED);
        $hotelBookingRepository->save($booking);

        $helper->adminRequest(Request::METHOD_PUT, '/api/admin/hotel/bookings/'.$booking->getId().'/room', $token, json_encode([
            'roomId' => $room->getId(),
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame(
            'Abgelehnte Buchungen können keinem Zimmer zugewiesen werden.',
            $data['errors']['roomId'] ?? null,
        );
    }
}
