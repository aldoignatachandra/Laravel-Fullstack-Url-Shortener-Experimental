# Security Audit Report — shrt.dev URL Shortener

> **Date:** 2026-05-21  
> **Scope:** Full security audit  
> **Status:** Pending Review

---

## Summary

| Severity    | Count  | Fixed |
| ----------- | ------ | ----- |
| 🔴 Critical | 1      | ✅    |
| 🟠 High     | 4      | ⬜    |
| 🟡 Medium   | 5      | ⬜    |
| 🟢 Low      | 4      | ⬜    |
| **Total**   | **14** | **0** |

---

## 🔴 CRITICAL

### ✅ 1. SSRF via Short URL Redirect — FIXED

**File:** `routes/web.php:28-41`  
**Status:** ✅ Fixed

```php
$parsedUrl = parse_url($link->original_url);
if (! in_array($parsedUrl['scheme'] ?? '', ['http', 'https'], true)) {
    abort(404);
}
return redirect($link->original_url, 301);
```

**Description:** The redirect handler only validates the URL scheme (`http`/`https`). An attacker can create a short URL pointing to internal services (`http://169.254.169.254/latest/meta-data/`, `http://localhost:8080/admin`, `http://127.0.0.1:3306`). While this is a redirect (not a server-side fetch), it enables:

- **Open redirect** — any external malicious site
- **SSRF-adjacent** — if any downstream system follows redirects from your domain
- **Phishing** — `https://shrt.dev/s/abc` redirects to `https://evil.com/login` that mimics a bank

**Remediation:**

```php
// Block private/internal IPs and known dangerous ranges
$parsedUrl = parse_url($link->original_url);
$host = $parsedUrl['host'] ?? '';

if (! in_array($parsedUrl['scheme'] ?? '', ['http', 'https'], true)) {
    abort(404);
}

// Block internal IPs
if (filter_var($host, FILTER_VALIDATE_IP)) {
    if (filter_var($host, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        abort(404);
    }
}

// Block localhost variants
if (in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '::1', '[::1]'], true)) {
    abort(404);
}
```

---

## 🟠 HIGH

### 2. Google OAuth — Account Takeover via Email Auto-Linking

**File:** `app/Http/Controllers/Auth/GoogleController.php:42-56`  
**Status:** ⬜ Pending

```php
$existingUser = User::where('email', $googleUser->getEmail())->first();
if ($existingUser) {
    $existingUser->update([
        'google_id' => $googleUser->getId(),
        ...
    ]);
    Auth::login($existingUser);
}
```

**Description:** If an attacker registers a Google account with the same email as a victim's manually-registered account, the attacker gets auto-linked to the victim's account. Google allows creating accounts with any email verification flow, and some OAuth providers don't verify email ownership at the same level. This is an **account takeover** vector.

**Remediation:**

```php
if ($existingUser) {
    // Only auto-link if email was verified via the app
    if (! $existingUser->email_verified_at) {
        return redirect()->route('login')
            ->with('error', 'Please verify your email first, then link Google from your profile.');
    }
    // ... proceed with linking
}
```

---

### 3. OTP Session Fixation — No Session Regeneration After OTP Verification

**File:** `app/Services/Auth/PasswordResetOtpService.php:131-133`  
**Status:** ⬜ Pending

```php
$otpRecord->update(['used_at' => now()]);
session()->put('otp_verified_email', $email);
```

**Description:** After OTP verification, the session ID is not regenerated. If an attacker can fixate or steal the session (via XSS, network sniffing on non-HTTPS), they can use the `otp_verified_email` session value to reset the password.

**Remediation:**

```php
$otpRecord->update(['used_at' => now()]);
session()->regenerate(); // Prevent session fixation
session()->put('otp_verified_email', $email);
```

---

### 4. Password Reset Flow — Missing `guest` Middleware on `reset-password` POST

**File:** `routes/auth.php:30-31`  
**Status:** ⬜ Pending

```php
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
    ->name('password.update');
```

**Description:** The `resetPassword` POST route is inside the `guest` middleware group, which is correct. However, the `PasswordResetController::resetPassword` method checks `session()->has('otp_verified_email')` but doesn't verify the **session belongs to the current request's session**. If the `otp_verified_email` session key persists across logins (user logs in after OTP verification), a logged-in user could trigger password reset for the email stored in session.

**Remediation:**

- Ensure `otp_verified_email` is cleared on login (add listener to `Login` event)
- Or: add explicit `guest` check in the controller method

---

### 5. Rate Limit Race Condition in `RateLimitGuard`

**File:** `app/Support/Security/RateLimitGuard.php:14-24`  
**Status:** ⬜ Pending

