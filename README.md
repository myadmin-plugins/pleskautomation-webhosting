# MyAdmin PleskAutomation Webhosting Plugin

[![Tests](https://github.com/detain/myadmin-pleskautomation-webhosting/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-pleskautomation-webhosting/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-pleskautomation-webhosting/version)](https://packagist.org/packages/detain/myadmin-pleskautomation-webhosting)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-pleskautomation-webhosting/downloads)](https://packagist.org/packages/detain/myadmin-pleskautomation-webhosting)
[![License](https://poser.pugx.org/detain/myadmin-pleskautomation-webhosting/license)](https://packagist.org/packages/detain/myadmin-pleskautomation-webhosting)

A MyAdmin plugin that integrates Parallels Plesk Automation (PPA) for automated webhosting provisioning and lifecycle management. It provides XML-RPC connectivity to PPA management nodes, handling account creation, subscription activation, webspace provisioning, and service deactivation/termination through the Symfony EventDispatcher hook system.

## Features

- XML-RPC 2 connector for Parallels Plesk Automation management nodes
- Full webhosting lifecycle: activate, reactivate, deactivate, terminate
- Account and subscription management via PPA API
- Webspace creation with configurable resource templates
- Domain-to-account lookup and reverse mapping
- Structured exception hierarchy for PPA error handling
- Symfony EventDispatcher integration for MyAdmin hook system

## Requirements

- PHP 8.2 or higher
- ext-soap
- Symfony EventDispatcher 5.x, 6.x, or 7.x

## Installation

Install via Composer:

```sh
composer require detain/myadmin-pleskautomation-webhosting
```

## Usage

This package is a MyAdmin plugin that registers event hooks automatically. The `Plugin::getHooks()` method returns all registered event handlers:

- `webhosting.settings` - Plugin configuration in the admin panel
- `webhosting.activate` - Provision a new PPA webhosting account
- `webhosting.reactivate` - Re-enable a suspended subscription
- `webhosting.deactivate` - Suspend an active subscription
- `webhosting.terminate` - Permanently disable a subscription
- `function.requirements` - Register helper function autoloading

## Testing

```sh
composer install
vendor/bin/phpunit
```

## License

This package is licensed under the LGPL-2.1 license. See the [LICENSE](LICENSE) file for details.
