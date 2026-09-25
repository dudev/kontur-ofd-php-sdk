<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Tests\Integration;

use Dudev\KonturOfdPhpSdk\Exception\AccessDeniedException;

final class StatisticsIntegrationTest extends IntegrationTestCase
{
    private const PERIOD = '-7 days';

    public function testCashboxByDaysStaysWithinRequestedPeriod(): void
    {
        $client = self::client();
        $organization = self::firstOrganization($client);
        $cashbox = self::firstCashbox($client, $organization);
        $dateFrom = new \DateTimeImmutable(self::PERIOD);
        $dateTo = new \DateTimeImmutable();

        $days = $client->statistics()->cashboxByDays($organization->id, $cashbox->regNumber, $dateFrom, $dateTo);

        foreach ($days as $day) {
            self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}/', $day->date);
            self::assertGreaterThanOrEqual($dateFrom->format('Y-m-d'), substr($day->date, 0, 10));
            self::assertLessThanOrEqual($dateTo->format('Y-m-d'), substr($day->date, 0, 10));
            self::assertGreaterThanOrEqual(0, $day->sell->count);
            self::assertGreaterThanOrEqual(0, $day->sell->totalKopeks);
        }
    }

    public function testCashboxByShiftsReturnsNumberedShifts(): void
    {
        $client = self::client();
        $organization = self::firstOrganization($client);
        $cashbox = self::firstCashbox($client, $organization);

        $shifts = $client->statistics()->cashboxByShifts(
            $organization->id,
            $cashbox->regNumber,
            new \DateTimeImmutable(self::PERIOD),
            new \DateTimeImmutable(),
        );

        foreach ($shifts as $shift) {
            self::assertGreaterThan(0, $shift->shiftNumber);
            self::assertGreaterThanOrEqual(0, $shift->sell->totalKopeks);
        }
    }

    public function testOrganizationByDaysOrAccessDenied(): void
    {
        $client = self::client();
        $organization = self::firstOrganization($client);

        try {
            $days = $client->statistics()->organizationByDays(
                $organization->id,
                new \DateTimeImmutable(self::PERIOD),
                new \DateTimeImmutable(),
            );
        } catch (AccessDeniedException) {
            self::markTestSkipped('The integrator key has no access to all cashboxes of the organization.');
        }

        foreach ($days as $day) {
            self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}/', $day->date);
        }
    }
}
