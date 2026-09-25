<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Tests\Integration;

use Dudev\KonturOfdPhpSdk\Exception\AccessDeniedException;
use Dudev\KonturOfdPhpSdk\Exception\NotFoundException;

final class OrganizationsAndCashboxesIntegrationTest extends IntegrationTestCase
{
    public function testOrganizationFromListCanBeFetchedById(): void
    {
        $client = self::client();
        $organization = self::firstOrganization($client);

        $fetched = $client->organizations()->get($organization->id);

        self::assertNotSame('', $organization->id);
        self::assertNotSame('', $organization->inn);
        self::assertSame($organization->id, $fetched->id);
        self::assertSame($organization->inn, $fetched->inn);
    }

    public function testCashboxFromListCanBeFetchedByRegNumber(): void
    {
        $client = self::client();
        $organization = self::firstOrganization($client);
        $cashbox = self::firstCashbox($client, $organization);

        $fetched = $client->cashboxes()->get($organization->id, $cashbox->regNumber);

        self::assertNotSame('', $cashbox->regNumber);
        self::assertSame($cashbox->regNumber, $fetched->regNumber);
        self::assertSame($cashbox->serialNumber, $fetched->serialNumber);
    }

    public function testUnknownOrganizationIsRejected(): void
    {
        $client = self::client();

        try {
            $client->organizations()->get('00000000-0000-0000-0000-000000000000');
            self::fail('Expected an access/not-found error for an unknown organization.');
        } catch (AccessDeniedException | NotFoundException $e) {
            self::assertContains($e->getHttpStatusCode(), [403, 404]);
            self::assertNotNull($e->errorCodeId);
        }
    }
}
