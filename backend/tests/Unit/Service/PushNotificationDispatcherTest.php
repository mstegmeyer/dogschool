<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Notification;
use App\Entity\PushDevice;
use App\Repository\PushDeviceRepository;
use App\Service\Push\PushSendResult;
use App\Service\Push\PushSenderInterface;
use App\Service\PushNotificationDispatcher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class PushNotificationDispatcherTest extends TestCase
{
    private PushDeviceRepository&MockObject $pushDeviceRepository;
    private PushSenderInterface&MockObject $webPushSender;

    protected function setUp(): void
    {
        $this->pushDeviceRepository = $this->createMock(PushDeviceRepository::class);
        $this->webPushSender = $this->createMock(PushSenderInterface::class);
        $this->webPushSender->method('supports')->willReturnCallback(static fn (string $provider): bool => $provider === 'webpush');
    }

    #[Test]
    public function it_dispatches_notifications_to_supported_senders(): void
    {
        $webDevice = (new PushDevice())
            ->setToken('{"endpoint":"https://example.com/push"}')
            ->setPlatform('web')
            ->setProvider('webpush');

        $notification = (new Notification())
            ->setTitle('Training reminder')
            ->setMessage('Tomorrow at 10:00');

        $this->pushDeviceRepository
            ->expects(self::once())
            ->method('findCustomerTargetsForNotification')
            ->with($notification)
            ->willReturn([$webDevice]);

        $this->webPushSender
            ->expects(self::once())
            ->method('send')
            ->with($webDevice, $notification, '/customer/notifications')
            ->willReturn(PushSendResult::success());

        $dispatcher = new PushNotificationDispatcher(
            $this->pushDeviceRepository,
            [$this->webPushSender],
            new NullLogger(),
        );

        self::assertSame(1, $dispatcher->dispatchNotificationCreated($notification));
    }

    #[Test]
    public function it_removes_invalid_tokens_after_send_attempt(): void
    {
        $device = (new PushDevice())
            ->setToken('stale-token')
            ->setPlatform('web')
            ->setProvider('webpush');
        $notification = (new Notification())
            ->setTitle('Update')
            ->setMessage('Body');

        $this->pushDeviceRepository
            ->expects(self::once())
            ->method('findCustomerTargetsForNotification')
            ->willReturn([$device]);

        $this->webPushSender
            ->expects(self::once())
            ->method('send')
            ->willReturn(PushSendResult::invalidToken('Unregistered'));

        $this->pushDeviceRepository
            ->expects(self::once())
            ->method('remove')
            ->with($device);

        $dispatcher = new PushNotificationDispatcher(
            $this->pushDeviceRepository,
            [$this->webPushSender],
            new NullLogger(),
        );

        self::assertSame(0, $dispatcher->dispatchNotificationCreated($notification));
    }
}
