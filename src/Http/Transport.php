<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Http;

use Dudev\KonturOfdPhpSdk\Exception\AccessDeniedException;
use Dudev\KonturOfdPhpSdk\Exception\AuthenticationException;
use Dudev\KonturOfdPhpSdk\Exception\KonturOfdException;
use Dudev\KonturOfdPhpSdk\Exception\NotFoundException;
use Dudev\KonturOfdPhpSdk\Exception\ValidationException;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Тонкий транспортный слой поверх PSR-18/PSR-17 — не привязан к конкретной HTTP-библиотеке,
 * реализацию находит через php-http/discovery, если не передана явно (см. docs/roadmap.md, M1).
 * Держит текущий `auth.sid` и ключ интегратора, добавляет их к запросам на data-хост — auth-хост
 * (`authenticate-by-pass`/`authenticate-by-cert`/`approve-cert`) их не требует.
 */
final class Transport
{
    private readonly ClientInterface $httpClient;
    private readonly RequestFactoryInterface $requestFactory;
    private readonly StreamFactoryInterface $streamFactory;

    public function __construct(
        private readonly string $authBaseUri,
        private readonly string $dataBaseUri,
        private readonly string $apiKey,
        private ?string $sid = null,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ) {
        $this->httpClient = $httpClient ?? Psr18ClientDiscovery::find();
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
    }

    public function setSid(string $sid): void
    {
        $this->sid = $sid;
    }

    public function getSid(): ?string
    {
        return $this->sid;
    }

    /**
     * @param array<string, string> $query
     * @return array<array-key, mixed>
     */
    public function getFromDataHost(string $path, array $query = []): array
    {
        return $this->request('GET', $this->dataBaseUri . $path, $query, requiresAuth: true);
    }

    /**
     * Запрос на хост аутентификации (`authenticate-by-pass`/`authenticate-by-cert`/
     * `approve-cert`) — точка входа всегда `https://api.kontur.ru`, не зависит от того, тестовая
     * или боевая площадка используется для данных (`Endpoints.rst`).
     *
     * @param array<string, string> $query
     * @return array<array-key, mixed>
     */
    public function postToAuthHost(string $path, string $body, array $query = []): array
    {
        return $this->request('POST', $this->authBaseUri . $path, $query, body: $body, requiresAuth: false);
    }

    /**
     * `approve-cert` отдаёт свою следующую ссылку целиком (`Link.Href`, уже абсолютный URL,
     * возможно, с версией API в пути) — идём по ней как есть, не собираем сами (см.
     * `Auth\AuthClient::authenticateByCert()`/`approveCert()`).
     *
     * @return array<array-key, mixed>
     */
    public function postToAbsoluteUrl(string $absoluteUrl, string $body): array
    {
        return $this->request('POST', $absoluteUrl, [], body: $body, requiresAuth: false);
    }

    /**
     * @param array<string, string> $query
     * @return array<array-key, mixed>
     */
    private function request(string $method, string $uri, array $query, ?string $body = null, bool $requiresAuth = true): array
    {
        if ($query !== []) {
            $uri .= (str_contains($uri, '?') ? '&' : '?') . http_build_query($query);
        }

        $request = $this->requestFactory
            ->createRequest($method, $uri)
            ->withHeader('Cache-Control', 'no-cache');

        if ($requiresAuth) {
            if ($this->sid === null) {
                throw new AuthenticationException(
                    'No auth.sid is set — authenticate first (AuthClient) or provide one via Transport::setSid()/KonturOfdClient::withSid().',
                );
            }

            $request = $request
                ->withHeader('Authorization', 'auth.sid ' . $this->sid)
                ->withHeader('X-Kontur-Ofd-ApiKey', $this->apiKey);
        }

        if ($body !== null) {
            $request = $request->withBody($this->streamFactory->createStream($body));
        }

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new KonturOfdException('HTTP transport error calling Kontur.OFD API: ' . $e->getMessage(), previous: $e);
        }

        return $this->parseResponse($response);
    }

    /** @return array<array-key, mixed> */
    private function parseResponse(ResponseInterface $response): array
    {
        $status = $response->getStatusCode();
        $rawBody = (string) $response->getBody();

        $decoded = $rawBody === '' ? [] : json_decode($rawBody, true);
        $decoded = is_array($decoded) ? $decoded : [];

        if ($status >= 200 && $status < 300) {
            return $decoded;
        }

        $this->throwForError($status, $decoded);
    }

    /**
     * @param array<mixed> $errorBody
     */
    private function throwForError(int $status, array $errorBody): never
    {
        $errorCodeId = is_string($errorBody['errorCodeId'] ?? null) ? $errorBody['errorCodeId'] : null;
        $moreInfo = is_string($errorBody['moreInfo'] ?? null) ? $errorBody['moreInfo'] : null;
        $userMessage = $errorBody['userMessage'] ?? null;
        $ruMessage = is_array($userMessage) && is_string($userMessage['ru'] ?? null) ? $userMessage['ru'] : null;

        $message = $ruMessage ?? sprintf(
            'Kontur.OFD API error, HTTP %d%s',
            $status,
            $errorCodeId !== null ? sprintf(' (%s)', $errorCodeId) : '',
        );

        $exceptionClass = match ($status) {
            400 => ValidationException::class,
            401 => AuthenticationException::class,
            403 => AccessDeniedException::class,
            404 => NotFoundException::class,
            default => KonturOfdException::class,
        };

        throw new $exceptionClass($message, $errorCodeId, $moreInfo, $status);
    }
}
