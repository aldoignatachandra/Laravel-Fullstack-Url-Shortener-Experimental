<?php

namespace App\Support\Security;

class RateLimitResult
{
    public function __construct(
        public readonly bool $allowed,
        public readonly int $retryAfterSeconds = 0,
    ) {}

    public static function allowed(): self
    {
        return new self(true);
    }

    public static function blocked(int $retryAfterSeconds): self
    {
        return new self(false, max(1, $retryAfterSeconds));
    }
}
