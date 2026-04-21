<?php

namespace DevWizard\Payify\Commands;

use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Console\Command;

class CleanupCommand extends Command
{
    protected $signature = 'payify:cleanup
        {--status=pending : Status to clean (pending|failed|expired)}
        {--before= : Days old threshold (overrides config default)}
        {--dry-run : Report rows without deleting}';

    protected $description = 'Soft-delete stale Payify transactions';

    public function handle(): int
    {
        $status = TransactionStatus::tryFrom((string) $this->option('status'));

        if (! $status) {
            $this->error('Invalid status.');
            return self::FAILURE;
        }

        $days = $this->option('before') !== null
            ? (int) $this->option('before')
            : (int) config(
                $status === TransactionStatus::Failed
                    ? 'payify.cleanup.failed_ttl_days'
                    : 'payify.cleanup.pending_ttl_days',
                30
            );

        $cutoff = now()->subDays($days);

        $query = Transaction::where('status', $status->value)
            ->where('created_at', '<', $cutoff);

        $count = $query->count();

        if ($this->option('dry-run')) {
            $this->info("Dry run: would delete {$count} {$status->value} transactions older than {$days} days.");
            return self::SUCCESS;
        }

        $query->chunkById(200, fn ($rows) => $rows->each->delete());

        $this->info("Deleted {$count} {$status->value} transactions older than {$days} days.");

        return self::SUCCESS;
    }
}
