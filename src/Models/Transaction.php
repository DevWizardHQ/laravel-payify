<?php

namespace DevWizard\Payify\Models;

use DevWizard\Payify\Database\Factories\TransactionFactory;
use DevWizard\Payify\Dto\StatusResponse;
use DevWizard\Payify\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Transaction extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'payify_transactions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'provider', 'type', 'intent',
        'provider_transaction_id', 'agreement_id', 'reference', 'amount', 'currency',
        'status', 'customer', 'metadata', 'request_payload', 'response_payload',
        'error_code', 'error_message', 'refunded_amount', 'webhook_payload',
        'webhook_verified_at', 'paid_at', 'failed_at', 'refunded_at', 'expires_at',
        'authorized_at', 'captured_at', 'voided_at',
        'payable_type', 'payable_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'status' => TransactionStatus::class,
        'customer' => 'array',
        'metadata' => 'array',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'webhook_payload' => 'array',
        'webhook_verified_at' => 'datetime',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
        'expires_at' => 'datetime',
        'authorized_at' => 'datetime',
        'captured_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function getTable()
    {
        return config('payify.table', 'payify_transactions');
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function agreement(): HasOne
    {
        return $this->hasOne(Agreement::class, 'agreement_id', 'agreement_id')
            ->where('payify_agreements.provider', $this->provider);
    }

    public function markSucceeded(?string $providerTxnId = null, array $raw = []): void
    {
        $this->update([
            'status' => TransactionStatus::Succeeded,
            'provider_transaction_id' => $providerTxnId ?? $this->provider_transaction_id,
            'response_payload' => $raw ?: $this->response_payload,
            'paid_at' => now(),
        ]);
    }

    public function markFailed(string $code, string $message, array $raw = []): void
    {
        $this->update([
            'status' => TransactionStatus::Failed,
            'error_code' => $code,
            'error_message' => $message,
            'response_payload' => $raw ?: $this->response_payload,
            'failed_at' => now(),
        ]);
    }

    public function markCancelled(array $raw = []): void
    {
        $this->update([
            'status' => TransactionStatus::Cancelled,
            'response_payload' => $raw ?: $this->response_payload,
        ]);
    }

    public function markRefunded(float $amount, array $raw = []): void
    {
        DB::transaction(function () use ($amount, $raw) {
            /** @var static $fresh */
            $fresh = static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            $newTotal = (float) $fresh->refunded_amount + $amount;
            $status = $newTotal >= (float) $fresh->amount
                ? TransactionStatus::Refunded
                : TransactionStatus::PartiallyRefunded;

            $fresh->update([
                'status' => $status,
                'refunded_amount' => $newTotal,
                'response_payload' => $raw ?: $fresh->response_payload,
                'refunded_at' => now(),
            ]);
        });

        $this->refresh();
    }

    public function markAuthorized(?string $providerTxnId = null, array $raw = []): void
    {
        $this->update([
            'status' => TransactionStatus::Processing,
            'provider_transaction_id' => $providerTxnId ?? $this->provider_transaction_id,
            'response_payload' => $raw ?: $this->response_payload,
            'authorized_at' => now(),
        ]);
    }

    public function markCaptured(?float $capturedAmount = null, array $raw = []): void
    {
        $this->update([
            'status' => TransactionStatus::Succeeded,
            'response_payload' => $raw ?: $this->response_payload,
            'captured_at' => now(),
            'paid_at' => now(),
            'amount' => $capturedAmount ?? $this->amount,
        ]);
    }

    public function markVoided(array $raw = []): void
    {
        $this->update([
            'status' => TransactionStatus::Cancelled,
            'response_payload' => $raw ?: $this->response_payload,
            'voided_at' => now(),
        ]);
    }

    public function refreshFromStatus(StatusResponse $status): void
    {
        $this->update([
            'status' => $status->status,
            'provider_transaction_id' => $status->providerTransactionId ?? $this->provider_transaction_id,
            'refunded_amount' => $status->refundedAmount ?? $this->refunded_amount,
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === TransactionStatus::Pending;
    }

    public function isSucceeded(): bool
    {
        return $this->status === TransactionStatus::Succeeded;
    }

    public function canRefund(): bool
    {
        return $this->status->canRefund();
    }

    public function remainingRefundable(): float
    {
        return (float) $this->amount - (float) $this->refunded_amount;
    }

    protected static function newFactory(): TransactionFactory
    {
        return TransactionFactory::new();
    }
}
