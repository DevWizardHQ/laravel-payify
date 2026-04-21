# Driver-based payment gateway manager for Laravel supporting multiple providers, webhooks, and dynamic integration.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/devwizardhq/laravel-payify.svg?style=flat-square)](https://packagist.org/packages/devwizardhq/laravel-payify)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/devwizardhq/laravel-payify/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/devwizardhq/laravel-payify/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/devwizardhq/laravel-payify/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/devwizardhq/laravel-payify/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/devwizardhq/laravel-payify.svg?style=flat-square)](https://packagist.org/packages/devwizardhq/laravel-payify)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/laravel-payify.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/laravel-payify)

## Installation

You can install the package via composer:

```bash
composer require devwizardhq/laravel-payify
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-payify-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-payify-config"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [IQBAL HASAN](https://github.com/iqbalhasandev)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
