# shory.ly URL Shortener — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a complete URL shortener with auth (register, login, OTP password reset), landing page, dashboard, link management, and click analytics — all styled with the Supabase design system.

**Architecture:** Laravel 13 + Livewire/Volt (anonymous class-based SFC) + Tailwind CSS + PostgreSQL. Single-page-like navigation via `wire:navigate`. Models use PHP 8 attributes. Volt components handle all interactive pages.

**Tech Stack:** Laravel 13, Livewire 3, Volt 1, Tailwind CSS 3, Blade Components, Alpine.js, PostgreSQL

---

## Phase 1: Database Foundation

### Task 1: Update Tailwind config for Supabase design system

**Files:**

- Modify: `tailwind.config.js`

**Step 1: Update tailwind.config.js with Supabase design tokens**

Replace the entire file:

```js
import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    DEFAULT: "#3ecf8e",
                    dark: "#00c573",
                },
                surface: {
                    black: "#0f0f0f",
                    dark: "#171717",
                    "dark-border": "#242424",
                    border: "#2e2e2e",
                    "mid-border": "#363636",
                    "light-border": "#393939",
                    charcoal: "#434343",
                    "dark-gray": "#4d4d4d",
                    "mid-gray": "#898989",
                    "light-gray": "#b4b4b4",
                    "near-white": "#efefef",
                    "off-white": "#fafafa",
                },
            },
            borderRadius: {
                pill: "9999px",
            },
        },
    },

    plugins: [forms],
};
```

**Step 2: Verify build works**

Run: `npx tailwindcss --help`
Expected: no errors

**Step 3: Commit**

```bash
git add tailwind.config.js
git commit -m "feat: add Supabase design tokens to Tailwind config"
```

---

### Task 2: Create links table migration

**Files:**

- Create: `database/migrations/2026_04_28_000001_create_links_table.php`

**Step 1: Generate migration**

Run: `php artisan make:migration create_links_table --no-interaction`

**Step 2: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 100)->nullable();
            $table->text('original_url');
            $table->string('short_code', 10)->unique();
            $table->unsignedSmallInteger('status')->default(1);
            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};
```

**Step 3: Run migration**

Run: `php artisan migrate --no-interaction`
Expected: links table created

**Step 4: Verify table**

Run via Laravel Boost: `database-schema` with filter `links`

**Step 5: Commit**

```bash
git add database/migrations/
git commit -m "feat: create links table migration"
```

---

### Task 3: Rebuild logs table as link_logs

**Files:**

- Create: `database/migrations/2026_04_28_000002_create_link_logs_table.php`

**Step 1: Generate migration**

Run: `php artisan make:migration create_link_logs_table --no-interaction`

**Step 2: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('link_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')->constrained()->cascadeOnDelete();
            $table->timestamp('clicked_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('referrer')->nullable();
            $table->timestamps();

            $table->index('link_id');
            $table->index(['link_id', 'clicked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_logs');
    }
};
```

**Step 3: Run migration**

Run: `php artisan migrate --no-interaction`
Expected: link_logs table created

**Step 4: Commit**

```bash
git add database/migrations/
git commit -m "feat: create link_logs table migration"
```

---

### Task 4: Create password_reset_otps table migration

**Files:**

- Create: `database/migrations/2026_04_28_000003_create_password_reset_otps_table.php`

**Step 1: Generate migration**

Run: `php artisan make:migration create_password_reset_otps_table --no-interaction`

