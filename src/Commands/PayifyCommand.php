<?php

namespace DevWizard\Payify\Commands;

use Illuminate\Console\Command;

class PayifyCommand extends Command
{
    public $signature = 'laravel-payify';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
