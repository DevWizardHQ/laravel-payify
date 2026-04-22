<?php

namespace DevWizard\Payify\Commands;

use DevWizard\Payify\Models\Agreement;
use Illuminate\Console\Command;

class AgreementCancelCommand extends Command
{
    protected $signature = 'payify:agreement:cancel {agreement_id}';

    protected $description = 'Cancel a Payify agreement via its provider';

    public function handle(): int
    {
        $agreement = Agreement::where('agreement_id', $this->argument('agreement_id'))->first();

        if (! $agreement) {
            $this->error('Agreement not found.');

            return self::FAILURE;
        }

        $ok = $agreement->cancel();

        $this->info($ok ? "Cancelled {$agreement->agreement_id}." : 'Cancel failed.');

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
