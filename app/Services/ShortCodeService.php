<?php

namespace App\Services;

use App\Models\Link;
use Illuminate\Support\Str;

class ShortCodeService
{
    /**
     * Generate a random alphanumeric short code.
     */
    public static function generate(int $length = 6): string
    {
        return Str::random($length);
    }

    /**
     * Generate a unique short code that doesn't exist in the database.
     *
     * @throws \RuntimeException when unable to generate a unique code
     */
    public static function generateUnique(int $maxAttempts = 3): string
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            $code = static::generate();

            if (! Link::where('short_code', $code)->exists()) {
                return $code;
            }
        }

        throw new \RuntimeException('Unable to generate unique short code after '.$maxAttempts.' attempts.');
    }
}
