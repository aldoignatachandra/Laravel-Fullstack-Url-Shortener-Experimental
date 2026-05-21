<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Pulse\Facades\Pulse;

class MetricsService
{
    /**
     * Record a metric event. Silently fails if Pulse is unavailable.
     *
     * This ensures the application continues working even if the
     * metrics infrastructure (Pulse, database) is down.
     */
    public static function record(string $event, array $tags = []): void
    {
        try {
            if (class_exists(Pulse::class)) {
                Pulse::record($event, $tags);
            }
        } catch (\Throwable $e) {
            Log::channel('metrics')->warning('Failed to record metric', [
                'event' => $event,
                'tags' => $tags,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Record a redirect hit.
     */
    public static function redirectHit(string $shortCode): void
    {
        static::record('redirect_hit', ['short_code' => $shortCode]);
    }

    /**
     * Record a link creation.
     */
    public static function linkCreated(int $userId): void
    {
        static::record('link_created', ['user_id' => $userId]);
    }

    /**
     * Record a user registration.
     */
    public static function userRegistered(): void
    {
        static::record('user_registered');
    }

    /**
     * Record a rate limit hit.
     */
    public static function rateLimitHit(string $key): void
    {
        static::record('rate_limit_hit', ['key' => $key]);
    }

    /**
     * Record an error event.
     */
    public static function error(string $type, string $message = ''): void
    {
        static::record('app_error', ['type' => $type, 'message' => Str::limit($message, 255)]);
    }
}
