# kontur-ofd-php-sdk

PHP SDK for the [Kontur.OFD API](https://docs-ofd-api.kontur.ru/) — organizations, cashboxes,
fiscal documents (receipts/BSO), auth.

Status: M1–M4 implemented (transport, auth primitives, organizations, cashboxes, documents with
typed `Receipt`/generic fallback and generator-based pagination). Statistics (M5) not implemented
yet. See `docs/roadmap.md` for scope and open questions.

## Installation

```bash
composer require dudev/kontur-ofd-php-sdk
```

## Usage

```php
use Dudev\KonturOfdPhpSdk\KonturOfdClient;
use Dudev\KonturOfdPhpSdk\Document\DocumentType;

$client = new KonturOfdClient(apiKey: $integratorApiKey, dataBaseUri: KonturOfdClient::TEST_DATA_BASE_URI);
$sid = $client->auth()->authenticateByPass($login, $password)->sid; // or ->withSid($sid) if obtained elsewhere
$client->withSid($sid);

foreach ($client->organizations()->list() as $organization) {
    foreach ($client->cashboxes()->list($organization->id) as $cashbox) {
        foreach ($client->documents()->byPeriodAll(
            $organization->id,
            $cashbox->regNumber,
            new DateTimeImmutable('-1 day'),
            new DateTimeImmutable(),
            types: [DocumentType::Receipt, DocumentType::ReceiptCorrection],
        ) as $document) {
            // $document is a Receipt for receipt/bso types, GenericDocument otherwise
        }
    }
}
```

## Development

```bash
composer install
vendor/bin/phpstan analyse
vendor/bin/phpunit
```
