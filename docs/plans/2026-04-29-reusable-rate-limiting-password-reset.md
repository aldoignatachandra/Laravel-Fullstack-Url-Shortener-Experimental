# Reusable Rate Limiting and Password Reset UX Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add reusable Laravel rate limiting with Redis-to-database fallback and improve password reset UX with explicit unknown-email, success, and throttle messages.

**Architecture:** Configure Laravel's rate limiter cache store to use a `rate_limiter` failover store, then add small security support classes for generating safe actor keys and enforcing policies. Password reset uses the shared limiter before user lookup so unknown and known emails both consume attempts.

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit 12, Laravel RateLimiter facade, Redis cache store with failover cache driver, Blade/Volt auth pages.

---

### Task 1: Environment and cache limiter configuration

**Files:**
- Modify: `.env.example`
- Modify: `config/cache.php`

**Step 1: Update `.env.example`**

Set app/cache/Redis defaults for local setup:

```ini
APP_NAME=shrt.dev
APP_URL=http://localhost:8000
CACHE_STORE=database
CACHE_LIMITER=rate_limiter
CACHE_PREFIX=shrt.dev:cache:
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_PREFIX=shrt.dev:redis:
REDIS_CACHE_CONNECTION=cache
REDIS_CACHE_LOCK_CONNECTION=default
```

**Step 2: Update `config/cache.php`**

Add:

```php
'limiter' => env('CACHE_LIMITER', 'rate_limiter'),
```

Add `rate_limiter` store:

```php
'rate_limiter' => [
    'driver' => 'failover',
    'stores' => [
        'redis',
        'database',
        'array',
    ],
],
```

**Step 3: Verify config loads**

Run: `php artisan config:show cache.limiter`
Expected: `rate_limiter`.

---

### Task 2: Add reusable rate limit support classes

**Files:**
- Create: `app/Support/Security/RateLimitBucket.php`
- Create: `app/Support/Security/RateLimitKey.php`
- Create: `app/Support/Security/RateLimitPolicy.php`
- Create: `app/Support/Security/RateLimitResult.php`
- Create: `app/Support/Security/RateLimitGuard.php`

**Step 1: Add DTOs**

`RateLimitBucket` stores key, max attempts, and decay seconds.
`RateLimitResult` stores allowed state and retry seconds.

**Step 2: Add key builder**

Generate keys with app namespace:

```php
shrt.dev:rate-limit:{scope}:{identifier}
```

Use user ID for authenticated requests, IP for guests, and `sha1(strtolower(email))` for email keys.

**Step 3: Add policy factory**

Add balanced password reset policy:

- actor: 5 attempts / 15 minutes
- email: 3 attempts / 15 minutes
- global actor: 20 attempts / hour

Add API defaults for future middleware use.

**Step 4: Add guard service**

Guard checks all buckets. If blocked, returns retry seconds. If allowed, hits all buckets.

---

### Task 3: Add reusable middleware alias

**Files:**
- Create: `app/Http/Middleware/SmartThrottle.php`
- Modify: `bootstrap/app.php`

**Step 1: Add middleware**

Middleware signature:

```php
public function handle(Request $request, Closure $next, string $policy = 'api'): Response
```

Use `RateLimitGuard` with selected policy. Return JSON `429` for JSON requests and redirect back with `email` error for web requests.

**Step 2: Register alias**

In `bootstrap/app.php`:

```php
$middleware->alias([
    'smart.throttle' => SmartThrottle::class,
]);
```

This keeps API-specific usage available later:

```php
->middleware('smart.throttle:api')
->middleware('smart.throttle:strict-api')
```

---

### Task 4: Update forgot password behavior

**Files:**
- Modify: `app/Http/Controllers/Auth/PasswordResetController.php`
- Modify: `resources/views/livewire/pages/auth/forgot-password.blade.php`

**Step 1: Enforce password reset limiter**

In `sendOtp`, validate email, call `RateLimitGuard` for password reset using request + email, then perform user lookup.

**Step 2: Unknown email UX**

If user does not exist:

```php
return back()
    ->withErrors(['email' => 'We couldn’t find an account with that email.'])
    ->withInput();
```

**Step 3: Throttle UX**

If blocked:

```php
return back()
    ->withErrors(['email' => "Too many reset requests. Try again in {$seconds} seconds."])
    ->withInput();
```

**Step 4: Registered email UX**

After sending OTP, redirect to OTP page with status:

```php
return redirect()
    ->route('password.otp', ['email' => $email])
    ->with('status', 'We sent a reset code to your email. Check your inbox and spam folder.');
```

**Step 5: UI text**

Keep existing inline error component under email. Use existing green session status component above form.

---

### Task 5: Add and update PHPUnit coverage

**Files:**
- Modify: `tests/Feature/Auth/PasswordResetOtpTest.php`
- Create: `tests/Feature/Http/Middleware/SmartThrottleTest.php`

**Step 1: Update password reset tests**

Replace generic unknown-email test with explicit validation error assertion.

Add throttle tests for repeated unknown/known email attempts.

**Step 2: Add middleware tests**

Register temporary test routes using `smart.throttle:api` and assert:

- guest limits by IP
- authenticated user limits by user ID
- JSON requests get 429 JSON response

**Step 3: Run focused tests**

Run:

```bash
php artisan test --compact tests/Feature/Auth/PasswordResetOtpTest.php tests/Feature/Http/Middleware/SmartThrottleTest.php
```

Expected: all pass.

---

### Task 6: Format and final verification

**Files:**
- All modified PHP files

**Step 1: Format PHP**

Run:

```bash
vendor/bin/pint --dirty --format agent
```

Expected: modified PHP files formatted.

**Step 2: Run focused auth tests**

Run:

```bash
php artisan test --compact tests/Feature/Auth/PasswordResetOtpTest.php tests/Feature/Auth/PasswordResetTest.php tests/Feature/Http/Middleware/SmartThrottleTest.php
```

Expected: all pass.

**Step 3: Run frontend build if Blade changed**

Run:

```bash
rtk npm run build
```

Expected: build succeeds.

**Step 4: Do not commit unless user explicitly asks**

User requested no git add/commit/push without explicit permission.
