<div align="center">
    <h1>Laravel Odoo</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/marshmallow/laravel-odoo"><img src="https://img.shields.io/packagist/v/marshmallow/laravel-odoo.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/marshmallow/laravel-odoo"><img src="https://img.shields.io/packagist/php-v/marshmallow/laravel-odoo.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/marshmallow/laravel-odoo"><img src="https://badge.laravel.cloud/badge/marshmallow/laravel-odoo?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/marshmallow/laravel-odoo/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/marshmallow/laravel-odoo/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/marshmallow/laravel-odoo"><img src="https://img.shields.io/packagist/dt/marshmallow/laravel-odoo.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Laravel client for the Odoo 19+ External JSON-2 API, with per-model resources for partners, products, invoices and more.

## Installation

You can install the package via Composer:

```bash
composer require marshmallow/laravel-odoo
```

You may publish all of the package's resources at once:

```bash
php artisan vendor:publish --tag="laravel-odoo"
```

Or, you may publish each resource individually:

### Publishing the Configuration File

```bash
php artisan vendor:publish --tag="laravel-odoo-config"
```

## Usage

<!-- Add a basic usage example here. -->

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Laravel Odoo! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Marshmallow](https://github.com/marshmallow)
- [All Contributors](../../contributors)

## License

Laravel Odoo is open-sourced software licensed under the [MIT license](LICENSE.md).