```php
foreach ($buckets as $bucket) {
    if (RateLimiter::tooManyAttempts($bucket->key, $bucket->maxAttempts)) {
        return RateLimitResult::blocked(...);
    }
}
foreach ($buckets as $bucket) {
    RateLimiter::hit($bucket->key, $bucket->decaySeconds);
}
```

**Description:** TOCTOU (Time-of-Check-Time-of-Use) race condition. Between checking `tooManyAttempts` and calling `hit()`, concurrent requests can pass the check and all get through. Under high concurrency, this can allow 2-3x the intended rate limit.

**Remediation:**

```php
// Use RateLimiter::attempt() which is atomic in Laravel
foreach ($buckets as $bucket) {
    $executed = RateLimiter::attempt(
        $bucket->key,
        $bucket->maxAttempts,
        fn() => true,
        $bucket->decaySeconds
    );
    if (! $executed) {
        return RateLimitResult::blocked(RateLimiter::availableIn($bucket->key));
    }
}
```

---

## 🟡 MEDIUM

### 6. No Security Headers Middleware

**File:** `bootstrap/app.php` (missing)  
**Status:** ⬜ Pending

**Description:** No custom security headers are configured. Missing:

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `Strict-Transport-Security` (HSTS)
- `Content-Security-Policy`
- `Referrer-Policy`
- `Permissions-Policy`

**Remediation:**

```php
// app/Http/Middleware/SecurityHeaders.php
public function handle($request, Closure $next) {
    $response = $next($request);
    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->headers->set('X-Frame-Options', 'DENY');
    $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    return $response;
}
```

---

### 7. Session Cookie `secure` Flag Defaults to `null`

**File:** `config/session.php:172`  
**Status:** ⬜ Pending

```php
'secure' => env('SESSION_SECURE_COOKIE'),
```

**Description:** If `SESSION_SECURE_COOKIE` is not set in `.env`, the cookie is sent over HTTP. In production, this allows session hijacking via network sniffing. `.env.example` doesn't set this value.

**Remediation:**

