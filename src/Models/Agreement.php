<?php

namespace DevWizard\Payify\Models;

use DevWizard\Payify\Contracts\SupportsTokenization;
use DevWizard\Payify\Database\Factories\AgreementFactory;
use DevWizard\Payify\Dto\PaymentRequest;
use DevWizard\Payify\Dto\PaymentResponse;
use DevWizard\Payify\Exceptions\UnsupportedOperationException;
use DevWizard\Payify\Managers\PayifyManager;
use DevWizard\Payify\Support\ReferenceGenerator;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agreement extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'provider', 'agreement_id', 'payer_reference', 'payable_type', 'payable_id',
        'status', 'metadata', 'activated_at', 'cancelled_at', 'expires_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'activated_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function getTable()
    {
        return config('payify.agreements_table', 'payify_agreements');
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function charge(float $amount, ?string $reference = null, ?string $currency = null, array $extras = []): PaymentResponse
    {
        $driver = app(PayifyManager::class)->provider($this->provider);

        if (! $driver instanceof SupportsTokenization) {
            throw new UnsupportedOperationException("Provider [{$this->provider}] does not support tokenization.");
        }
        if (! $this->isActive()) {
            throw new \LogicException("Agreement [{$this->agreement_id}] is not active (status: {$this->status}).");
        }

        return $driver->chargeToken($this->agreement_id, new PaymentRequest(
            amount: $amount,
            currency: $currency ?? config('payify.default_currency', 'BDT'),
            reference: $reference ?? ReferenceGenerator::make(),
            extras: $extras,
        ));
    }

    public function cancel(): bool
    {
        $driver = app(PayifyManager::class)->provider($this->provider);

        if (! $driver instanceof SupportsTokenization) {
            throw new UnsupportedOperationException("Provider [{$this->provider}] does not support tokenization.");
        }

        return $driver->detokenize($this->agreement_id);
    }

    protected static function newFactory(): AgreementFactory
    {
        return AgreementFactory::new();
    }
}
