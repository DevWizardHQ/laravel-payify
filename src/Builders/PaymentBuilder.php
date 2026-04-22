<?php

namespace DevWizard\Payify\Builders;

use DevWizard\Payify\Contracts\PaymentProvider;
use DevWizard\Payify\Contracts\SupportsAuthCapture;
use DevWizard\Payify\Contracts\SupportsPayout;
use DevWizard\Payify\Contracts\SupportsRefund;
use DevWizard\Payify\Contracts\SupportsTokenization;
use DevWizard\Payify\Dto\LineItem;
use DevWizard\Payify\Dto\PaymentRequest;
use DevWizard\Payify\Dto\PaymentResponse;
use DevWizard\Payify\Dto\RefundRequest;
use DevWizard\Payify\Dto\RefundResponse;
use DevWizard\Payify\Dto\StatusResponse;
use DevWizard\Payify\Exceptions\UnsupportedOperationException;
use DevWizard\Payify\Models\Transaction;
use DevWizard\Payify\Support\ReferenceGenerator;
use Illuminate\Database\Eloquent\Model;

class PaymentBuilder
{
    private array $state = [];

    public function __construct(public readonly PaymentProvider $driver) {}

    public function amount(float $amount, ?string $currency = null): self
    {
        $this->state['amount'] = $amount;
        if ($currency) {
            $this->state['currency'] = $currency;
        }

        return $this;
    }

    public function currency(string $currency): self
    {
        $this->state['currency'] = $currency;

        return $this;
    }

    public function invoice(string $reference): self
    {
        $this->state['reference'] = $reference;

        return $this;
    }

    public function reference(string $reference): self
    {
        return $this->invoice($reference);
    }

    public function customer(?string $name = null, ?string $email = null, ?string $phone = null, array $meta = []): self
    {
        $this->state['customer'] = array_filter([
            'name' => $name, 'email' => $email, 'phone' => $phone,
        ], fn ($v) => $v !== null) + ['metadata' => $meta];

        return $this;
    }

    public function address(?string $line1 = null, ?string $line2 = null, ?string $city = null, ?string $state = null, ?string $postcode = null, ?string $country = null): self
    {
        $existing = $this->state['customer'] ?? [];
        $this->state['customer'] = array_merge($existing, array_filter([
            'address1' => $line1, 'address2' => $line2,
            'city' => $city, 'state' => $state,
            'postcode' => $postcode, 'country' => $country,
        ], fn ($v) => $v !== null));

        return $this;
    }

    public function callback(string $url): self
    {
        $this->state['callback'] = $url;

        return $this;
    }

    public function webhook(string $url): self
    {
        $this->state['webhook'] = $url;

        return $this;
    }

    public function payable(Model $model): self
    {
        $this->state['payable'] = $model;

        return $this;
    }

    public function metadata(array $data): self
    {
        $this->state['metadata'] = $data;

        return $this;
    }

    public function mode(string $mode): self
    {
        $this->state['mode'] = $mode;

        return $this;
    }

    public function intent(string $intent): self
    {
        $this->state['intent'] = $intent;

        return $this;
    }

    public function productCategory(string $category): self
    {
        $this->state['productCategory'] = $category;

        return $this;
    }

    public function productName(string $name): self
    {
        $this->state['productName'] = $name;

        return $this;
    }

    public function productProfile(string $profile): self
    {
        $this->state['productProfile'] = $profile;

        return $this;
    }

    public function gateway(string $method): self
    {
        $this->state['gateway'] = $method;

        return $this;
    }

    public function emi(bool $enabled = true, ?int $maxInstallments = null): self
    {
        $this->state['emiOption'] = $enabled ? '1' : '0';
        if ($maxInstallments !== null) {
            $this->state['emiMaxInstallments'] = $maxInstallments;
        }

        return $this;
    }

    public function lineItems(array $items): self
    {
        $mapped = [];
        foreach ($items as $item) {
            $mapped[] = $item instanceof LineItem ? $item : LineItem::fromArray($item);
        }
        $this->state['lineItems'] = $mapped;

        return $this;
    }

    public function with(array $extras): self
    {
        $this->state['extras'] = array_merge($this->state['extras'] ?? [], $extras);

        return $this;
    }

    public function pay(array $overrides = []): PaymentResponse
    {
        $data = $this->merged($overrides);
        $data['currency'] ??= config('payify.default_currency', 'BDT');
        $data['reference'] ??= ReferenceGenerator::make();

        return $this->driver->pay(PaymentRequest::fromArray($data));
    }

    public function authorize(array $overrides = []): PaymentResponse
    {
        if (! $this->driver instanceof SupportsAuthCapture) {
            throw new UnsupportedOperationException("Provider [{$this->driver->name()}] does not support authorize/capture.");
        }

        $data = $this->merged($overrides);
        $data['intent'] = 'authorization';
        $data['currency'] ??= config('payify.default_currency', 'BDT');
        $data['reference'] ??= ReferenceGenerator::make();

        return $this->driver->authorize(PaymentRequest::fromArray($data));
    }

    public function refund(array $overrides = []): RefundResponse
    {
        if (! $this->driver instanceof SupportsRefund) {
            throw new UnsupportedOperationException("Provider [{$this->driver->name()}] does not support refunds.");
        }

        $data = $this->merged($overrides);

        return $this->driver->refund(RefundRequest::fromArray($data));
    }

    public function status(array $overrides = []): StatusResponse
    {
        $data = $this->merged($overrides);

        $txn = null;
        if (! empty($data['transactionId']) || ! empty($data['transaction_id'])) {
            $txn = Transaction::findOrFail($data['transactionId'] ?? $data['transaction_id']);
        } elseif (! empty($data['reference'])) {
            $txn = Transaction::where('provider', $this->driver->name())
                ->where('reference', $data['reference'])
                ->firstOrFail();
        } else {
            throw new \InvalidArgumentException('status() requires a transactionId or reference.');
        }

        return $this->driver->status($txn);
    }

    public function agreement(?string $agreementId = null): AgreementBuilder
    {
        if (! $this->driver instanceof SupportsTokenization) {
            throw new UnsupportedOperationException("Provider [{$this->driver->name()}] does not support tokenization.");
        }

        return new AgreementBuilder($this->driver, $agreementId, $this->state);
    }

    public function payout(): PayoutBuilder
    {
        if (! $this->driver instanceof SupportsPayout) {
            throw new UnsupportedOperationException("Provider [{$this->driver->name()}] does not support payouts.");
        }

        return new PayoutBuilder($this->driver, $this->state);
    }

    private function merged(array $overrides): array
    {
        return array_replace($this->state, $overrides);
    }
}
