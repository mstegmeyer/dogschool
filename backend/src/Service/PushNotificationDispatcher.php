<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Notification;
use App\Repository\PushDeviceRepository;
use App\Service\Push\PushSenderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class PushNotificationDispatcher
{
    /** @var iterable<PushSenderInterface> */
    private iterable $pushSenders;
    private LoggerInterface $logger;

    /**
     * @param iterable<PushSenderInterface> $pushSenders
     */
    public function __construct(
        private readonly PushDeviceRepository $pushDeviceRepository,
        iterable $pushSenders = [],
        ?LoggerInterface $logger = null,
    ) {
        $this->pushSenders = $pushSenders;
        $this->logger = $logger ?? new NullLogger();
    }

    public function dispatchNotificationCreated(Notification $notification): int
    {
        $sent = 0;
        $link = '/customer/notifications';

        foreach ($this->pushDeviceRepository->findCustomerTargetsForNotification($notification) as $pushDevice) {
            $sender = $this->findSender($pushDevice->getProvider());
            if ($sender === null) {
                $this->logger->warning('No push sender configured for provider.', ['provider' => $pushDevice->getProvider()]);
                continue;
            }

            try {
                $result = $sender->send($pushDevice, $notification, $link);
            } catch (\Throwable $e) {
                $this->logger->error('Unexpected push dispatch failure.', ['exception' => $e]);
                continue;
            }

            if ($result->shouldDeleteToken()) {
                $this->pushDeviceRepository->remove($pushDevice);
                continue;
            }

            if ($result->isSuccess()) {
                ++$sent;
            }
        }

        return $sent;
    }

    private function findSender(string $provider): ?PushSenderInterface
    {
        foreach ($this->pushSenders as $pushSender) {
            if ($pushSender->supports($provider)) {
                return $pushSender;
            }
        }

        return null;
    }
}
