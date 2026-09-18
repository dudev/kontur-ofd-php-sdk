<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Tests\Http;

use Dudev\KonturOfdPhpSdk\Exception\AccessDeniedException;
use Dudev\KonturOfdPhpSdk\Exception\AuthenticationException;
use Dudev\KonturOfdPhpSdk\Exception\KonturOfdException;
use Dudev\KonturOfdPhpSdk\Exception\NotFoundException;
use Dudev\KonturOfdPhpSdk\Exception\ValidationException;
use Dudev\KonturOfdPhpSdk\Tests\Support\MakesTransport;
use PHPUnit\Framework\TestCase;

final class TransportTest extends TestCase
{
    use MakesTransport;

    public function testGetFromDataHostAddsAuthHeadersAndReturnsDecodedBody(): void
    {
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonResponse(200, ['id' => 'abc']));
        $transport = $this->makeTransport($mockClient, sid: 'my-sid');

        $result = $transport->getFromDataHost('/v2/organizations');

        self::assertSame(['id' => 'abc'], $result);
        $request = $this->lastRequest($mockClient);
        self::assertSame('auth.sid my-sid', $request->getHeaderLine('Authorization'));
        self::assertSame('test-api-key', $request->getHeaderLine('X-Kontur-Ofd-ApiKey'));
        self::assertSame('ofd-project.kontur.ru', $request->getUri()->getHost());
    }

    public function testGetFromDataHostWithoutSidThrowsBeforeSendingRequest(): void
    {
        $mockClient = $this->makeMockClient();
        $transport = $this->makeTransport($mockClient, sid: null);

        $this->expectException(AuthenticationException::class);
        $transport->getFromDataHost('/v2/organizations');
    }

    public function testPostToAuthHostDoesNotAddAuthHeaders(): void
    {
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonResponse(200, ['Sid' => 'new-sid']));
        $transport = $this->makeTransport($mockClient, sid: null);

        $result = $transport->postToAuthHost('/auth/authenticate-by-pass', 'secret', ['login' => 'a@b.c']);

        self::assertSame(['Sid' => 'new-sid'], $result);
        $request = $this->lastRequest($mockClient);
        self::assertFalse($request->hasHeader('Authorization'));
        self::assertFalse($request->hasHeader('X-Kontur-Ofd-ApiKey'));
        self::assertSame('api.kontur.ru', $request->getUri()->getHost());
        self::assertSame('login=a%40b.c', $request->getUri()->getQuery());
        self::assertSame('secret', (string) $request->getBody());
    }

    public function testQueryParametersAreAppendedCorrectly(): void
    {
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonResponse(200, []));
        $transport = $this->makeTransport($mockClient);

        $transport->getFromDataHost('/v2/organizations/x/cashboxes/y/documents/by-period', [
            'dateFrom' => '2026-01-01T00:00:00',
            'dateTo' => '2026-01-02T00:00:00',
        ]);

        $query = $this->lastRequest($mockClient)->getUri()->getQuery();
        self::assertStringContainsString('dateFrom=2026-01-01T00%3A00%3A00', $query);
        self::assertStringContainsString('dateTo=2026-01-02T00%3A00%3A00', $query);
    }

    /**
     * @return iterable<string, array{int, class-string<KonturOfdException>}>
     */
    public static function errorStatusProvider(): iterable
    {
        yield '400 -> ValidationException' => [400, ValidationException::class];
        yield '401 -> AuthenticationException' => [401, AuthenticationException::class];
        yield '403 -> AccessDeniedException' => [403, AccessDeniedException::class];
        yield '404 -> NotFoundException' => [404, NotFoundException::class];
        yield '500 -> generic KonturOfdException' => [500, KonturOfdException::class];
    }

    /** @param class-string<KonturOfdException> $expectedClass */
    #[\PHPUnit\Framework\Attributes\DataProvider('errorStatusProvider')]
    public function testErrorResponsesAreMappedToExceptions(int $status, string $expectedClass): void
    {
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonResponse($status, [
            'errorCodeId' => 'urn:error:test',
            'errorCode' => 999999,
            'moreInfo' => 'https://example.test/docs',
            'userMessage' => ['ru' => 'Тестовая ошибка', 'en' => 'Test error'],
        ]));
        $transport = $this->makeTransport($mockClient);

        try {
            $transport->getFromDataHost('/v2/organizations');
            self::fail('Expected exception was not thrown');
        } catch (KonturOfdException $e) {
            self::assertInstanceOf($expectedClass, $e);
            self::assertSame('urn:error:test', $e->errorCodeId);
            self::assertSame('https://example.test/docs', $e->moreInfo);
            self::assertSame('Тестовая ошибка', $e->getMessage());
            self::assertSame($status, $e->getHttpStatusCode());
        }
    }

    public function testErrorResponseWithoutUserMessageFallsBackToGenericMessage(): void
    {
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonResponse(403, ['errorCodeId' => 'urn:error:access:forbidden']));
        $transport = $this->makeTransport($mockClient);

        try {
            $transport->getFromDataHost('/v2/organizations');
            self::fail('Expected exception was not thrown');
        } catch (AccessDeniedException $e) {
            self::assertStringContainsString('403', $e->getMessage());
            self::assertStringContainsString('urn:error:access:forbidden', $e->getMessage());
        }
    }
}
