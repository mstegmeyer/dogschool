<?php

declare(strict_types=1);

namespace App\Controller\Api\Customer;

use App\Entity\Course;
use App\Entity\Customer;
use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Service\ApiNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/customer/notifications', name: 'api_customer_notifications_')]
#[IsGranted('ROLE_CUSTOMER')]
final class NotificationController extends AbstractController
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly ApiNormalizer $normalizer,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Customer $customer): JsonResponse
    {
        $subscribedCourseIds = array_values(
            $customer->getSubscribedCourses()->map(static fn (Course $c) => $c->getId())->toArray()
        );
        $notifications = $this->notificationRepository->findForCustomerCourses($subscribedCourseIds);

        return $this->json([
            'items' => array_map(fn (Notification $n) => $this->normalizer->normalizeNotification($n), $notifications),
        ]);
    }
}
