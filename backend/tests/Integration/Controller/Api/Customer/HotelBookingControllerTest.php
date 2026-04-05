<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Customer;

use App\Entity\Dog;
use App\Entity\HotelBooking;
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
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('Beginn muss zwischen 06:00 und 22:00 Uhr liegen.', $data['errors']['startAt'] ?? null);
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
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('Ende muss zwischen 06:00 und 22:00 Uhr liegen.', $data['errors']['endAt'] ?? null);
    }
}
