# Laravel Payify

Driver-based payment gateway manager for Laravel. Ships contracts, unified transaction lifecycle, Guzzle-backed HTTP client, webhook + callback pipeline, and a test harness. Phase 2 adds the bKash (Tokenized Checkout) and SSLCommerz v4 providers with full capability surfaces: refunds, agreements, authorize/capture/void, B2B payouts, EMI, embedded popup, and 3-layer IPN defense.

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

## Providers

### bKash (Tokenized Checkout)

```php
$response = Payify::driver('bkash')
    ->amount(500, 'BDT')
    ->invoice('INV-2026-0001')
    ->customer(name: 'Iqbal', phone: '01700000000')
    ->callback(route('checkout.return'))
    ->pay();

return redirect($response->redirectUrl);
```

Authorize + capture:

```php
Payify::driver('bkash')
    ->amount(500)->invoice('AUTH-1')
    ->customer(phone: '01700000000')
    ->callback(route('checkout.return'))
    ->authorize();

// After user returns and transaction is in Processing+authorized state:
$driver->capture($transaction);      // or $driver->capture($transaction, 250) for partial
$driver->void($transaction);
```

Agreements (stored payer consent):

```php
// 1. Create agreement
Payify::driver('bkash')->agreement()
    ->payerReference('01700000000')
    ->callback(route('agreement.return'))
    ->create();

// 2. Later charges using stored agreement
Payify::driver('bkash')->agreement('AGR123')->charge(500, reference: 'INV-RE-1');

// 3. Cancel
Payify::driver('bkash')->agreement()->cancel('AGR123');
```

B2B payout:

```php
Payify::driver('bkash')->payout()
    ->amount(5000)->reference('PAYOUT-1')
    ->receiver('01712345678', name: 'Vendor Ltd')
    ->send();
```

**Env vars:**
- `BKASH_MODE` — `sandbox` or `live`
- `BKASH_APP_KEY`, `BKASH_APP_SECRET`, `BKASH_USERNAME`, `BKASH_PASSWORD`
- `BKASH_CACHE_STORE` — override token cache store (optional)

### SSLCommerz

```php
$response = Payify::driver('sslcommerz')
    ->amount(1000, 'BDT')
    ->invoice('INV-2026-0002')
    ->customer(name: 'Iqbal', email: 'a@b.com', phone: '01700000000')
    ->address(line1: '1 Main St', city: 'Dhaka', country: 'BD')
    ->productCategory('General')
    ->callback(route('checkout.return'))
    ->webhook(route('payify.webhook', ['provider' => 'sslcommerz']))
    ->pay();

return redirect($response->redirectUrl);
```

Cart line items + EMI:

```php
Payify::driver('sslcommerz')
    ->amount(15000)->invoice('INV-EMI-1')
    ->customer(/*...*/)->address(/*...*/)
    ->lineItems([
        ['name' => 'Phone', 'price' => 14000, 'quantity' => 1],
        ['name' => 'Case', 'price' => 1000, 'quantity' => 1],
    ])
    ->emi(true, maxInstallments: 12)
    ->callback(route('checkout.return'))
    ->pay();
```

Embedded popup checkout (returns attributes for your HTML element):

```blade
@php
    $driver = Payify::provider('sslcommerz');
    $attrs = $driver->embedAttributes($request);
@endphp

<button @foreach($attrs as $k => $v) {{ $k }}="{{ $v }}" @endforeach>Pay Now</button>
<script src="{{ $driver->embedScript() }}"></script>
```

**Env vars:**
- `SSLCOMMERZ_MODE` — `sandbox` or `live`
- `SSLCOMMERZ_STORE_ID`, `SSLCOMMERZ_STORE_PASSWD`
- `SSLCOMMERZ_VERIFY_IP`, `SSLCOMMERZ_VERIFY_SIGNATURE`, `SSLCOMMERZ_VERIFY_VALIDATOR` — defense layers (all default `true`)

**IPN security layers** (all enabled by default):
1. IP allowlist (SSLCommerz publishes source IPs).
2. `verify_sign` MD5 signature check.
3. Validator server recheck (confirms amount/status server-to-server).

If any layer fails, IPN is rejected with 400.

## Custom drivers

```bash
php artisan payify:make-driver Razorpay
```

Register the generated class in `config/payify.php` under `providers.razorpay`.

## Testing

```php
$fake = Payify::fake();
Payify::driver('bkash')->amount(100)->invoice('INV-1')->pay();
$fake->assertPaid(fn ($txn) => $txn->reference === 'INV-1');
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
| `payify:capture {id} [--amount=]` | Capture authorized transaction |
| `payify:void {id}` | Void authorized transaction |
| `payify:agreement:list [--provider=] [--status=]` | List agreements |
| `payify:agreement:cancel {agreement_id}` | Cancel agreement via provider |
| `payify:payout --amount= --receiver= --reference= [--provider=]` | Trigger a payout |
| `payify:refund:status {id}` | Query SSLCommerz refund status (for two-phase refunds) |

## Configuration

See `config/payify.php`. Key env vars: `PAYIFY_DEFAULT`, `PAYIFY_MODE`, `PAYIFY_CURRENCY`, `PAYIFY_LOG`, `PAYIFY_WEBHOOK_QUEUE`.

## Testing the package

```bash
composer test
composer analyse
```

## License

MIT.