**Step 2: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_otps', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255);
            $table->string('otp', 6);
            $table->timestamp('used_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('email');
            $table->index(['email', 'otp', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_otps');
    }
};
```

**Step 3: Run migration**

Run: `php artisan migrate --no-interaction`
Expected: password_reset_otps table created

**Step 4: Commit**

```bash
git add database/migrations/
git commit -m "feat: create password_reset_otps table for OTP flow"
```

---

### Task 5: Create Eloquent models

**Files:**

- Modify: `app/Models/log.php` → rename to `LinkLog`
- Create: `app/Models/Link.php`
- Create: `app/Models/PasswordResetOtp.php`
- Modify: `app/Models/User.php` (add links relationship)

**Step 1: Delete old log model, create new models**

```bash
rm app/Models/log.php
php artisan make:model Link --no-interaction
php artisan make:model LinkLog --no-interaction
php artisan make:model PasswordResetOtp --no-interaction
```

**Step 2: Write Link model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'title', 'original_url', 'short_code', 'status'])]
class Link extends Model
{
    protected function casts(): array
    {
        return [
            'status' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(LinkLog::class);
    }

    public function isActive(): bool
    {
        return $this->status === 1;
    }

    public function clickCount(): int
    {
        return $this->logs()->count();
    }

    public function uniqueVisitorCount(): int
    {
        return $this->logs()->distinct('ip_address')->count('ip_address');
    }
}
```

**Step 3: Write LinkLog model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['link_id', 'clicked_at', 'ip_address', 'user_agent', 'referrer'])]
class LinkLog extends Model
{
    protected $table = 'link_logs';

    protected function casts(): array
    {
        return [
            'clicked_at' => 'datetime',
        ];
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}
```

**Step 4: Write PasswordResetOtp model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['email', 'otp', 'used_at', 'expires_at'])]
class PasswordResetOtp extends Model
{
    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->isUsed();
    }
}
```

**Step 5: Add links relationship to User model**

Add to `app/Models/User.php`:

```php
use Illuminate\Database\Eloquent\Relations\HasMany;

public function links(): HasMany
{
    return $this->hasMany(Link::class);
}
```

**Step 6: Verify models load**

Run: `php artisan tinker --execute 'App\Models\Link::first(); App\Models\LinkLog::first(); App\Models\PasswordResetOtp::first(); echo "OK";'`
Expected: OK

**Step 7: Commit**

```bash
git add app/Models/
git commit -m "feat: create Link, LinkLog, PasswordResetOtp models with relationships"
```

---

### Task 6: Create model factories

**Files:**

- Create: `database/factories/LinkFactory.php`
- Create: `database/factories/LinkLogFactory.php`

**Step 1: Generate factories**

```bash
php artisan make:factory LinkFactory --no-interaction
php artisan make:factory LinkLogFactory --no-interaction
```

**Step 2: Write LinkFactory**

```php
<?php

namespace Database\Factories;

use App\Models\Link;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LinkFactory extends Factory
{
    protected $model = Link::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'original_url' => fake()->url(),
            'short_code' => Str::random(6),
            'status' => 1,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 2,
        ]);
    }
}
```

**Step 3: Write LinkLogFactory**

```php
<?php

namespace Database\Factories;

use App\Models\LinkLog;
use App\Models\Link;
use Illuminate\Database\Eloquent\Factories\Factory;

class LinkLogFactory extends Factory
{
    protected $model = LinkLog::class;

    public function definition(): array
    {
        return [
            'link_id' => Link::factory(),
            'clicked_at' => now(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'referrer' => fake()->optional()->url(),
        ];
    }
}
```

**Step 4: Add HasFactory to Link and LinkLog models**

Ensure both models have `use HasFactory;` trait imported and used.

**Step 5: Verify factories work**

Run: `php artisan tinker --execute 'App\Models\Link::factory()->make(); echo "OK";'`
Expected: OK

**Step 6: Commit**

```bash
git add database/factories/
git commit -m "feat: create Link and LinkLog factories"
```

---

## Phase 2: Short Code Service & Link Redirect

### Task 7: Create ShortCodeService

**Files:**

- Create: `app/Services/ShortCodeService.php`
- Test: `tests/Unit/ShortCodeServiceTest.php`

**Step 1: Write the test**

```php
<?php

namespace Tests\Unit;

use App\Services\ShortCodeService;
use PHPUnit\Framework\TestCase;

class ShortCodeServiceTest extends TestCase
{
    public function test_generates_six_character_code(): void
    {
        $code = ShortCodeService::generate();

        $this->assertSame(6, strlen($code));
    }

    public function test_code_is_alphanumeric(): void
    {
        $code = ShortCodeService::generate();

        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $code);
    }

    public function test_generates_unique_codes(): void
    {
        $codes = collect(range(1, 100))->map(fn () => ShortCodeService::generate());

        $this->assertSame(100, $codes->unique()->count());
    }
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Unit/ShortCodeServiceTest.php`
Expected: FAIL — class not found

**Step 3: Write ShortCodeService**

```php
<?php

namespace App\Services;

use Illuminate\Support\Str;

class ShortCodeService
{
    public static function generate(int $length = 6): string
    {
        return Str::random($length);
    }

    public static function generateUnique(int $maxAttempts = 3): string
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            $code = static::generate();

            if (! \App\Models\Link::where('short_code', $code)->exists()) {
                return $code;
            }
        }

        throw new \RuntimeException('Unable to generate unique short code after ' . $maxAttempts . ' attempts.');
    }
}
```

**Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Unit/ShortCodeServiceTest.php`
Expected: PASS

**Step 5: Commit**

```bash
git add app/Services/ tests/Unit/ShortCodeServiceTest.php
git commit -m "feat: add ShortCodeService for generating unique short codes"
```

---

### Task 8: Create link redirect route with click logging

**Files:**

- Modify: `routes/web.php`
- Create: `app/Http/Middleware/LogLinkClick.php` (or use a controller)

**Step 1: Write the redirect test**

```php
<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\User;
use Tests\TestCase;

class LinkRedirectTest extends TestCase
{
    public function test_redirects_active_link_to_original_url(): void
    {
        $link = Link::factory()->create([
            'short_code' => 'abc123',
            'original_url' => 'https://example.com/very-long-url',
            'status' => 1,
        ]);

        $response = $this->get('/abc123');

        $response->assertRedirect('https://example.com/very-long-url');
    }

