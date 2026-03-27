<?php

declare(strict_types=1);

namespace App\Service\Push;

use App\Entity\Notification;
use App\Entity\PushDevice;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Psr\Log\LoggerInterface;

final class WebPushSender implements PushSenderInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function supports(string $provider): bool
    {
        return $provider === 'webpush';
    }

    public function send(PushDevice $pushDevice, Notification $notification, string $link): PushSendResult
    {
        $config = $this->loadConfig();
        if ($config === null) {
            return PushSendResult::skipped('Web push VAPID keys not configured.');
        }

        try {
            /** @var array<string, mixed> $subscriptionData */
            $subscriptionData = json_decode($pushDevice->getToken(), true, flags: JSON_THROW_ON_ERROR);
            $subscription = Subscription::create($subscriptionData);
            $payload = json_encode([
                'title' => $notification->getTitle(),
                'body' => $notification->getMessage(),
                'link' => $link,
                'notificationId' => $notification->getId(),
            ], JSON_THROW_ON_ERROR);

            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => $config['subject'],
                    'publicKey' => $config['publicKey'],
                    'privateKey' => $config['privateKey'],
                ],
            ]);
            $webPush->setReuseVAPIDHeaders(true);

            $report = $webPush->sendOneNotification($subscription, $payload);
        } catch (\JsonException $e) {
            $this->logger->warning('Stored web push subscription JSON is invalid.', ['exception' => $e]);

            return PushSendResult::invalidToken('Invalid subscription payload.');
        } catch (\Throwable $e) {
            $this->logger->error('Web push send failed unexpectedly.', ['exception' => $e]);

            return PushSendResult::failure($e->getMessage());
        }

        if ($report->isSuccess()) {
            return PushSendResult::success();
        }

        if ($report->isSubscriptionExpired()) {
            return PushSendResult::invalidToken($report->getReason());
        }

        $reason = $report->getReason();
        if (str_contains($reason, '410') || str_contains($reason, '404')) {
            return PushSendResult::invalidToken($reason);
        }

        $this->logger->warning('Web push delivery failed.', ['reason' => $reason]);

        return PushSendResult::failure($reason);
    }

    /**
     * @return array{subject: string, publicKey: string, privateKey: string}|null
     */
    private function loadConfig(): ?array
    {
        $subject = $this->env('WEB_PUSH_VAPID_SUBJECT');
        $publicKey = $this->env('WEB_PUSH_VAPID_PUBLIC_KEY');
        $privateKey = $this->env('WEB_PUSH_VAPID_PRIVATE_KEY');

        if ($subject === null || $publicKey === null || $privateKey === null) {
            return null;
        }

        return [
            'subject' => $subject,
            'publicKey' => $publicKey,
            'privateKey' => $privateKey,
        ];
    }

    private function env(string $name): ?string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
