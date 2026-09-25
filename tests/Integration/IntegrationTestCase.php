<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Tests\Integration;

use Dudev\KonturOfdPhpSdk\Cashbox\Cashbox;
use Dudev\KonturOfdPhpSdk\KonturOfdClient;
use Dudev\KonturOfdPhpSdk\Organization\Organization;
use PHPUnit\Framework\TestCase;

/**
 * Основа интеграционных тестов против настоящего API (по умолчанию — тестовая площадка,
 * `KonturOfdClient::TEST_DATA_BASE_URI`, там демо-организации с кассами, которые сами генерируют
 * документы). Реальный HTTP-клиент находит `php-http/discovery` (в `require-dev` — Guzzle).
 *
 * Креды — только из окружения, без них тесты помечаются пропущенными, а не падают:
 * - `KONTUR_OFD_API_KEY` — ключ интегратора, обязателен;
 * - `KONTUR_OFD_SID` — готовый `auth.sid`, либо `KONTUR_OFD_LOGIN` + `KONTUR_OFD_PASSWORD` —
 *   тогда `auth.sid` получается через `authenticateByPass()` один раз на весь прогон;
 * - `KONTUR_OFD_DATA_BASE_URI` — необязательно, другой хост данных (например, боевой).
 *
 * Все тесты только читают данные — ничего не создают и не меняют.
 */
abstract class IntegrationTestCase extends TestCase
{
    private static ?string $sid = null;

    protected static function apiKey(): string
    {
        $apiKey = self::env('KONTUR_OFD_API_KEY');
        if ($apiKey === null) {
            self::markTestSkipped('KONTUR_OFD_API_KEY is not set — integration tests need real credentials.');
        }

        return $apiKey;
    }

    protected static function dataBaseUri(): string
    {
        return self::env('KONTUR_OFD_DATA_BASE_URI') ?? KonturOfdClient::TEST_DATA_BASE_URI;
    }

    /** Клиент без `auth.sid` — для тестов самой аутентификации и ошибок. */
    protected static function anonymousClient(): KonturOfdClient
    {
        return new KonturOfdClient(apiKey: self::apiKey(), dataBaseUri: self::dataBaseUri());
    }

    /** Клиент с `auth.sid` из `KONTUR_OFD_SID` или полученным по логину/паролю. */
    protected static function client(): KonturOfdClient
    {
        $client = self::anonymousClient();
        $client->withSid(self::sid($client));

        return $client;
    }

    /** @return array{string, string} */
    protected static function loginAndPassword(): array
    {
        $login = self::env('KONTUR_OFD_LOGIN');
        $password = self::env('KONTUR_OFD_PASSWORD');
        if ($login === null || $password === null) {
            self::markTestSkipped('KONTUR_OFD_LOGIN/KONTUR_OFD_PASSWORD are not set.');
        }

        return [$login, $password];
    }

    protected static function firstOrganization(KonturOfdClient $client): Organization
    {
        $organizations = $client->organizations()->list();
        if ($organizations === []) {
            self::markTestSkipped('The integrator key has no organizations to test against.');
        }

        return $organizations[0];
    }

    protected static function firstCashbox(KonturOfdClient $client, Organization $organization): Cashbox
    {
        $cashboxes = $client->cashboxes()->list($organization->id);
        if ($cashboxes === []) {
            self::markTestSkipped(sprintf('Organization %s has no cashboxes available.', $organization->id));
        }

        return $cashboxes[0];
    }

    private static function sid(KonturOfdClient $client): string
    {
        if (self::$sid !== null) {
            return self::$sid;
        }

        $sid = self::env('KONTUR_OFD_SID');
        if ($sid === null) {
            [$login, $password] = self::loginAndPassword();
            $sid = $client->auth()->authenticateByPass($login, $password)->sid;
        }

        return self::$sid = $sid;
    }

    private static function env(string $name): ?string
    {
        $value = getenv($name);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
