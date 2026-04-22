<?php

namespace DevWizard\Payify\Builders;

use DevWizard\Payify\Contracts\PaymentProvider;
use DevWizard\Payify\Contracts\SupportsRefund;
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

    private function merged(array $overrides): array
    {
        return array_replace($this->state, $overrides);
    }
}
