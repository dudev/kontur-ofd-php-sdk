<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Tests\Integration;

use Dudev\KonturOfdPhpSdk\Exception\AuthenticationException;

final class AuthIntegrationTest extends IntegrationTestCase
{
    public function testAuthenticateByPassReturnsUsableSid(): void
    {
        [$login, $password] = self::loginAndPassword();
        $client = self::anonymousClient();

        $sid = $client->auth()->authenticateByPass($login, $password)->sid;

        self::assertNotSame('', $sid);
        $client->withSid($sid);
        $client->organizations()->list();
    }

    public function testUnknownSidIsRejectedWithAuthenticationException(): void
    {
        $client = self::anonymousClient();
        $client->withSid('00000000000000000000000000000000000000000000000000000000');

        try {
            $client->organizations()->list();
            self::fail('Expected AuthenticationException for an unknown auth.sid.');
        } catch (AuthenticationException $e) {
            self::assertSame(401, $e->getHttpStatusCode());
            self::assertSame('urn:error:request-parameter:auth-sid:required:access-denied', $e->errorCodeId);
        }
    }
}
