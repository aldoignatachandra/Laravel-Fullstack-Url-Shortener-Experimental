<?php

namespace App\Support\Security;

use App\Services\MetricsService;
use Illuminate\Support\Facades\RateLimiter;

class RateLimitGuard
{
    /**
     * @param array<int, RateLimitBucket> $buckets
     */
    public function attempt(array $buckets): RateLimitResult
    {
        foreach ($buckets as $bucket) {
            $executed = RateLimiter::attempt(
                $bucket->key,
                $bucket->maxAttempts,
                fn () => true,
                $bucket->decaySeconds,
            );

            if (! $executed) {
                MetricsService::rateLimitHit($bucket->key);

                return RateLimitResult::blocked(RateLimiter::availableIn($bucket->key));
            }
        }

        return RateLimitResult::allowed();
    }
}
