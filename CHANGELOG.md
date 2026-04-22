# Changelog

## [Unreleased] — 0.2.0

### Added
- **bKash (Tokenized Checkout) provider** — full surface: create/execute/status/search/refund, agreements (create/charge/cancel), authorize+capture+void, B2B payout init+execute.
- **SSLCommerz v4 provider** — full surface: hosted redirect + embedded popup, gateway-specific deep links, IPN with 3-layer defense (IP allowlist + MD5 signature + validator recheck), refund initiate + query, multi-currency (BDT/USD/EUR/GBP/INR/SGD/MYR), cart line items, EMI, `value_a..value_d` passthrough.
- **4 new capability contracts**: `SupportsAuthCapture`, `SupportsPayout`, `SupportsEmi`, `SupportsEmbeddedCheckout`.
- **2 new exception types**: `AlreadyCompletedException`, `IpNotAllowedException`.
- **3 new DTOs**: `LineItem`, `PayoutRequest`, `PayoutResponse`.
- **Extended DTOs**: `Customer` gains address fields; `PaymentRequest` gains `intent`, `productCategory`, `productName`, `productProfile`, `gateway`, `emiOption`, `emiMaxInstallments`, `lineItems`.
- **Agreement model** with `payify_agreements` migration, `charge()` and `cancel()` helpers.
- **Transaction model extensions**: `type` (`payment`|`payout`), `intent`, `agreement_id`, `authorized_at`, `captured_at`, `voided_at` columns; `markAuthorized`/`markCaptured`/`markVoided` helpers; `agreement()` relation.
- **2 new builders**: `AgreementBuilder` (create/charge/cancel), `PayoutBuilder` (send/initiate/execute).
- **8 new events**: `PaymentAuthorized`, `PaymentCaptured`, `PaymentVoided`, `AgreementCreated`, `AgreementCancelled`, `PayoutInitiated`, `PayoutSucceeded`, `PayoutFailed`.
- **6 new Artisan commands**: `payify:capture`, `payify:void`, `payify:agreement:list`, `payify:agreement:cancel`, `payify:payout`, `payify:refund:status`.
- Fixture-based tests for all provider endpoints (bKash + SSLCommerz) using Guzzle `MockHandler`.

### Security
- Added `app_secret`, `store_passwd`, `id_token`, `refresh_token` to `payify.http.mask_keys` default masking list.
- IP allowlist (configurable) + MD5 signature verify + validator recheck on SSLCommerz IPN.
- Token refresh auto-retry (once) on bKash `2079 Invalid app Token`.

### Compatibility
- Fully additive. Phase 1 contracts, DTOs, events, migrations, and commands unchanged except `Customer` + `PaymentRequest` DTO extensions (additive, default-null — existing code continues to work).

## [0.1.0]

### Added
- Driver-based core framework: `PayifyManager`, `AbstractDriver`, `FakeDriver`.
- Unified DTOs: `PaymentRequest`, `PaymentResponse`, `RefundRequest`, `RefundResponse`, `StatusResponse`, `WebhookPayload`, `TokenResponse`, `Customer`.
- `Transaction` Eloquent model with UUID PK, polymorphic `payable`, soft deletes, lifecycle helpers.
- Guzzle-backed `PayifyHttpClient` with retry, logging, secret-masking middleware.
- Webhook + callback pipeline with queue support and Telescope-style route override.
- Lifecycle events and typed exception hierarchy.
- Artisan commands: install, make-driver, list, status, refund, webhook:replay, cleanup.
- Pest test harness with `Payify::fake()` + assertions.
