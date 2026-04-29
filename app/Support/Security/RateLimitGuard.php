<?php

namespace App\Support\Security;

use Illuminate\Support\Facades\RateLimiter;

class RateLimitGuard
{
    /**
     * @param  array<int, RateLimitBucket>  $buckets
     */
    public function attempt(array $buckets): RateLimitResult
    {
        foreach ($buckets as $bucket) {
            if (RateLimiter::tooManyAttempts($bucket->key, $bucket->maxAttempts)) {
                return RateLimitResult::blocked(RateLimiter::availableIn($bucket->key));
            }
        }

        foreach ($buckets as $bucket) {
            RateLimiter::hit($bucket->key, $bucket->decaySeconds);
        }

        return RateLimitResult::allowed();
    }
}
