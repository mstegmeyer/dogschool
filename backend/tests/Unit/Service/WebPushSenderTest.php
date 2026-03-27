<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Notification;
use App\Entity\PushDevice;
use App\Service\Push\WebPushSender;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class WebPushSenderTest extends TestCase
{
    #[Test]
    public function it_skips_when_vapid_configuration_is_missing(): void
    {
        $device = (new PushDevice())
            ->setToken(json_encode([
                'endpoint' => 'https://example.com/push',
                'keys' => ['p256dh' => 'abc', 'auth' => 'def'],
            ], JSON_THROW_ON_ERROR))
            ->setPlatform('web')
            ->setProvider('webpush');

        $notification = (new Notification())
            ->setTitle('Title')
            ->setMessage('Body');

        $sender = new WebPushSender(new NullLogger());
        $result = $sender->send($device, $notification, '/customer/notifications');

        self::assertTrue($result->isSkipped());
    }

    #[Test]
    public function it_marks_invalid_subscription_payloads_as_invalid_tokens(): void
    {
        putenv('WEB_PUSH_VAPID_SUBJECT=mailto:test@example.com');
        putenv('WEB_PUSH_VAPID_PUBLIC_KEY='.str_repeat('A', 87));
        putenv('WEB_PUSH_VAPID_PRIVATE_KEY='.str_repeat('A', 43));
        $_ENV['WEB_PUSH_VAPID_SUBJECT'] = 'mailto:test@example.com';
        $_ENV['WEB_PUSH_VAPID_PUBLIC_KEY'] = str_repeat('A', 87);
        $_ENV['WEB_PUSH_VAPID_PRIVATE_KEY'] = str_repeat('A', 43);

        $device = (new PushDevice())
            ->setToken('{invalid-json')
            ->setPlatform('web')
            ->setProvider('webpush');

        $notification = (new Notification())
            ->setTitle('Title')
            ->setMessage('Body');

        $sender = new WebPushSender(new NullLogger());
        $result = $sender->send($device, $notification, '/customer/notifications');

        self::assertTrue($result->shouldDeleteToken());

        putenv('WEB_PUSH_VAPID_SUBJECT');
        putenv('WEB_PUSH_VAPID_PUBLIC_KEY');
        putenv('WEB_PUSH_VAPID_PRIVATE_KEY');
        unset($_ENV['WEB_PUSH_VAPID_SUBJECT'], $_ENV['WEB_PUSH_VAPID_PUBLIC_KEY'], $_ENV['WEB_PUSH_VAPID_PRIVATE_KEY']);
    }
}
