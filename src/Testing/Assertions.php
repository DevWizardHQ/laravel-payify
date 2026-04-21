<?php

namespace DevWizard\Payify\Testing;

use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Models\Transaction;
use PHPUnit\Framework\Assert as PHPUnit;

trait Assertions
{
    public function assertPaid(?callable $filter = null): self
    {
        $matches = $this->paidTransactions($filter);

        PHPUnit::assertNotEmpty(
            $matches,
            'Expected at least one matching payment transaction, none found.'
        );

        return $this;
    }

    public function assertPaidCount(int $count): self
    {
        $actual = count($this->paidTransactions());

        PHPUnit::assertSame(
            $count,
            $actual,
            "Expected {$count} paid transactions, got {$actual}."
        );

        return $this;
    }

    public function assertRefunded(string $reference): self
    {
        $txn = Transaction::where('reference', $reference)->first();

        PHPUnit::assertNotNull($txn, "Transaction [{$reference}] not found.");
        PHPUnit::assertTrue(
            in_array($txn->status->value, ['refunded', 'partially_refunded'], true),
            "Transaction [{$reference}] was not refunded (status: {$txn->status->value})."
        );

        return $this;
    }

    public function assertWebhookReceived(string $provider): self
    {
        $found = Transaction::where('provider', $provider)
            ->whereNotNull('webhook_verified_at')
            ->exists();

        PHPUnit::assertTrue($found, "No verified webhooks recorded for provider [{$provider}].");

        return $this;
    }

    public function assertNothingPaid(): self
    {
        PHPUnit::assertEmpty(
            $this->paidTransactions(),
            'Expected no paid transactions, but some were recorded.'
        );

        return $this;
    }

    /** @return array<int, Transaction> */
    private function paidTransactions(?callable $filter = null): array
    {
        return Transaction::where('status', TransactionStatus::Succeeded->value)
            ->get()
            ->filter(fn (Transaction $t) => $filter === null || $filter($t))
            ->values()
            ->all();
    }
}
