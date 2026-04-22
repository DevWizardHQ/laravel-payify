<?php

namespace DevWizard\Payify\Commands;

use DevWizard\Payify\Contracts\SupportsPayout;
use DevWizard\Payify\Dto\PayoutRequest;
use DevWizard\Payify\Managers\PayifyManager;
use DevWizard\Payify\Support\ReferenceGenerator;
use Illuminate\Console\Command;

class PayoutCommand extends Command
{
    protected $signature = 'payify:payout
        {--provider= : Provider name (defaults to payify.default)}
        {--amount= : Payout amount}
        {--currency= : Currency (defaults to payify.default_currency)}
        {--receiver= : Receiver identifier (MSISDN, account)}
        {--receiver-name= : Receiver name}
        {--reference= : Internal payout reference (auto-generated if omitted)}
        {--reason= : Reason text}';

    protected $description = 'Trigger a payout via the given provider';

    public function handle(PayifyManager $manager): int
    {
        $providerName = $this->option('provider') ?? config('payify.default');
        $driver = $manager->provider($providerName);

        if (! $driver instanceof SupportsPayout) {
            $this->error("Provider [{$providerName}] does not support payouts.");

            return self::FAILURE;
        }

        $amount = $this->option('amount');
        $receiver = $this->option('receiver');

        if ($amount === null || $receiver === null) {
            $this->error('--amount and --receiver are required.');

            return self::FAILURE;
        }

        $request = new PayoutRequest(
            reference: $this->option('reference') ?? ReferenceGenerator::make('PO'),
            amount: (float) $amount,
            currency: $this->option('currency') ?? config('payify.default_currency', 'BDT'),
            receiverIdentifier: (string) $receiver,
            receiverName: $this->option('receiver-name'),
            reason: $this->option('reason'),
        );

        $response = $driver->payout($request);

        $this->line("Payout ID: {$response->providerPayoutId}");
        $this->line("Status:    {$response->status->value}");
        $this->line("Amount:    {$response->amount} {$response->currency}");

        return self::SUCCESS;
    }
}