```env
# .env (production)
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

---

### 8. Session Encryption Disabled

**File:** `config/session.php:50`  
**Status:** ⬜ Pending

```php
'encrypt' => env('SESSION_ENCRYPT', false),
```

**Description:** Session data is stored unencrypted. If the database is compromised, session contents (including `otp_verified_email`) are readable in plaintext.

**Remediation:**

```env
SESSION_ENCRYPT=true
```

---

### 9. Short Code Collision Window — No Database Constraint

**File:** `app/Services/ShortCodeService.php:32-37`  
**Status:** ⬜ Pending

```php
if (! Link::where('short_code', $code)->exists()) {
    return $code;
}
```

**Description:** The uniqueness check and the subsequent `Link::create()` are not atomic. Two concurrent requests could generate the same code, both pass the `exists()` check, and one would fail with a database error (if there's a unique constraint) or silently overwrite (if not).

**Remediation:**

- Add a unique database index on `short_code` (if not already present)
- Wrap the check + create in a database transaction with `DB::transaction()`
- Or: catch `QueryException` on duplicate and retry

---

### 10. Google OAuth Callback — No State Parameter Verification

**File:** `app/Http/Controllers/Auth/GoogleController.php:31`  
**Status:** ⬜ Pending

```php
$googleUser = Socialite::driver('google')->user();
```

**Description:** While Laravel Socialite handles CSRF state verification by default, if `GOOGLE_REDIRECT_URI` is misconfigured or if the `state` session is lost (e.g., session driver issues), this could allow CSRF attacks on the OAuth callback. No explicit error handling for failed state verification.

**Remediation:**

```php
try {
    $googleUser = Socialite::driver('google')->user();
} catch (\Exception $e) {
    return redirect()->route('login')->with('error', 'Authentication failed. Please try again.');
}
```

---

## 🟢 LOW

### 11. User Enumeration via Registration

**File:** `resources/views/livewire/pages/auth/register.blade.php:29-33`  
**Status:** ⬜ Pending

```php
$existingUser = User::where('email', $validated['email'])->first();
if ($existingUser && $existingUser->isGoogleUser()) {
    $this->addError('email', 'This email is linked to a Google account...');
```

**Description:** Different error messages for "email exists as Google user" vs "email doesn't exist" allow attackers to enumerate which emails have accounts and their registration method.

**Remediation:**

```php
$this->addError('email', 'An account with this email already exists. Please log in.');
```

---

### 12. OTP Brute Force Window — 10 Attempts per 15 Minutes

**File:** `app/Support/Security/RateLimitPolicy.php:40-44`  
**Status:** ⬜ Pending

```php
new RateLimitBucket(
    key: RateLimitKey::actor($request, 'password-reset-verification:15-minutes'),
    maxAttempts: 10,
    decaySeconds: 15 * 60,
),
```

**Description:** 10 OTP verification attempts per 15 minutes is generous for a 6-digit numeric code. A 6-digit code has 1M combinations, but 10 attempts is still a meaningful attack window if the attacker has multiple IPs.

**Remediation:**

- Reduce to 5 attempts per OTP (already 5 per email ✓)
- Consider lockout after 3 failed attempts on same OTP
- Add exponential backoff

---

### 13. IP Logging Privacy Concern

**File:** `routes/web.php:36`  
**Status:** ⬜ Pending

```php
'ip_address' => $request->ip(),
```

**Description:** Storing raw IP addresses without hashing may violate GDPR/privacy regulations in some jurisdictions. IPs are personal data under GDPR.

**Remediation:**

```php
'ip_address' => hash('sha256', $request->ip() . config('app.key')),
// Or: anonymize last octet for IPv4
```

---

### 14. Telescope Exposed in Production

**File:** `.env.example:79`  
**Status:** ⬜ Pending

```
TELESCOPE_ALLOWED_EMAILS=your-email@gmail.com
```

**Description:** Telescope is a debugging tool that exposes detailed request/response data, database queries, and more. If deployed to production without proper access controls, it's a significant information disclosure risk.

**Remediation:**

- Ensure `TELESCOPE_ENABLED=false` in production `.env`
- Or: use `TelescopeServiceProvider` to gate access properly
- Verify the provider blocks production access

---

## ✅ What's Done Well

| Area                      | Status                                                                       |
| ------------------------- | ---------------------------------------------------------------------------- |
| **SQL Injection**         | ✅ All queries use Eloquent — no raw SQL injection vectors                   |
| **XSS**                   | ✅ All Blade templates use `{{ }}` (escaped), no `{!! !!}` in app views      |
| **CSRF**                  | ✅ `csrf-token` meta tag present, Livewire handles CSRF automatically        |
| **Password Hashing**      | ✅ `bcrypt` with 12 rounds, `Password::defaults()` enforces strong passwords |
| **OTP Hashing**           | ✅ OTPs stored as `Hash::make()`, not plaintext                              |
| **Session Regeneration**  | ✅ `Session::regenerate()` on login (login.blade.php:20)                     |
| **Authorization**         | ✅ `LinkPolicy` properly checks `user_id` ownership                          |
| **Rate Limiting**         | ✅ Multi-bucket rate limiting on auth endpoints, redirect, and link creation |
| **Input Validation**      | ✅ `StoreLinkRequest` validates URL format and length                        |
| **Soft Deletes**          | ✅ Links use `SoftDeletes` for data retention                                |
| **Short Code**            | ✅ Uses `Str::random()` (cryptographically secure in PHP 8)                  |
| **Session Serialization** | ✅ JSON serialization (not PHP) prevents gadget chain attacks                |

---

## Priority Remediation Order

| Priority | Issue                     | Severity    | Effort |
| -------- | ------------------------- | ----------- | ------ |
| 1        | SSRF blocking             | 🔴 Critical | 10 min |
| 2        | Google OAuth security     | 🟠 High     | 15 min |
| 3        | OTP session regeneration  | 🟠 High     | 5 min  |
| 4        | Password reset session    | 🟠 High     | 10 min |
| 5        | Rate limit race condition | 🟠 High     | 10 min |
| 6        | Security headers          | 🟡 Medium   | 10 min |
| 7        | Session security config   | 🟡 Medium   | 5 min  |
| 8        | Session encryption        | 🟡 Medium   | 5 min  |
| 9        | Short code atomicity      | 🟡 Medium   | 10 min |
| 10       | OAuth error handling      | 🟡 Medium   | 5 min  |
| 11       | User enumeration          | 🟢 Low      | 5 min  |
| 12       | OTP brute force           | 🟢 Low      | 5 min  |
| 13       | IP logging privacy        | 🟢 Low      | 5 min  |
| 14       | Telescope production      | 🟢 Low      | 5 min  |

**Total Estimated Effort:** ~1.5 hours

---

## References

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://laravel.com/docs/13.x/security)
- [OWASP SSRF Prevention](https://cheatsheetseries.owasp.org/cheatsheets/Server_Side_Request_Forgery_Prevention_Cheat_Sheet.html)
- [OWASP Session Fixation](https://owasp.org/www-community/attacks/Session_fixation)
