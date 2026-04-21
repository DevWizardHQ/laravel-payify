<?php

namespace DevWizard\Payify\Testing;

use DevWizard\Payify\Drivers\FakeDriver;
use DevWizard\Payify\Managers\PayifyManager;

class PayifyFake
{
    use Assertions;

    /**
     * @param  array<string, array>  $cannedByProvider
     */
    public function __construct(private array $cannedByProvider = [])
    {
    }

    public static function install(array|string $providers = []): self
    {
        $canned = is_string($providers) ? [$providers => []] : $providers;
        $fake = new self($canned);

        app()->singleton('payify', function ($app) use ($fake) {
            return new FakePayifyManager($app, $fake);
        });

        // Force Laravel to re-resolve the singleton next time it is requested
        app()->forgetInstance('payify');

        // Clear the Facade's own resolved-instance cache so subsequent
        // Payify::driver() calls use the new FakePayifyManager.
        \DevWizard\Payify\Facades\Payify::clearResolvedInstance('payify');

        app()->alias('payify', PayifyManager::class);

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
            client: $this->container->make(\DevWizard\Payify\Http\PayifyHttpClient::class),
            config: $config,
            events: $this->container->make('events'),
            logger: $this->container->make('log')->getLogger(),
        );
    }
}
