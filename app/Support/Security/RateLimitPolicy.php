<?php

namespace App\Support\Security;

use Illuminate\Http\Request;
use InvalidArgumentException;

class RateLimitPolicy
{
    /**
     * @return array<int, RateLimitBucket>
     */
    public static function passwordReset(Request $request, string $email): array
    {
        return [
            new RateLimitBucket(
                key: RateLimitKey::actor($request, 'password-reset:15-minutes'),
                maxAttempts: 5,
                decaySeconds: 15 * 60,
            ),
            new RateLimitBucket(
                key: RateLimitKey::email('password-reset:15-minutes', $email),
                maxAttempts: 3,
                decaySeconds: 15 * 60,
            ),
            new RateLimitBucket(
                key: RateLimitKey::actor($request, 'password-reset:hour'),
                maxAttempts: 20,
                decaySeconds: 60 * 60,
            ),
        ];
    }

    /**
     * @return array<int, RateLimitBucket>
     */
    public static function passwordResetVerification(Request $request, string $email): array
    {
        return [
            new RateLimitBucket(
                key: RateLimitKey::actor($request, 'password-reset-verification:15-minutes'),
                maxAttempts: 5,
                decaySeconds: 15 * 60,
            ),
            new RateLimitBucket(
                key: RateLimitKey::email('password-reset-verification:15-minutes', $email),
                maxAttempts: 5,
                decaySeconds: 15 * 60,
            ),
            new RateLimitBucket(
                key: RateLimitKey::actor($request, 'password-reset-verification:hour'),
                maxAttempts: 15,
                decaySeconds: 60 * 60,
            ),
        ];
    }

    /**
     * @return array<int, RateLimitBucket>
     */
    public static function api(Request $request): array
    {
        return [
            new RateLimitBucket(
                key: RateLimitKey::actor($request, 'api:minute'),
                maxAttempts: 60,
                decaySeconds: 60,
            ),
        ];
    }

    /**
     * @return array<int, RateLimitBucket>
     */
    public static function strictApi(Request $request): array
    {
        return [
            new RateLimitBucket(
                key: RateLimitKey::actor($request, 'strict-api:minute'),
                maxAttempts: 10,
                decaySeconds: 60,
            ),
        ];
    }

    /**
     * @return array<int, RateLimitBucket>
     */
    public static function for(string $policy, Request $request): array
    {
        return match ($policy) {
            'api' => self::api($request),
            'strict-api' => self::strictApi($request),
            default => throw new InvalidArgumentException("Unknown rate limit policy [{$policy}]."),
        };
    }
}
