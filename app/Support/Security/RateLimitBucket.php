<?php

namespace App\Support\Security;

class RateLimitBucket
{
    public function __construct(
        public readonly string $key,
        public readonly int $maxAttempts,
        public readonly int $decaySeconds,
    ) {}
}
