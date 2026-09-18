<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Tests\Auth;

use Dudev\KonturOfdPhpSdk\Auth\AuthClient;
use Dudev\KonturOfdPhpSdk\Tests\Support\MakesTransport;
use PHPUnit\Framework\TestCase;

final class AuthClientTest extends TestCase
{
    use MakesTransport;

    public function testAuthenticateByPassSendsLoginAsQueryAndPasswordAsRawBody(): void
    {
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonResponse(200, ['Sid' => 'AAAA']));
        $auth = new AuthClient($this->makeTransport($mockClient, sid: null));

        $result = $auth->authenticateByPass('testlogin@testDomain.net', 'myPassword');

        self::assertSame('AAAA', $result->sid);
        self::assertNull($result->refreshToken);
        $request = $this->lastRequest($mockClient);
        self::assertSame('POST', $request->getMethod());
        self::assertSame('/auth/authenticate-by-pass', $request->getUri()->getPath());
        self::assertSame('login=testlogin%40testDomain.net', $request->getUri()->getQuery());
        self::assertSame('myPassword', (string) $request->getBody());
    }

    public function testAuthenticateByCertReturnsEncryptedKeyAndApproveUrl(): void
    {
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonResponse(200, [
            'EncryptedKey' => 'MIIDsQ==',
            'Link' => [
                'Rel' => 'Send decrypted key to this link',
                'Href' => 'https://api.kontur.ru/auth/v5.9/approve-cert?thumbprint=ABC123',
            ],
        ]));
        $auth = new AuthClient($this->makeTransport($mockClient, sid: null));

        $result = $auth->authenticateByCert('base64cert');

        self::assertSame('MIIDsQ==', $result->encryptedKey);
        self::assertSame('https://api.kontur.ru/auth/v5.9/approve-cert?thumbprint=ABC123', $result->approveCertUrl);
        $request = $this->lastRequest($mockClient);
        self::assertSame('free=false', $request->getUri()->getQuery());
        self::assertSame('base64cert', (string) $request->getBody());
    }

    public function testApproveCertPostsToTheGivenUrlAsIs(): void
    {
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonResponse(200, ['Sid' => 'BBBB', 'RefreshToken' => 'RRRR']));
        $auth = new AuthClient($this->makeTransport($mockClient, sid: null));

        $result = $auth->approveCert('https://api.kontur.ru/auth/v5.9/approve-cert?thumbprint=ABC123', "\x01\x02\x03");

        self::assertSame('BBBB', $result->sid);
        self::assertSame('RRRR', $result->refreshToken);
        $request = $this->lastRequest($mockClient);
        self::assertSame('/auth/v5.9/approve-cert', $request->getUri()->getPath());
        self::assertSame('thumbprint=ABC123', $request->getUri()->getQuery());
        self::assertFalse($request->hasHeader('Authorization'));
    }

    public function testAuthenticateByPassThrowsOnUnexpectedResponseShape(): void
    {
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonResponse(200, ['NotSid' => 'oops']));
        $auth = new AuthClient($this->makeTransport($mockClient, sid: null));

        $this->expectException(\UnexpectedValueException::class);
        $auth->authenticateByPass('a@b.c', 'pw');
    }
}
