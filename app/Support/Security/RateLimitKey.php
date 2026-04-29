<?php

namespace App\Support\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RateLimitKey
{
    public static function actor(Request $request, string $scope): string
    {
        if ($request->user()) {
            return self::make($scope, 'user', (string) $request->user()->getAuthIdentifier());
        }

        return self::make($scope, 'ip', (string) $request->ip());
    }

    public static function email(string $scope, string $email): string
    {
        return self::make($scope, 'email', Str::lower($email));
    }

    public static function make(string $scope, string $identifierType, string $identifier): string
    {
        return implode(':', [
            self::namespace(),
            'rate-limit',
            Str::slug($scope),
            Str::slug($identifierType),
            sha1(Str::lower($identifier)),
        ]);
    }

    private static function namespace(): string
    {
        return (string) config('app.name', 'shrt.dev');
    }
}
