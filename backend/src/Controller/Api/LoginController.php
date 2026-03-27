<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\CourseDate;
use App\Repository\BookingRepository;
use App\Repository\CustomerRepository;
use App\Service\CustomerCalendarFeedBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Login routes for obtaining a Bearer JWT.
 *
 * Authentication flow:
 * 1. POST to /api/customer/login with JSON {"email": "...", "password": "..."}
 *   or POST to /api/admin/login with JSON {"username": "...", "password": "..."}
 * 2. On success you receive a JSON response with a JWT (e.g. {"token": "..."}).
 * 3. Use that token in subsequent requests: Authorization: Bearer <token>
 *
 * The firewall's json_login authenticator handles valid JSON login requests and returns
 * the JWT via Lexik JWT. This controller is only hit when the request is invalid
 * (e.g. missing Content-Type: application/json or empty/malformed body).
 */
final class LoginController extends AbstractController
{
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            CustomerRepository::class,
            BookingRepository::class,
            CustomerCalendarFeedBuilder::class,
        ]);
    }

    #[Route('/api/customer/login', name: 'api_customer_login_check', methods: ['POST'])]
    public function customerLogin(): JsonResponse
    {
        return $this->json(
            ['error' => 'Send JSON with "email" and "password".'],
            Response::HTTP_BAD_REQUEST
        );
    }

    #[Route('/api/admin/login', name: 'api_admin_login_check', methods: ['POST'])]
    public function adminLogin(): JsonResponse
    {
        return $this->json(
            ['error' => 'Send JSON with "username" and "password".'],
            Response::HTTP_BAD_REQUEST
        );
    }

    public function customerCalendarFeed(string $token): Response
    {
        $customerRepository = $this->container->get(CustomerRepository::class);
        $bookingRepository = $this->container->get(BookingRepository::class);
        $calendarFeedBuilder = $this->container->get(CustomerCalendarFeedBuilder::class);

        $customer = $customerRepository->findOneByCalendarFeedToken($token);
        if ($customer === null) {
            return new Response('Calendar feed not found.', Response::HTTP_NOT_FOUND, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        $content = $calendarFeedBuilder->build(
            $customer,
            $bookingRepository->findActiveForCalendarFeed($customer),
        );

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="komm-bookings.ics"',
        ]);
    }
}
