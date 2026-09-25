# kontur-ofd-php-sdk

PHP SDK for the [Kontur.OFD API](https://docs-ofd-api.kontur.ru/) — organizations, cashboxes,
fiscal documents (receipts/BSO), receipt statistics, auth.

Status: M1–M5 implemented (transport, auth primitives, organizations, cashboxes, documents with
typed `Receipt`/generic fallback and generator-based pagination, receipt statistics by days/shifts).
See `docs/roadmap.md` for scope and open questions.

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

// Aggregated receipt totals (kopeks) by day / by shift, dates inclusive
foreach ($client->statistics()->cashboxByDays($organizationId, $kktRegId, $from, $to) as $day) {
    echo $day->date, ': ', $day->sell->totalKopeks - $day->returnSell->totalKopeks, PHP_EOL;
}
```

## Development

```bash
composer install
vendor/bin/phpstan analyse
vendor/bin/phpcs
vendor/bin/phpunit                         # unit tests, no network
```

Integration tests call the real API (the Kontur.OFD test site by default) and are skipped unless
credentials are set:

```bash
export KONTUR_OFD_API_KEY=...              # integrator key
export KONTUR_OFD_LOGIN=... KONTUR_OFD_PASSWORD=...   # or KONTUR_OFD_SID=... instead
# export KONTUR_OFD_DATA_BASE_URI=...      # optional, defaults to https://ofd-project.kontur.ru:11002
vendor/bin/phpunit --testsuite integration --display-skipped
```

In GitHub Actions the same suite runs from the `Integration` workflow (manually or weekly) with
`KONTUR_OFD_API_KEY`/`KONTUR_OFD_LOGIN`/`KONTUR_OFD_PASSWORD` repository secrets and an optional
`KONTUR_OFD_DATA_BASE_URI` variable; without the secrets it does nothing.
