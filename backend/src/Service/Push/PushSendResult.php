<?php

declare(strict_types=1);

namespace App\Service\Push;

final class PushSendResult
{
    private function __construct(
        private readonly string $status,
        private readonly ?string $detail = null,
    ) {
    }

    public static function success(?string $detail = null): self
    {
        return new self('success', $detail);
    }

    public static function skipped(?string $detail = null): self
    {
        return new self('skipped', $detail);
    }

    public static function invalidToken(?string $detail = null): self
    {
        return new self('invalid_token', $detail);
    }

    public static function failure(?string $detail = null): self
    {
        return new self('failure', $detail);
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function shouldDeleteToken(): bool
    {
        return $this->status === 'invalid_token';
    }

    public function isSkipped(): bool
    {
        return $this->status === 'skipped';
    }

    public function getDetail(): ?string
    {
        return $this->detail;
    }
}
