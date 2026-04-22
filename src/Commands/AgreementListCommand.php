<?php

namespace DevWizard\Payify\Commands;

use DevWizard\Payify\Models\Agreement;
use Illuminate\Console\Command;

class AgreementListCommand extends Command
{
    protected $signature = 'payify:agreement:list {--provider=} {--status=}';

    protected $description = 'List Payify agreements';

    public function handle(): int
    {
        $query = Agreement::query();

        if ($provider = $this->option('provider')) {
            $query->where('provider', $provider);
        }
        if ($status = $this->option('status')) {
            $query->where('status', $status);
        }

        $rows = $query->get()->map(fn (Agreement $a) => [
            $a->provider, $a->agreement_id, $a->payer_reference,
            $a->status, $a->activated_at?->toDateTimeString(),
        ])->all();

        if ($rows === []) {
            $this->info('No agreements found.');

            return self::SUCCESS;
        }

        $this->table(['Provider', 'Agreement ID', 'Payer', 'Status', 'Activated'], $rows);

        return self::SUCCESS;
    }
}
