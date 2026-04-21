# Changelog

## [Unreleased]

### Added
- Driver-based core framework: `PayifyManager`, `AbstractDriver`, `FakeDriver`.
- Unified DTOs: `PaymentRequest`, `PaymentResponse`, `RefundRequest`, `RefundResponse`, `StatusResponse`, `WebhookPayload`, `TokenResponse`, `Customer`.
- `Transaction` Eloquent model with UUID PK, polymorphic `payable`, soft deletes, lifecycle helpers.
- Guzzle-backed `PayifyHttpClient` with retry, logging, secret-masking middleware.
- Webhook + callback pipeline with queue support and Telescope-style route override.
- Lifecycle events and typed exception hierarchy.
- Artisan commands: install, make-driver, list, status, refund, webhook:replay, cleanup.
- Pest test harness with `Payify::fake()` + assertions.
