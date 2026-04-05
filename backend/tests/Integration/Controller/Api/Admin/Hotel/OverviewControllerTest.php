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

final class OverviewControllerTest extends WebTestCase
{
    public function testOccupancyAndMovementsEndpointsReturnRoomAssignments(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();
        ['customer' => $customer] = $helper->createCustomerAndLogin('hotel-overview-'.uniqid('', true).'@example.com');

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

        $room = (new Room())
            ->setName('Garden')
            ->setSquareMeters(14);
        $roomRepository->save($room);

        $firstDog = (new Dog())
            ->setCustomer($managedCustomer)
            ->setName('Mila')
            ->setShoulderHeightCm(48);
        $dogRepository->save($firstDog);

        $secondDog = (new Dog())
            ->setCustomer($managedCustomer)
            ->setName('Bruno')
            ->setShoulderHeightCm(66);
        $dogRepository->save($secondDog);

        $firstBooking = (new HotelBooking())
            ->setCustomer($managedCustomer)
            ->setDog($firstDog)
            ->setRoom($room)
            ->setStartAt(new \DateTimeImmutable('2026-04-05 08:00'))
            ->setEndAt(new \DateTimeImmutable('2026-04-05 18:00'))
            ->setState(HotelBookingState::CONFIRMED);
        $hotelBookingRepository->save($firstBooking);

        $secondBooking = (new HotelBooking())
            ->setCustomer($managedCustomer)
            ->setDog($secondDog)
            ->setRoom($room)
            ->setStartAt(new \DateTimeImmutable('2026-04-05 09:00'))
            ->setEndAt(new \DateTimeImmutable('2026-04-06 10:00'))
            ->setState(HotelBookingState::CONFIRMED);
        $hotelBookingRepository->save($secondBooking);

        $helper->adminRequest(
            Request::METHOD_GET,
            '/api/admin/hotel/occupancy?from=2026-04-05T07:00&to=2026-04-05T20:00',
            $token,
        );
        self::assertResponseIsSuccessful();
        $occupancy = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('2026-04-05T07:00:00+02:00', $occupancy['from']);
        self::assertSame('2026-04-05T20:00:00+02:00', $occupancy['to']);
        $gardenOccupancy = array_values(array_filter(
            $occupancy['items'],
            static fn (array $item): bool => ($item['room']['id'] ?? null) === $room->getId(),
        ));
        self::assertCount(1, $gardenOccupancy);
        self::assertSame('Garden', $gardenOccupancy[0]['room']['name']);
        self::assertSame(13, $gardenOccupancy[0]['peakRequiredSquareMeters']);
        self::assertSame('2026-04-05T09:00:00+02:00', $gardenOccupancy[0]['segments'][2]['startAt']);
        self::assertSame('2026-04-05T18:00:00+02:00', $gardenOccupancy[0]['segments'][2]['endAt']);

        $helper->adminRequest(
            Request::METHOD_GET,
            '/api/admin/hotel/movements?from=2026-04-05T07:00&to=2026-04-06T12:00',
            $token,
        );
        self::assertResponseIsSuccessful();
        $movements = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('2026-04-05T07:00:00+02:00', $movements['from']);
        self::assertSame('2026-04-06T12:00:00+02:00', $movements['to']);
        $gardenArrivals = array_values(array_filter(
            $movements['arrivals'],
            static fn (array $item): bool => ($item['roomId'] ?? null) === $room->getId(),
        ));
        $gardenDepartures = array_values(array_filter(
            $movements['departures'],
            static fn (array $item): bool => ($item['roomId'] ?? null) === $room->getId(),
        ));
        self::assertCount(2, $gardenArrivals);
        self::assertCount(2, $gardenDepartures);
        self::assertSame('Garden', $gardenArrivals[0]['roomName']);
        self::assertSame('2026-04-05T08:00:00+02:00', $gardenArrivals[0]['startAt']);
        self::assertSame('2026-04-05T18:00:00+02:00', $gardenDepartures[0]['endAt']);
    }
}
