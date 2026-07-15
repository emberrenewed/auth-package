# Laravel Auth Kit

Clean authentication for Laravel apps: **Google**, **Facebook**, and **Iraqi phone OTP** (Asiacell / Korek / Zain), with Sanctum API tokens and domain events.

| | |
|---|---|
| **Package** | [`emberrenewed/laravel-auth-kit`](https://packagist.org/packages/emberrenewed/laravel-auth-kit) |
| **Require** | PHP 8.3+, Laravel 12 or 13 |
| **Depends on** | `laravel/sanctum` ^4, `laravel/socialite` ^5 |

---

## Install

```bash
composer require emberrenewed/laravel-auth-kit
php artisan auth-kit:install
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

`auth-kit:install` publishes config + migrations and adds Google, Facebook, and Iraqi SMS keys to `.env`, `.env.example`, and `config/services.php`.

Then fill your credentials:

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

### User model

Your `User` model must use Sanctum’s `HasApiTokens` and support these attributes:

`first_name`, `last_name`, `provider`, `provider_id`, `avatar`, `phone`, `banned_at`

```php
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
}
```

---

## Update

Pull the latest release from Packagist:

```bash
composer update emberrenewed/laravel-auth-kit
```

To lock a major line (recommended):

```bash
composer require emberrenewed/laravel-auth-kit:^1.2
```

After updating, re-publish config if you want package defaults refreshed:

```bash
php artisan vendor:publish --tag=auth-kit-config --force
php artisan migrate
```

> Packagist only sees tagged GitHub releases. New commits on `main` are not installable via Composer until a new version tag (e.g. `v1.2.0`) is published.

---

## Remove

Uninstall the package:

```bash
composer remove emberrenewed/laravel-auth-kit
```

Optional cleanup (manual):

1. Delete `config/auth-kit.php` if you published it.
2. Remove Auth Kit migration files under `database/migrations/` (only if unused).
3. Drop unused env keys: `GOOGLE_*`, `FACEBOOK_*`, `IRAQI_SMS_*`.
4. Remove matching blocks from `config/services.php`.

---

## Drivers

| Driver | Flavor | Description |
|--------|--------|-------------|
| `google` | api + web | Google OAuth / Socialite |
| `facebook` | api + web | Facebook OAuth / Socialite |
| `phone_otp` | api | Iraqi SMS OTP (Asiacell `077`/`078`, Korek `075`, Zain `079`) |

Toggle drivers in `config/auth-kit.php`:

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

SMS goes through `services.iraqi_sms.endpoint`. In `local` / `testing`, codes are logged when the endpoint is empty.

Store user `phone` as digits, e.g. `9647501234567`.

---

## Postman

Import [`postman/AuthKit.postman_collection.json`](postman/AuthKit.postman_collection.json).

---

## Links

- Packagist: https://packagist.org/packages/emberrenewed/laravel-auth-kit
- Source: https://github.com/emberrenewed/auth-package
- Changelog: [CHANGELOG.md](CHANGELOG.md)
