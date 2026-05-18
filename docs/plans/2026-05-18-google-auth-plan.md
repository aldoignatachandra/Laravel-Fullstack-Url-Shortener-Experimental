# Google Authentication Implementation Plan

> **Date:** 2026-05-18  
> **Scope:** Social login with Google OAuth via Laravel Socialite  
> **Status:** Pending Review

---

## Overview

Add Google OAuth authentication to the URL shortener while preserving manual email/password registration. If a user tries to login with Google but their email already exists (registered manually), reject with an error message.

---

## Requirements

1. **Manual registration** — Keep existing email/password flow
2. **Google login/register** — One-click OAuth flow
3. **Email conflict handling** — Reject if email already registered manually
4. **Session-based auth** — Cookie-based (Laravel default)
5. **Avatar support** — Show Google avatar or 2-letter initials fallback

---

## Perbandingan dengan JavaScript

| Aspect           | JavaScript (better-auth) | Laravel (Socialite)                       |
| ---------------- | ------------------------ | ----------------------------------------- |
| Package          | `better-auth`            | `laravel/socialite`                       |
| Provider config  | `auth.ts`                | `config/services.php`                     |
| OAuth redirect   | `auth.signIn.social()`   | `Socialite::driver('google')->redirect()` |
| Callback handler | `handleCallback()`       | `callback()` method                       |
| Session          | Cookie-based             | Cookie-based (default)                    |

---

## Implementation Steps

### Task 1: Install Laravel Socialite

**Command:**

```bash
composer require laravel/socialite
```

**Files:**

- Modify: `composer.json`

---

### Task 2: Migration — Add Google Fields to Users

**Files:**

- Create: `database/migrations/xxxx_add_google_id_to_users_table.php`

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('google_id')->nullable()->unique()->after('email');
    $table->string('google_avatar')->nullable()->after('google_id');
    $table->string('password')->nullable()->change(); // Social login tidak perlu password
});
```

**Changes:**
| Column | Type | Notes |
|--------|------|-------|
| `google_id` | string, nullable, unique | Google user ID |
| `google_avatar` | string, nullable | Google avatar URL |
| `password` | string, nullable | Made optional for social login |

---

### Task 3: Update User Model

**Files:**

- Modify: `app/Models/User.php`

```php
#[Fillable(['name', 'email', 'password', 'google_id', 'google_avatar'])]
class User extends Authenticatable
{
    // ...

    /**
     * Check if user registered via Google.
     */
    public function isGoogleUser(): bool
    {
        return $this->google_id !== null;
    }

    /**
     * Get user initials for avatar fallback.
     */
    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        $initials = '';

        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }

        return $initials;
    }
}
```

---

### Task 4: Google OAuth Configuration

**Files:**

- Modify: `config/services.php`

```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
],
```

**Files:**

- Modify: `.env`

```env
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

---

### Task 5: Google Auth Controller

**Files:**

