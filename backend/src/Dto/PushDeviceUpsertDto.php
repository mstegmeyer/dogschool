<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class PushDeviceUpsertDto
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 8192)]
    public string $token = '';

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['ios', 'android', 'web'])]
    public string $platform = '';

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['apns', 'fcm', 'webpush'])]
    public string $provider = '';

    #[Assert\Length(max: 255)]
    public ?string $deviceName = null;
}
