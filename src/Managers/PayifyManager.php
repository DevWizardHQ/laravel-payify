<?php

namespace DevWizard\Payify\Managers;

use DevWizard\Payify\Contracts\PaymentProvider;
use DevWizard\Payify\Exceptions\ProviderNotFoundException;
use Illuminate\Support\Manager;
use Illuminate\Support\Str;

class PayifyManager extends Manager
{
    public function getDefaultDriver(): string
    {
        $default = $this->config->get('payify.default');

        if (! $default) {
            throw new ProviderNotFoundException('No default payify provider configured.');
        }

        return $default;
    }

    protected function createDriver($driver)
    {
        if (isset($this->customCreators[$driver])) {
            return $this->callCustomCreator($driver);
        }

        $config = $this->providerConfig($driver);

        if (isset($config['driver']) && class_exists($config['driver'])) {
            return $this->resolveClassDriver($config['driver'], $config);
        }

        $method = 'create'.Str::studly($driver).'Driver';
        if (method_exists($this, $method)) {
            return $this->{$method}($config);
        }

        throw new ProviderNotFoundException("No driver registered for [{$driver}].");
    }

    protected function callCustomCreator($driver)
    {
        $config = $this->providerConfig($driver);
        return $this->customCreators[$driver]($this->container, $config);
    }

    protected function resolveClassDriver(string $class, array $config): PaymentProvider
    {
        $instance = $this->container->make($class, [
            'config' => $config,
        ]);

        if (! $instance instanceof PaymentProvider) {
            throw new \LogicException("[{$class}] must implement PaymentProvider contract.");
        }

        return $instance;
    }

    protected function providerConfig(string $driver): array
    {
        $config = $this->config->get("payify.providers.{$driver}");

        if (! $config) {
            throw new ProviderNotFoundException("Provider [{$driver}] is not configured.");
        }

        $config['mode'] ??= $this->config->get('payify.mode', 'sandbox');

        return $config;
    }

    public function driver($driver = null): mixed
    {
        return new \DevWizard\Payify\Builders\PaymentBuilder($this->provider($driver));
    }

    public function provider(?string $name = null): PaymentProvider
    {
        return parent::driver($name);
    }
}
