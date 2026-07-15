# Laravel Auth Kit

Laravel authentication package with **Google**, **Facebook**, and **Iraqi phone OTP** (Asiacell / Korek / Zain), plus Sanctum API tokens and events.

**Package:** `emberrenewed/laravel-auth-kit`

---

## Installation

```bash
composer require emberrenewed/laravel-auth-kit:^1.1
php artisan auth-kit:install
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

`auth-kit:install` publishes config/migrations and adds env + `services.php` keys for Google, Facebook, and Iraqi SMS.

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
FACEBOOK_REDIRECT_URI="${APP_URL}/auth/facebook/callback"
IRAQI_SMS_ENDPOINT=
IRAQI_SMS_TOKEN=
IRAQI_SMS_FROM=
```

### Requirements

- PHP 8.3+
- Laravel 12 or 13
- `laravel/sanctum` `^4.0`
- `laravel/socialite` `^5.0`

### User model

Your `User` model must use Sanctum’s `HasApiTokens` and support:

`first_name`, `last_name`, `provider`, `provider_id`, `avatar`, `phone`, `banned_at`

```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
}
```

---

## Drivers

| Driver | Flavor | Description |
|--------|--------|-------------|
| `google` | api + web | Google OAuth / Socialite |
| `facebook` | api + web | Facebook OAuth / Socialite |
| `phone_otp` | api | Iraqi SMS OTP (Asiacell `077`/`078`, Korek `075`, Zain `079`) |

Config (`config/auth-kit.php`):

```php
'drivers' => [
    'web' => [
        'google' => true,
        'facebook' => true,
    ],
    'api' => [
        'google' => true,
        'facebook' => true,
        'phone_otp' => true,
    ],
],
```

---

## API endpoints

| Method | Path | Body |
|--------|------|------|
| GET | `/api/auth/providers` | — |
| POST | `/api/auth/google` | `access_token` |
| POST | `/api/auth/facebook` | `access_token` |
| POST | `/api/auth/otp/phone/send` | `phone` (Iraqi) |
| POST | `/api/auth/otp/phone/verify` | `phone`, `code` |
| POST | `/api/auth/logout` | Bearer token |

### Web

| Method | Path |
|--------|------|
| GET | `/auth/google/redirect` → `/auth/google/callback` |
| GET | `/auth/facebook/redirect` → `/auth/facebook/callback` |
| POST | `/auth/logout` |

---

## Iraqi phone OTP

Accepted formats: `07501234567`, `+9647501234567`, `9647501234567`.

Send response includes `carrier` (`asiacell` | `korek` | `zain`).

SMS is sent via `services.iraqi_sms.endpoint`. In `local` / `testing`, codes are logged when the endpoint is empty.

Store user `phone` as digits, e.g. `9647501234567`.

---

## Postman

Import `postman/AuthKit.postman_collection.json`.