- Create: `app/Http/Controllers/Auth/GoogleController.php`

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class GoogleController extends Controller
{
    /**
     * Redirect to Google OAuth.
     */
    public function redirect()
    {
        return Socialite::driver('google')
            ->redirect();
    }

    /**
     * Handle Google OAuth callback.
     */
    public function callback()
    {
        $googleUser = Socialite::driver('google')->user();

        // Check if user exists by google_id
        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            Auth::login($user);
            return redirect()->route('dashboard');
        }

        // Check if email already exists (manual registration)
        $existingUser = User::where('email', $googleUser->getEmail())->first();

        if ($existingUser) {
            // Reject: email already registered manually
            return redirect()->route('login')
                ->withErrors([
                    'email' => 'An account with this email already exists. Please login with your password.',
                ]);
        }

        // Create new user
        $user = User::create([
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'google_avatar' => $googleUser->getAvatar(),
            'email_verified_at' => now(), // Google email is verified
        ]);

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
```

---

### Task 6: Routes

**Files:**

- Modify: `routes/web.php`

```php
use App\Http\Controllers\Auth\GoogleController;

Route::get('/auth/google', [GoogleController::class, 'redirect'])
    ->name('auth.google')
    ->middleware('throttle:5,1'); // Rate limit OAuth redirects

Route::get('/auth/google/callback', [GoogleController::class, 'callback'])
    ->name('auth.google.callback');
```

---

### Task 7: Update Login Page UI

**Files:**

- Modify: `resources/views/livewire/pages/auth/login.blade.php`

Add Google login button above the form:

```blade
{{-- Google Login Button --}}
<a href="{{ route('auth.google') }}"
   class="flex items-center justify-center gap-3 w-full py-2.5 px-4 text-sm font-medium rounded-md border border-surface-border bg-surface-black text-surface-off-white hover:bg-surface-border transition-colors">
    <svg class="w-5 h-5" viewBox="0 0 24 24">
        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
    </svg>
    Continue with Google
</a>

{{-- Divider --}}
<div class="relative my-6">
    <div class="absolute inset-0 flex items-center">
        <div class="w-full border-t border-surface-border"></div>
    </div>
    <div class="relative flex justify-center text-xs">
        <span class="bg-surface-dark px-2 text-surface-mid-gray">or continue with email</span>
    </div>
</div>
```

---

### Task 8: Update Sidebar with Avatar

**Files:**

- Modify: `resources/views/livewire/layout/navigation.blade.php`

Replace user info section with avatar:

```blade
{{-- Before --}}
<div class="flex items-center gap-3 px-3 mb-3">
    <div class="flex-1 min-w-0">
        <div class="text-sm font-medium text-surface-off-white truncate">{{ auth()->user()?->name }}</div>
        <div class="text-xs text-surface-mid-gray truncate">{{ auth()->user()?->email }}</div>
    </div>
</div>

{{-- After --}}
<div class="flex items-center gap-3 px-3 mb-3">
    {{-- Avatar --}}
    @if(auth()->user()?->google_avatar)
        <img src="{{ auth()->user()->google_avatar }}"
             alt="{{ auth()->user()->name }}"
             class="w-8 h-8 rounded-full object-cover"
             referrerpolicy="no-referrer" />
    @else
        <div class="w-8 h-8 rounded-full bg-brand/20 flex items-center justify-center text-xs font-medium text-brand">
            {{ auth()->user()?->initials }}
        </div>
    @endif

    <div class="flex-1 min-w-0">
        <div class="text-sm font-medium text-surface-off-white truncate" x-data="{{ json_encode(['name' => auth()->user()?->name ?? '']) }}"
            x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
        <div class="text-xs text-surface-mid-gray truncate">{{ auth()->user()?->email }}</div>
    </div>
</div>
```

---

### Task 9: Update User Factory

**Files:**

- Modify: `database/factories/UserFactory.php`

```php
public function definition(): array
{
    return [
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'email_verified_at' => now(),
        'password' => static::$password ??= Hash::make('password'),
        'remember_token' => Str::random(10),
        'google_id' => null,
        'google_avatar' => null,
    ];
}

/**
 * Indicate that the user is a Google user.
 */
public function google(): static
{
    return $this->state(fn (array $attributes) => [
        'google_id' => fake()->unique()->numerify('##############'),
        'google_avatar' => fake()->imageUrl(100, 100, 'avatar'),
        'password' => null,
    ]);
}
```

---

### Task 10: Feature Tests

**Files:**

- Create: `tests/Feature/Auth/GoogleAuthTest.php`

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_login_page_has_button(): void
    {
        $response = $this->get('/login');

        $response->assertOk()
            ->assertSee('Continue with Google');
    }

    public function test_google_login_redirects_to_google(): void
    {
        // This test would need to mock Socialite
        // Skipping for now - requires Socialite::fake()
    }

    public function test_existing_email_rejects_google_login(): void
    {
        // Create user with manual registration
        $user = User::factory()->create(['email' => 'test@example.com']);

        // Mock Google user with same email
        // This would use Socialite::fake() in real implementation

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $this->assertNull($user->google_id);
    }

    public function test_google_user_has_initials(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);

        $this->assertEquals('JD', $user->initials);
    }

    public function test_google_user_initials_with_single_name(): void
    {
        $user = User::factory()->create(['name' => 'John']);

        $this->assertEquals('J', $user->initials);
    }
}
```

---

## File Summary

| File                                                        | Action | Description                  |
| ----------------------------------------------------------- | ------ | ---------------------------- |
| `composer.json`                                             | Modify | Add `laravel/socialite`      |
| `config/services.php`                                       | Modify | Add Google config            |
| `.env`                                                      | Modify | Add Google credentials       |
| `database/migrations/xxxx_add_google_id_to_users_table.php` | Create | Add google_id, google_avatar |
| `app/Models/User.php`                                       | Modify | Add fillable, methods        |
| `app/Http/Controllers/Auth/GoogleController.php`            | Create | OAuth flow                   |
| `routes/web.php`                                            | Modify | Add Google routes            |
| `resources/views/livewire/pages/auth/login.blade.php`       | Modify | Add Google button            |
| `resources/views/livewire/layout/navigation.blade.php`      | Modify | Add avatar                   |
| `database/factories/UserFactory.php`                        | Modify | Add google state             |
| `tests/Feature/Auth/GoogleAuthTest.php`                     | Create | Feature tests                |

---

## Security Considerations

| Concern         | Solution                                 |
| --------------- | ---------------------------------------- |
| Email conflict  | Reject if email exists without google_id |
| CSRF            | Laravel auto-handle                      |
| State parameter | Socialite auto-handle                    |
| Rate limiting   | Throttle OAuth redirect (5/min)          |
| Avatar referrer | `referrerpolicy="no-referrer"`           |

---

## Priority Matrix

| Phase | Task                          | Priority |
| ----- | ----------------------------- | -------- |
| 1     | Install Socialite + Migration | High     |
| 2     | GoogleController + Routes     | High     |
| 3     | Update Login UI               | High     |
| 4     | Update Sidebar Avatar         | Medium   |
| 5     | Tests                         | High     |

---

## Notes

- **No package needed for initials** — Pure PHP logic (`mb_strtoupper(mb_substr())`)
- **Google avatar** — Use `google_avatar` column, fallback to initials
- **Password nullable** — Social login users don't need password
- **Email verification** — Google email is pre-verified (`email_verified_at => now()`)
