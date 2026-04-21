# Laravel Payify

Driver-based payment gateway manager for Laravel. Ships contracts, unified transaction lifecycle, Guzzle-backed HTTP client, webhook + callback pipeline, and a test harness. Phase 1 ships the core framework — concrete providers (bKash, Nagad, SSLCommerz, ShurjoPay, AmarPay, Upay, PayStation, Walletmix, Stripe) land in later releases.

## Install

```bash
composer require devwizardhq/laravel-payify
php artisan payify:install
```

## Quick start

```php
use DevWizard\Payify\Facades\Payify;

$response = Payify::driver('bkash')
    ->amount(250, 'BDT')
    ->invoice('INV-2026-0001')
    ->customer(name: 'Iqbal', phone: '01700000000')
    ->callback('https://app.test/payment/return')
    ->payable($order)
    ->pay();

return redirect($response->redirectUrl);
```

Array shortcut:

```php
Payify::driver('bkash')->pay([
    'amount' => 250, 'currency' => 'BDT',
    'reference' => 'INV-2026-0001',
    'callback' => route('payment.return'),
]);
```

## Custom drivers

```bash
php artisan payify:make-driver Razorpay
```

Register the generated class in `config/payify.php` under `providers.razorpay`.

## Testing

```php
Payify::fake();
Payify::driver('bkash')->amount(100)->invoice('INV-1')->pay();
Payify::assertPaid(fn ($txn) => $txn->reference === 'INV-1');
```

## Commands

| Command | Purpose |
|---|---|
| `payify:install` | Publish config + migrations |
| `payify:list` | List registered providers |
| `payify:make-driver {Name}` | Scaffold a custom driver |
| `payify:status {id}` | Refresh status from provider |
| `payify:refund {id}` | Refund (full or partial) |
| `payify:webhook:replay {id}` | Replay stored webhook payload |
| `payify:cleanup` | Prune stale transactions |

## Configuration

See `config/payify.php`. Key env vars: `PAYIFY_DEFAULT`, `PAYIFY_MODE`, `PAYIFY_CURRENCY`, `PAYIFY_LOG`, `PAYIFY_WEBHOOK_QUEUE`.

## Testing the package

```bash
composer test
composer analyse
```

## License

MIT.
