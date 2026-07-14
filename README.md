# Laravel Auth Kit

Laravel authentication package with email/password, Google / Facebook / GitHub Socialite, Email OTP, WhatsApp OTP, Sanctum API tokens, password reset, throttling, and events.

**Package:** `emberrenewed/laravel-auth-kit`  
**Repository:** https://github.com/emberrenewed/auth-kit-technoboase

---

## Installation

In your Laravel project:

```bash
composer require emberrenewed/laravel-auth-kit
php artisan auth-kit:install
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

`auth-kit:install` automatically:

- publishes `config/auth-kit.php` + migrations
- adds OAuth + WhatsApp keys to `.env` / `.env.example` if missing:

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
FACEBOOK_REDIRECT_URI="${APP_URL}/auth/facebook/callback"
GITHUB_CLIENT_ID=
GITHUB_CLIENT_SECRET=
GITHUB_REDIRECT_URI="${APP_URL}/auth/github/callback"
WHATSAPP_TOKEN=
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_OTP_TEMPLATE=auth_otp
```

- adds Socialite + WhatsApp blocks to `config/services.php` if missing

Paste real Client IDs / Secrets from the provider consoles (and Meta WhatsApp Cloud credentials for OTP).

### If Packagist is not available yet

Install directly from GitHub:

```bash
composer config repositories.auth-kit vcs https://github.com/emberrenewed/auth-kit-technoboase
composer require emberrenewed/laravel-auth-kit:^1.0
```

### Requirements

- PHP 8.3+
- Laravel 12 or 13
- `laravel/sanctum` `^4.0`
- `laravel/socialite` `^5.0` (Google / Facebook / GitHub)

### User model

Your `User` model must use Sanctum’s `HasApiTokens` and support Auth Kit columns:

`first_name`, `last_name`, `provider`, `provider_id`, `avatar`, `phone`, `banned_at`

```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
}
```

---

## Quick start (API)

```http
POST /api/auth/login
Content-Type: application/json
Accept: application/json

{
  "email": "user@example.com",
  "password": "password"
}
```

Success:

```json
{
  "data": { "user": { "id": 1, "email": "user@example.com" } },
  "token": "1|xxxxxxxx"
}
```

### Main API routes

| Method | URL | Body / auth |
|--------|-----|-------------|
| GET | `/api/auth/providers` | — |
| POST | `/api/auth/login` | `email`, `password` |
| POST | `/api/auth/google` | `access_token` |
| POST | `/api/auth/facebook` | `access_token` |
| POST | `/api/auth/github` | `access_token` |
| POST | `/api/auth/otp/email/send` | `email` |
| POST | `/api/auth/otp/email/verify` | `email`, `code` |
| POST | `/api/auth/otp/whatsapp/send` | `phone` |
| POST | `/api/auth/otp/whatsapp/verify` | `phone`, `code` |
| POST | `/api/auth/logout` | Bearer token |
| POST | `/api/auth/forgot-password` | `email` |
| POST | `/api/auth/reset-password` | `email`, `token`, `password`, `password_confirmation` |

---

## Configuration

Publish `config/auth-kit.php` and set:

| Key | Purpose |
|-----|---------|
| `subjects.api` / `subjects.web` | Model, guard, resolver, `auto_create_on_social`, `lookup_columns` |
| `drivers.api` / `drivers.web` | Enabled drivers |
| `routes.api` / `routes.web` | Prefix + middleware |
| `throttle` | Login rate limit |
| `password_reset.broker` | Password broker name |
| `otp` | Length, TTL, max attempts, channel classes |

Default drivers:

```php
'drivers' => [
    'web' => ['password', 'google', 'facebook', 'github'],
    'api' => ['password', 'google', 'facebook', 'github', 'email_otp', 'whatsapp_otp'],
],
```

---

## Social OAuth (Google / Facebook / GitHub)

```bash
php artisan auth-kit:install
```

Fill `.env` for each provider you enable.

- **API:** `POST /api/auth/{driver}` with `{ "access_token": "..." }`
- **Web:** `GET /auth/{driver}/redirect` → callback

---

## Email OTP

```http
POST /api/auth/otp/email/send
{ "email": "user@example.com" }

POST /api/auth/otp/email/verify
{ "email": "user@example.com", "code": "123456" }
```

Codes are hashed in `auth_kit_otps`, mailed via `MailOtpChannel`, and expire per `auth-kit.otp.ttl_seconds` (default 300).

With `auto_create_on_social=false`, the email must already belong to a user (or be findable via `lookup_columns`).

---

## WhatsApp OTP (Meta Cloud API)

Not OAuth — phone OTP via WhatsApp Cloud API.

```env
WHATSAPP_TOKEN=
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_OTP_TEMPLATE=auth_otp
```

```http
POST /api/auth/otp/whatsapp/send
{ "phone": "+15551234567" }

POST /api/auth/otp/whatsapp/verify
{ "phone": "+15551234567", "code": "123456" }
```

Users are resolved by `phone` / `provider`+`provider_id`. In `local`/`testing`, missing WhatsApp credentials fall back to `LogOtpChannel` (code written to the log).

---

## Events

- `LoginAttempted`
- `LoginSucceeded`
- `LoginFailed`
- `SocialUserResolved`
- `LoggedOut`

```php
use Technobase\AuthKit\Events\LoginSucceeded;

Event::listen(LoginSucceeded::class, function (LoginSucceeded $event): void {
    //
});
```

---

## API response shapes

| Status | When |
|--------|------|
| **200** | Login / logout / forgot / reset / OTP send success |
| **401** | Bad credentials / invalid social token / invalid or expired OTP / throttled |
| **403** | Banned user |
| **404** | Social / OTP user not found (`auto_create_on_social=false`) |
| **422** | Invalid / expired reset token |

Forgot password and OTP send return **200** without revealing whether the destination exists as an account.

---

## Postman

Import [`postman/AuthKit.postman_collection.json`](postman/AuthKit.postman_collection.json)  
Set `base_url` to your app (e.g. `http://127.0.0.1:8000`).

---

## License

MIT
