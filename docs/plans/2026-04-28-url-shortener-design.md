# shory.ly — URL Shortener Design

## Overview

A Laravel 13 URL shortener with analytics, OTP-based password reset, and Supabase-inspired dark UI. Built with Livewire/Volt, Tailwind CSS, and PostgreSQL.

## Tech Stack

- **Backend**: Laravel 13, PHP 8.4
- **Frontend**: Livewire 3, Volt 1, Tailwind CSS 3, Blade
- **Auth**: Laravel Breeze (customized with OTP password reset)
- **Design**: Supabase design system (DESIGN.md)
- **Database**: PostgreSQL

## Database Schema

### `users` (existing — no changes)

| Column            | Type         | Notes          |
| ----------------- | ------------ | -------------- |
| id                | bigint (PK)  | auto-increment |
| name              | varchar(255) |                |
| email             | varchar(255) | unique         |
| email_verified_at | timestamp    | nullable       |
| password          | varchar(255) | bcrypt hashed  |
| remember_token    | varchar(100) | nullable       |
| created_at        | timestamp    |                |
| updated_at        | timestamp    |                |

### `links` (new)

| Column       | Type                   | Notes                                 |
| ------------ | ---------------------- | ------------------------------------- |
| id           | bigint (PK)            | auto-increment                        |
| user_id      | bigint (FK → users.id) | owner, cascade on delete              |
| title        | varchar(100)           | nullable, display name                |
| original_url | text                   | the long URL                          |
| short_code   | varchar(10)            | unique, indexed — 6-char alphanumeric |
| status       | smallint               | 1=active, 2=archived                  |
| created_at   | timestamp              |                                       |
| updated_at   | timestamp              |                                       |

**Indexes**: `short_code` unique index (hot path for redirects)

### `link_logs` (new — replaces empty `logs` table)

| Column     | Type                   | Notes                           |
| ---------- | ---------------------- | ------------------------------- |
| id         | bigint (PK)            | auto-increment                  |
| link_id    | bigint (FK → links.id) | cascade on delete               |
| clicked_at | timestamp              | when the click happened         |
| ip_address | varchar(45)            | visitor IP (IPv6 safe)          |
| user_agent | text                   | nullable, browser info          |
| referrer   | text                   | nullable, where click came from |
| created_at | timestamp              |                                 |
| updated_at | timestamp              |                                 |

**Indexes**: `link_id` index (analytics queries), composite `(link_id, clicked_at)` for trend queries

### `password_reset_otps` (new)

| Column     | Type         | Notes                    |
| ---------- | ------------ | ------------------------ |
| id         | bigint (PK)  | auto-increment           |
| email      | varchar(255) | indexed — who requested  |
| otp        | varchar(6)   | 6-digit numeric code     |
| used_at    | timestamp    | nullable — when consumed |
| expires_at | timestamp    | 15 min from creation     |
| created_at | timestamp    |                          |
| updated_at | timestamp    |                          |

**Indexes**: `email` index, composite `(email, otp, used_at)` for lookup

### Relationships

- User hasMany Links (1:M)
- Link hasMany LinkLogs (1:M)

## Eloquent Models

- `User` → `links()`: HasMany
- `Link` → `user()`: BelongsTo, `logs()`: HasMany
- `LinkLog` → `link()`: BelongsTo
- `PasswordResetOtp` → standalone (no FK to users — works for unauthenticated flow)

## Pages & Routes

### Public Routes

| Method | URI                | Name             | Description                    |
| ------ | ------------------ | ---------------- | ------------------------------ |
| GET    | `/`                | landing          | Hero, features, CTA            |
| GET    | `/{short_code}`    | redirect         | Resolve and redirect short URL |
| GET    | `/forgot-password` | password.request | Enter email form               |
| POST   | `/forgot-password` | password.email   | Send OTP to email              |
| GET    | `/verify-otp`      | password.otp     | OTP input form                 |
| POST   | `/verify-otp`      | password.verify  | Validate OTP                   |
| GET    | `/reset-password`  | password.reset   | New password form (isolated)   |
| POST   | `/reset-password`  | password.update  | Save new password              |

### Authenticated Routes (middleware: auth, verified)

| Method | URI             | Name          | Description                           |
| ------ | --------------- | ------------- | ------------------------------------- |
| GET    | `/dashboard`    | dashboard     | Stats overview, charts, popular links |
| GET    | `/links`        | links.index   | Card list of user links               |
| POST   | `/links`        | links.store   | Create short URL                      |
| GET    | `/links/{link}` | links.show    | Link detail + analytics               |
| DELETE | `/links/{link}` | links.destroy | Delete link                           |

## Short Code Generation

- **Length**: 6 characters
- **Charset**: `abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789`
- **Collision handling**: Generate → check unique → retry (max 3 attempts, then throw)
- **No custom aliases** in v1

## Forgot Password OTP Flow

```
User clicks "Forgot Password"
  → enters email on /forgot-password
  → POST generates 6-digit OTP
  → stores in password_reset_otps with 15min expiry
  → sends OTP via Laravel Notification (email)

User enters OTP on /verify-otp
  → POST validates: OTP exists, not expired, not used, email matches
  → max 5 attempts per session, then lockout

If valid → redirect to /reset-password (isolated page)
  → POST validates new password: min 8, mixedCase, numbers, symbols
  → updates user password
  → marks OTP as used_at
  → redirects to login
```

### Password Validation Rules

Applied globally via `AppServiceProvider::boot()`:

```php
Password::defaults(function () {
    return Password::min(8)
        ->mixedCase()
        ->numbers()
        ->symbols();
});
```

### OTP Security

- Rate limit: 3 OTP requests per email per hour
- OTP expiry: 15 minutes
- Single use: `used_at` prevents replay
- Brute force: 5 OTP verification attempts, then lockout

## Design System

All UI follows DESIGN.md (Supabase-inspired):

- Dark-mode-native: `#171717` page background
- Emerald green accents: `#3ecf8e` brand, `#00c573` links
- Border-defined depth: `#242424` → `#2e2e2e` → `#363636`
- No shadows — depth through borders only
- Pill buttons (9999px) for primary CTAs
- Weight 400 default, 500 for interactive elements only
- Tailwind CSS classes throughout

## Link Redirect Flow

```
GET /{short_code}
  → cache check (Redis/database cache)
  → if miss: query links table by short_code
  → if found and active (status=1):
    → async dispatch LinkLog job (ip, user_agent, referrer, clicked_at)
    → 301 redirect to original_url
  → if not found: 404
```

## Analytics Features (Dashboard + Link Detail)

- Total clicks (all links or single link)
- Unique visitors (distinct IP addresses)
- Click trend chart (7-day / 30-day)
- Popular links (top 5 by click count)
- Referrer breakdown (where traffic comes from)

## Scope — v1 Only

- Auth (register, login, OTP password reset, email verification)
- Landing page (public)
- Dashboard (stats overview)
- Links list (card view)
- Link detail (analytics)
- Short URL creation and redirect
- Click logging

## Out of Scope (v2+)

- QR code generation
- Custom aliases
- Link expiration
- Password-protected links
- UTM parameters
- Device/OS/browser breakdown
- API endpoints
- Custom domains