    public function test_returns_404_for_unknown_short_code(): void
    {
        $response = $this->get('/nonexistent');

        $response->assertNotFound();
    }

    public function test_returns_404_for_archived_link(): void
    {
        Link::factory()->create([
            'short_code' => 'archiv1',
            'status' => 2,
        ]);

        $response = $this->get('/archiv1');

        $response->assertNotFound();
    }

    public function test_logs_click_on_redirect(): void
    {
        $link = Link::factory()->create([
            'short_code' => 'logged1',
            'status' => 1,
        ]);

        $this->get('/logged1');

        $this->assertDatabaseHas('link_logs', [
            'link_id' => $link->id,
        ]);
    }
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/LinkRedirectTest.php`
Expected: FAIL

**Step 3: Add redirect route to routes/web.php**

Add before the existing `/` route (routes are matched top-to-bottom, short code redirect should be caught carefully):

```php
// In routes/web.php, add a dedicated route for short code redirects
// This must be registered carefully to not conflict with other routes
Route::get('/s/{short_code}', function (string $short_code) {
    $link = \App\Models\Link::where('short_code', $short_code)
        ->where('status', 1)
        ->firstOrFail();

    \App\Models\LinkLog::create([
        'link_id' => $link->id,
        'clicked_at' => now(),
        'ip_address' => request()->ip(),
        'user_agent' => request()->userAgent(),
        'referrer' => request()->header('referer'),
    ]);

    return redirect($link->original_url, 301);
})->name('redirect');
```

> **Note:** Using `/s/{short_code}` prefix to avoid conflicts with other routes like `/dashboard`, `/links`, etc. The tutorial uses root-level `/{short_code}` but that requires careful route ordering and collision prevention.

**Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/LinkRedirectTest.php`
Expected: PASS

**Step 5: Commit**

```bash
git add routes/web.php tests/Feature/LinkRedirectTest.php
git commit -m "feat: add short URL redirect route with click logging"
```

---

## Phase 3: Core Pages (Volt Components)

### Task 9: Landing page

**Files:**

- Modify: `resources/views/welcome/navigation.blade.php` (or replace welcome view)
- Modify: `routes/web.php`

> Follow DESIGN.md: dark background (#171717), emerald green accents, hero with tight line-height, pill CTAs.

**Step 1: Write test**

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_landing_page_is_accessible(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('shory.ly');
    }

    public function test_landing_page_shows_login_link(): void
    {
        $response = $this->get('/');

        $response->assertSee(route('login'));
    }

    public function test_authenticated_user_redirected_to_dashboard(): void
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect(route('dashboard'));
    }
}
```

**Step 2: Run test to verify current state**

Run: `php artisan test --compact tests/Feature/LandingPageTest.php`

**Step 3: Create landing page Volt component**

Create: `resources/views/livewire/welcome/landing.blade.php`

Build using Supabase design system — dark hero, green CTAs, feature highlights. Use `<x-primary-button>` and existing components where possible.

**Step 4: Update route**

In `routes/web.php`, change `Route::view('/', 'welcome')` to redirect authenticated users:

```php
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome.landing');
})->name('landing');
```

**Step 5: Run test**

Run: `php artisan test --compact tests/Feature/LandingPageTest.php`
Expected: PASS

**Step 6: Build assets and verify visually**

Run: `npm run build`

**Step 7: Commit**

```bash
git add resources/views/ routes/web.php tests/Feature/LandingPageTest.php
git commit -m "feat: add landing page with Supabase design system"
```

---

### Task 10: Dashboard page

**Files:**

- Create: `resources/views/livewire/dashboard.blade.php` (or modify existing dashboard view)
- Modify: `routes/web.php`

> Show: Total Links, Total Clicks, Unique Visitors, Popular Links (top 5), click trend placeholder. Dark cards with border-defined depth.

**Step 1: Write test**

```php
<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\LinkLog;
use App\Models\User;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_shows_user_stats(): void
    {
        $user = User::factory()->create();
        $links = Link::factory()->count(3)->create(['user_id' => $user->id]);

        LinkLog::factory()->count(10)->create(['link_id' => $links->first()->id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('3'); // total links
    }

    public function test_dashboard_shows_popular_links(): void
    {
        $user = User::factory()->create();
        $popularLink = Link::factory()->create([
            'user_id' => $user->id,
            'title' => 'Popular Link',
        ]);
        LinkLog::factory()->count(5)->create(['link_id' => $popularLink->id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('Popular Link');
    }
}
```

**Step 2: Run test to verify it fails**

**Step 3: Create dashboard Volt component**

Create: `resources/views/livewire/dashboard.blade.php`

Stats cards in a grid, popular links list, dark theme. Query only the authenticated user's links.

**Step 4: Update route**

```php
Volt::route('dashboard', 'dashboard')->middleware(['auth', 'verified'])->name('dashboard');
```

**Step 5: Run test**

**Step 6: Commit**

```bash
git add resources/views/livewire/dashboard.blade.php routes/web.php tests/Feature/DashboardTest.php
git commit -m "feat: add dashboard with stats and popular links"
```

---

### Task 11: Links list page (card view)

**Files:**

- Create: `resources/views/livewire/links/index.blade.php`
- Modify: `routes/web.php`

> Card grid showing each link: title, short URL, original URL (truncated), date, status badge, Share/Detail/Delete buttons. Add URL modal/form at top.

**Step 1: Write test**

```php
<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\User;
use Tests\TestCase;

class LinksListTest extends TestCase
{
    public function test_links_page_requires_auth(): void
    {
        $this->get('/links')->assertRedirect(route('login'));
    }

    public function test_shows_user_links_as_cards(): void
    {
        $user = User::factory()->create();
        Link::factory()->count(3)->create(['user_id' => $user->id, 'title' => 'My Link']);

        $response = $this->actingAs($user)->get('/links');

        $response->assertOk();
        $response->assertSee('My Link');
    }

    public function test_does_not_show_other_users_links(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Link::factory()->create(['user_id' => $otherUser->id, 'title' => 'Secret Link']);

        $response = $this->actingAs($user)->get('/links');

        $response->assertOk();
        $response->assertDontSee('Secret Link');
    }

    public function test_can_create_new_link(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/links', [
            'original_url' => 'https://example.com',
            'title' => 'Test Link',
        ]);

        $response->assertRedirect('/links');
        $this->assertDatabaseHas('links', [
            'user_id' => $user->id,
            'original_url' => 'https://example.com',
            'title' => 'Test Link',
            'status' => 1,
        ]);
    }
}
```

**Step 2: Run test to verify it fails**

**Step 3: Create Volt component with card layout**

Create: `resources/views/livewire/links/index.blade.php`

Properties: `$links` (computed), `$original_url`, `$title`
Actions: `create()`, `delete($id)`
Template: card grid, dark theme, green accent on short URLs

**Step 4: Add routes**

```php
Volt::route('links', 'links.index')->middleware(['auth', 'verified'])->name('links.index');
Route::post('/links', [LinkController::class, 'store'])->middleware(['auth', 'verified'])->name('links.store');
Route::delete('/links/{link}', [LinkController::class, 'destroy'])->middleware(['auth', 'verified'])->name('links.destroy');
```

**Step 5: Run test**

**Step 6: Commit**

```bash
git add resources/views/livewire/links/ routes/ tests/Feature/LinksListTest.php
git commit -m "feat: add links list page with card view and CRUD"
```

---

### Task 12: Link detail page with analytics

**Files:**

- Create: `resources/views/livewire/links/show.blade.php`
- Modify: `routes/web.php`

> Show: link info (short URL, original URL, title, status, date), Share button, analytics section (Total Clicks, Unique Visitors, click trend placeholder, Top Referrers).

**Step 1: Write test**

```php
<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\LinkLog;
use App\Models\User;
use Tests\TestCase;

class LinkDetailTest extends TestCase
{
    public function test_shows_link_detail(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->create(['user_id' => $user->id, 'title' => 'Detail Link']);

        $response = $this->actingAs($user)->get('/links/' . $link->id);

        $response->assertOk();
        $response->assertSee('Detail Link');
    }

    public function test_cannot_view_other_users_link(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $link = Link::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get('/links/' . $link->id);

        $response->assertForbidden();
    }

    public function test_shows_click_analytics(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->create(['user_id' => $user->id]);
        LinkLog::factory()->count(5)->create(['link_id' => $link->id]);

        $response = $this->actingAs($user)->get('/links/' . $link->id);

        $response->assertSee('5'); // total clicks
    }
}
```

**Step 2: Run test to verify it fails**

**Step 3: Create Volt component**

Create: `resources/views/livewire/links/show.blade.php`

Mount: load link via route model binding, ensure `$link->user_id === auth()->id()`
Display: link info card, analytics stats, click trend area

**Step 4: Add route**

```php
Volt::route('links/{link}', 'links.show')
    ->middleware(['auth', 'verified'])
    ->name('links.show');
```

**Step 5: Run test**

**Step 6: Commit**

```bash
git add resources/views/livewire/links/show.blade.php routes/ tests/Feature/LinkDetailTest.php
git commit -m "feat: add link detail page with analytics"
```

---

## Phase 4: OTP Password Reset

### Task 13: Password validation defaults

**Files:**

- Modify: `app/Providers/AppServiceProvider.php`

**Step 1: Add password defaults to AppServiceProvider boot()**

```php
use Illuminate\Validation\Rules\Password;

public function boot(): void
{
    Password::defaults(function () {
        return Password::min(8)
            ->mixedCase()
            ->numbers()
            ->symbols();
    });
}
```

**Step 2: Commit**

```bash
git add app/Providers/AppServiceProvider.php
git commit -m "feat: set global password complexity rules"
```

---

### Task 14: OTP notification

**Files:**

- Create: `app/Notifications/SendOtpNotification.php`

**Step 1: Create notification**

Run: `php artisan make:notification SendOtpNotification --no-interaction`

**Step 2: Write notification**

```php
<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SendOtpNotification extends Notification
{
    public function __construct(
        public string $otp,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Password Reset Code')
            ->greeting('Hello!')
            ->line("Your password reset code is: **{$this->otp}**")
            ->line('This code will expire in 15 minutes.')
            ->line('If you did not request this, please ignore this email.');
    }
}
```

**Step 3: Commit**

```bash
git add app/Notifications/
git commit -m "feat: add OTP email notification"
```

---

### Task 15: OTP password reset Volt pages

**Files:**

- Create: `resources/views/livewire/pages/auth/forgot-password.blade.php`
- Create: `resources/views/livewire/pages/auth/verify-otp.blade.php`
- Create: `resources/views/livewire/pages/auth/reset-password.blade.php`
- Modify: `routes/auth.php`

**Step 1: Write test**

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetOtpTest extends TestCase
{
    public function test_forgot_password_page_sends_otp(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = $this->post('/forgot-password', ['email' => 'test@example.com']);

        $response->assertRedirect('/verify-otp?email=test@example.com');
        $this->assertDatabaseHas('password_reset_otps', [
            'email' => 'test@example.com',
        ]);
        Notification::assertSentTo($user, \App\Notifications\SendOtpNotification::class);
    }

    public function test_verify_otp_accepts_valid_code(): void
    {
        $otp = PasswordResetOtp::create([
            'email' => 'test@example.com',
            'otp' => '123456',
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->post('/verify-otp', [
            'email' => 'test@example.com',
            'otp' => '123456',
        ]);

        $response->assertSessionHas('otp_verified_email');
        $response->assertRedirect('/reset-password');
    }

    public function test_verify_otp_rejects_expired_code(): void
    {
        PasswordResetOtp::create([
            'email' => 'test@example.com',
            'otp' => '123456',
            'expires_at' => now()->subMinutes(1),
        ]);

        $response = $this->post('/verify-otp', [
            'email' => 'test@example.com',
            'otp' => '123456',
        ]);

        $response->assertSessionHasErrors('otp');
    }

    public function test_reset_password_updates_password(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = $this->withSession(['otp_verified_email' => 'test@example.com'])
            ->post('/reset-password', [
                'password' => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ]);

        $response->assertRedirect(route('login'));
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewPassword1!', $user->fresh()->password));
    }
}
```

**Step 2: Run test to verify it fails**

**Step 3: Create the three Volt components**

Each follows the anonymous class-based SFC pattern. Dark theme per DESIGN.md.

**Step 4: Add routes to routes/auth.php**

```php
Route::middleware('guest')->group(function () {
    // ... existing routes ...

    Route::get('/forgot-password', fn () => view('livewire.pages.auth.forgot-password'))
        ->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendOtp'])
        ->name('password.email');
    Route::get('/verify-otp', fn () => view('livewire.pages.auth.verify-otp'))
        ->name('password.otp');
    Route::post('/verify-otp', [PasswordResetController::class, 'verifyOtp'])
        ->name('password.verify');
    Route::get('/reset-password', fn () => view('livewire.pages.auth.reset-password'))
        ->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
        ->name('password.update');
});
```

**Step 5: Run test**

**Step 6: Commit**

```bash
git add resources/views/livewire/pages/auth/ routes/ app/Http/Controllers/ tests/Feature/Auth/PasswordResetOtpTest.php
git commit -m "feat: add OTP-based password reset flow"
```

---

## Phase 5: Navigation & Polish

### Task 16: App layout sidebar navigation

**Files:**

- Modify: `resources/views/livewire/layout/navigation.blade.php`
- Modify: `resources/views/layouts/app.blade.php`

> Dark sidebar: Dashboard, Short URLs links. User avatar/logout top right. Green accent on active item. Follows tutorial screenshot layout.

**Step 1: Update navigation to match dashboard screenshots**

Dark sidebar with nav items: Dashboard, Short URLs. Top bar with user menu. All styled per DESIGN.md.

**Step 2: Commit**

```bash
git add resources/views/
git commit -m "feat: update app layout with Supabase-styled sidebar navigation"
```

---

### Task 17: Run pint formatter and full test suite

**Step 1: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

**Step 2: Run full test suite**

Run: `php artisan test --compact`

**Step 3: Fix any failures**

**Step 4: Final commit**

```bash
git add .
git commit -m "chore: lint and verify full test suite passes"
```
