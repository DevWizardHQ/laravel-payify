<?php

namespace DevWizard\Payify\Database\Factories;

use DevWizard\Payify\Models\Agreement;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgreementFactory extends Factory
{
    protected $model = Agreement::class;

    public function definition(): array
    {
        return [
            'provider' => 'bkash',
            'agreement_id' => 'AGR-'.$this->faker->unique()->numerify('########'),
            'payer_reference' => '01'.$this->faker->numerify('#########'),
            'status' => 'active',
            'activated_at' => now(),
        ];
    }

    public function cancelled(): self
    {
        return $this->state(['status' => 'cancelled', 'cancelled_at' => now()]);
    }
}
