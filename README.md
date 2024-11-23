# Settings Package for PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mbsoft31/settings.svg?style=flat-square)](https://packagist.org/packages/mbsoft31/settings)  
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/mbsoft31/settings/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mbsoft31/settings/actions?query=workflow%3Arun-tests+branch%3Amain)  
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/mbsoft31/settings/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/mbsoft31/settings/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)  
[![Total Downloads](https://img.shields.io/packagist/dt/mbsoft31/settings.svg?style=flat-square)](https://packagist.org/packages/mbsoft31/settings)


## Introduction

This package provides a simple and extensible way to manage application settings in various formats like JSON and YAML. It's designed to work seamlessly with Laravel and allows saving, loading, and managing configuration settings easily.

## Features

- Save and load application settings in JSON or YAML formats.
- Easily extendable for additional formats.
- Works out of the box with Laravel.
- Configurable storage paths.

---

## Installation

### Step 1: Install via Composer

You can install the package using Composer:

```bash
composer require mbsoft31/settings
```

### Step 2: Publish Configuration & Migrations

Publish the migrations and configuration files:

```bash
php artisan vendor:publish --tag="settings-migrations"
php artisan migrate

php artisan vendor:publish --tag="settings-config"
```

The configuration file (`config/settings.php`) will look like this:

```php
return [
    // Define default paths or custom settings here
];
```

Optionally, you can publish views using:

```bash
php artisan vendor:publish --tag="settings-views"
```

---

## Usage

### Example: Save and Load JSON Settings

Here's a quick example of how to use the package to manage your application settings:

```php
use MBsoft\Settings;

// Initialize settings
$settings = new Settings([
    'app.name' => 'My Application',
    'app.env' => 'local',
]);

// Save settings to a file
$settings->saveToFile(storage_path('settings.json'), \MBsoft\Settings\ConfigFormat::JSON);

// Load settings from a file
$loadedSettings = Settings::loadFromFile(storage_path('settings.json'), \MBsoft\Settings\ConfigFormat::JSON);

echo $loadedSettings->get('app.name'); // Outputs: "My Application"
```

---

## Testing

Run the test suite using the following command:

```bash
composer test
```

To ensure the directory structure for file-based tests is correctly created during CI or local development, the package includes setup logic in the tests.

---

## Advanced Usage

### Supported Formats

Currently, the package supports:

- JSON
- YAML

You can extend the package to support additional formats by adding new serialization and deserialization methods.

### Custom Configuration

You can modify the `settings.php` config file to define default file paths or application-specific settings.

---

## Contributing

We welcome contributions! Please see the [CONTRIBUTING](CONTRIBUTING.md) guide for details.

---

## Security Vulnerabilities

If you discover any security-related issues, please report them via the [Security Policy](../../security/policy).

---

## Changelog

Refer to the [CHANGELOG](CHANGELOG.md) for recent updates.

---

## Credits

- [Mouadh Bekhouche](https://github.com/mbsoft31)
- [All Contributors](../../contributors)

---

## License

This package is open-source software licensed under the [MIT License](LICENSE.md).
