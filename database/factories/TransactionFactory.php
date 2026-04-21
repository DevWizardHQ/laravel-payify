<?php

namespace DevWizard\Payify\Database\Factories;

use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'provider' => 'fake',
            'reference' => 'INV-'.$this->faker->unique()->numerify('########'),
            'amount' => $this->faker->randomFloat(2, 1, 1000),
            'currency' => 'BDT',
            'status' => TransactionStatus::Pending,
        ];
    }

    public function succeeded(): self
    {
        return $this->state([
            'status' => TransactionStatus::Succeeded,
            'paid_at' => now(),
            'provider_transaction_id' => 'pay_'.$this->faker->uuid(),
        ]);
    }

    public function failed(): self
    {
        return $this->state([
            'status' => TransactionStatus::Failed,
            'error_code' => 'E_TEST',
            'error_message' => 'test failure',
            'failed_at' => now(),
        ]);
    }
}
