<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Push;

use App\Service\Push\PushSendResult;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PushSendResultTest extends TestCase
{
    #[Test]
    public function factoryMethodsExposeExpectedFlags(): void
    {
        $success = PushSendResult::success('sent');
        self::assertTrue($success->isSuccess());
        self::assertFalse($success->isSkipped());
        self::assertFalse($success->shouldDeleteToken());
        self::assertSame('sent', $success->getDetail());

        $skipped = PushSendResult::skipped('missing vapid');
        self::assertFalse($skipped->isSuccess());
        self::assertTrue($skipped->isSkipped());
        self::assertFalse($skipped->shouldDeleteToken());
        self::assertSame('missing vapid', $skipped->getDetail());

        $invalid = PushSendResult::invalidToken('unregistered');
        self::assertFalse($invalid->isSuccess());
        self::assertFalse($invalid->isSkipped());
        self::assertTrue($invalid->shouldDeleteToken());
        self::assertSame('unregistered', $invalid->getDetail());

        $failure = PushSendResult::failure('gateway down');
        self::assertFalse($failure->isSuccess());
        self::assertFalse($failure->isSkipped());
        self::assertFalse($failure->shouldDeleteToken());
        self::assertSame('gateway down', $failure->getDetail());
    }
}
