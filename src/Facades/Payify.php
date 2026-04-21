<?php

namespace DevWizard\Payify\Facades;

use DevWizard\Payify\Managers\PayifyManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \DevWizard\Payify\Builders\PaymentBuilder driver(?string $name = null)
 * @method static \DevWizard\Payify\Contracts\PaymentProvider provider(string $name)
 * @method static \DevWizard\Payify\Dto\PaymentResponse pay(array $data = [])
 * @method static \DevWizard\Payify\Dto\RefundResponse refund(array $data = [])
 * @method static \DevWizard\Payify\Dto\StatusResponse status(array $data = [])
 * @method static \DevWizard\Payify\Managers\PayifyManager extend(string $name, \Closure $closure)
 * @method static \DevWizard\Payify\Testing\PayifyFake fake(array|string $providers = [])
 *
 * @see PayifyManager
 */
class Payify extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'payify';
    }
}
