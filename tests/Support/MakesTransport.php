<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Tests\Support;

use Dudev\KonturOfdPhpSdk\Http\Transport;
use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/** Общая обвязка для тестов, которым нужен `Transport` поверх `Http\Mock\Client` (без сети). */
trait MakesTransport
{
    private function makeMockClient(): MockClient
    {
        return new MockClient();
    }

    private function makeTransport(MockClient $mockClient, ?string $sid = 'test-sid'): Transport
    {
        $factory = new Psr17Factory();

        return new Transport(
            authBaseUri: 'https://api.kontur.ru',
            dataBaseUri: 'https://ofd-project.kontur.ru:11002',
            apiKey: 'test-api-key',
            sid: $sid,
            httpClient: $mockClient,
            requestFactory: $factory,
            streamFactory: $factory,
        );
    }

    /** @param array<string, mixed> $body */
    private function jsonResponse(int $status, array $body): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($body, JSON_THROW_ON_ERROR));
    }

    /** @param list<array<string, mixed>> $body */
    private function jsonListResponse(int $status, array $body): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($body, JSON_THROW_ON_ERROR));
    }

    private function lastRequest(MockClient $mockClient): RequestInterface
    {
        $request = $mockClient->getLastRequest();
        self::assertNotNull($request);

        return $request;
    }
}
