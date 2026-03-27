<?php

declare(strict_types=1);

namespace App\Service\Push;

use App\Entity\Notification;
use App\Entity\PushDevice;

interface PushSenderInterface
{
    public function supports(string $provider): bool;

    public function send(PushDevice $pushDevice, Notification $notification, string $link): PushSendResult;
}
