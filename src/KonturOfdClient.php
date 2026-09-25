<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk;

use Dudev\KonturOfdPhpSdk\Auth\AuthClient;
use Dudev\KonturOfdPhpSdk\Cashbox\CashboxesApi;
use Dudev\KonturOfdPhpSdk\Document\DocumentsApi;
use Dudev\KonturOfdPhpSdk\Http\Transport;
use Dudev\KonturOfdPhpSdk\Organization\OrganizationsApi;
use Dudev\KonturOfdPhpSdk\Statistics\StatisticsApi;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Точка входа SDK — держит общее состояние (`auth.sid`, ключ интегратора) в `Transport` и раздаёт
 * `*Api`-объекты. Сознательно не решает, откуда взялся `Sid` (пароль на backend, пароль на фронте,
 * ЭП на фронте — см. `docs/roadmap.md`, M2) — это вопрос архитектуры вызывающего кода, не SDK.
 */
final class KonturOfdClient
{
    public const PROD_DATA_BASE_URI = 'https://ofd-api.kontur.ru';
    public const TEST_DATA_BASE_URI = 'https://ofd-project.kontur.ru:11002';
    public const AUTH_BASE_URI = 'https://api.kontur.ru';

    private readonly Transport $transport;
    private readonly AuthClient $auth;
    private readonly OrganizationsApi $organizations;
    private readonly CashboxesApi $cashboxes;
    private readonly DocumentsApi $documents;
    private readonly StatisticsApi $statistics;

    public function __construct(
        string $apiKey,
        string $dataBaseUri = self::PROD_DATA_BASE_URI,
        string $authBaseUri = self::AUTH_BASE_URI,
        ?string $sid = null,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ) {
        $this->transport = new Transport(
            $authBaseUri,
            $dataBaseUri,
            $apiKey,
            $sid,
            $httpClient,
            $requestFactory,
            $streamFactory,
        );
        $this->auth = new AuthClient($this->transport);
        $this->organizations = new OrganizationsApi($this->transport);
        $this->cashboxes = new CashboxesApi($this->transport);
        $this->documents = new DocumentsApi($this->transport);
        $this->statistics = new StatisticsApi($this->transport);
    }

    public function auth(): AuthClient
    {
        return $this->auth;
    }

    public function organizations(): OrganizationsApi
    {
        return $this->organizations;
    }

    public function cashboxes(): CashboxesApi
    {
        return $this->cashboxes;
    }

    public function documents(): DocumentsApi
    {
        return $this->documents;
    }

    public function statistics(): StatisticsApi
    {
        return $this->statistics;
    }

    /** Устанавливает `auth.sid`, полученный любым из трёх способов (`docs/roadmap.md`, M2) — SDK не знает и не должен знать, как именно он был получен. */
    public function withSid(string $sid): void
    {
        $this->transport->setSid($sid);
    }

    public function getSid(): ?string
    {
        return $this->transport->getSid();
    }
}
