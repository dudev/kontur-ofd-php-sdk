# kontur-ofd-php-sdk

PHP SDK for the [Kontur.OFD API](https://docs-ofd-api.kontur.ru/) — organizations, cashboxes,
fiscal documents (receipts/BSO), auth.

Status: infrastructure only, no client code yet. See `docs/roadmap.md` for scope.

## Installation

```bash
composer require dudev/kontur-ofd-php-sdk
```

## Development

```bash
composer install
vendor/bin/phpstan analyse
vendor/bin/phpunit
```
