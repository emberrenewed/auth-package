# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- Renamed `Http/Callbacks` to `Http/Actions` with `*Action` class names (`AuthenticateAction`, `LogoutAction`, `SendOtpAction`, `ForgotPasswordAction`, `ResetPasswordAction`, `ListProvidersAction`, `RedirectToProviderAction`); AuthControllers still inject and invoke them

### Added

- Multi-driver authentication via `AuthDriver` contract and `DriverRegistry`
- Built-in `password` driver with rate limiting and identical failure responses
- Built-in `google` driver (API access-token + web OAuth callback) verifying tokens with Google/Socialite
- Configurable subject resolvers (`EloquentSubjectResolver`) with social linking and optional auto-create
- Sanctum API credential issuer and session web credential issuer
- Thin API/Web controllers backed by invokable HTTP actions for login, logout, password reset, redirect, and providers
- Route registration gated by `auth-kit.drivers.*` and `auth-kit.routes.*` config
- Password forgot/reset flows that never reveal email existence; reset revokes all Sanctum tokens
- Domain events: `LoginAttempted`, `LoginSucceeded`, `LoginFailed`, `SocialUserResolved`, `LoggedOut`
- Banned-subject protection (403)
- Publishable config and migrations
- Pint (`pint.json`) and PHPStan/Larastan (`phpstan.neon`, level 6) tooling
- Pest feature/unit test suite with Orchestra Testbench (coverage target ≥ 90%)
- README quick-start for install, Google OAuth, custom drivers, events, and Postman usage
