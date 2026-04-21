<?php

namespace DevWizard\Payify\Testing;

use DevWizard\Payify\Drivers\FakeDriver;
use DevWizard\Payify\Facades\Payify;
use DevWizard\Payify\Http\PayifyHttpClient;
use DevWizard\Payify\Managers\PayifyManager;

class PayifyFake
{
    use Assertions;

    /**
     * @param  array<string, array>  $cannedByProvider
     */
    public function __construct(private array $cannedByProvider = []) {}

    public static function install(array|string $providers = []): self
    {
        $canned = is_string($providers) ? [$providers => []] : $providers;
        $fake = new self($canned);

        app()->extend('payify', function ($manager, $app) use ($fake) {
            return new FakePayifyManager($app, $fake);
        });

        Payify::clearResolvedInstance('payify');

        return $fake;
    }

    public function cannedFor(string $provider): array
    {
        return $this->cannedByProvider[$provider] ?? [];
    }

    public function shouldFake(string $provider): bool
    {
        return $this->cannedByProvider === [] || isset($this->cannedByProvider[$provider]);
    }
}

/**
 * Internal manager subclass that coerces every driver resolution into a FakeDriver
 * while preserving custom canned responses per provider.
 *
 * @internal
 */
class FakePayifyManager extends PayifyManager
{
    public function __construct($container, private PayifyFake $fake)
    {
        parent::__construct($container);
    }

    protected function createDriver($driver)
    {
        if (! $this->fake->shouldFake($driver)) {
            return parent::createDriver($driver);
        }

        $config = [
            'mode' => 'sandbox',
            'credentials' => [],
            'name' => $driver,
            'canned' => $this->fake->cannedFor($driver),
        ];

        return new FakeDriver(
            client: $this->container->make(PayifyHttpClient::class),
            config: $config,
            events: $this->container->make('events'),
            logger: $this->container->make('log')->getLogger(),
        );
    }
}
