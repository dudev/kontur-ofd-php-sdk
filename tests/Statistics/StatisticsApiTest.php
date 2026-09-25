<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Tests\Statistics;

use Dudev\KonturOfdPhpSdk\Exception\AccessDeniedException;
use Dudev\KonturOfdPhpSdk\Statistics\StatisticsApi;
use Dudev\KonturOfdPhpSdk\Tests\Support\MakesTransport;
use PHPUnit\Framework\TestCase;

final class StatisticsApiTest extends TestCase
{
    use MakesTransport;

    public function testCashboxByDaysBuildsPathAndDateOnlyQuery(): void
    {
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonResponse(200, ['items' => []]));
        $api = new StatisticsApi($this->makeTransport($mockClient));

        $api->cashboxByDays(
            'org-id',
            'kkt-id',
            new \DateTimeImmutable('2019-11-05 13:45:00'),
            new \DateTimeImmutable('2019-11-06 23:59:59'),
        );

        $uri = $this->lastRequest($mockClient)->getUri();
        self::assertSame('/v2/organizations/org-id/cashboxes/kkt-id/statistics/cash-receipt/by-days', $uri->getPath());
        parse_str($uri->getQuery(), $query);
        self::assertSame(['from' => '2019-11-05', 'to' => '2019-11-06'], $query);
    }

    public function testCashboxByDaysParsesDocumentedResponse(): void
    {
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonResponse(200, [
            'items' => [
                [
                    'date' => '2019-11-05',
                    'buy' => self::operation(606296, 360542, 245754, 14, 101051),
                    'returnBuy' => self::operation(169522, 132320, 37202, 4, 28253),
                    'sell' => self::operation(6179383, 3316499, 2862884, 166, 1029913),
                    'returnSell' => self::operation(586075, 171692, 414383, 16, 97681),
                ],
                [
                    'date' => '2019-11-06',
                    'buy' => self::operation(729972, 402772, 327200, 18, 121665),
                    'returnBuy' => self::operation(222063, 92343, 129720, 4, 37010),
                    'sell' => self::operation(6374096, 3010182, 3363914, 160, 1062367),
                    'returnSell' => self::operation(660433, 357152, 303281, 17, 110074),
                ],
            ],
        ]));
        $api = new StatisticsApi($this->makeTransport($mockClient));

        $days = $api->cashboxByDays('org-id', 'kkt-id', new \DateTimeImmutable(), new \DateTimeImmutable());

        self::assertCount(2, $days);
        self::assertSame('2019-11-05', $days[0]->date);
        self::assertSame(6179383, $days[0]->sell->totalKopeks);
        self::assertSame(3316499, $days[0]->sell->cashTotalKopeks);
        self::assertSame(2862884, $days[0]->sell->cashlessTotalKopeks);
        self::assertSame(166, $days[0]->sell->count);
        self::assertSame(1029913, $days[0]->sell->ndsBreakdown['rate20']);
        self::assertSame(0, $days[0]->sell->ndsBreakdown['calculatedWithRate10']);
        self::assertSame(0, $days[0]->sell->ndsBreakdown['rate22']);
        self::assertCount(12, $days[0]->sell->ndsBreakdown);
        self::assertSame(97681, $days[0]->returnSell->ndsBreakdown['rate20']);
        self::assertSame(14, $days[0]->buy->count);
        self::assertSame(169522, $days[0]->returnBuy->totalKopeks);
        self::assertSame('2019-11-06', $days[1]->date);
        self::assertSame(17, $days[1]->returnSell->count);
    }

    public function testNdsBreakdownKeepsRatesUnknownToSdk(): void
    {
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonResponse(200, [
            'items' => [
                ['date' => '2026-01-10', 'sell' => ['total' => 12200, 'nds' => ['rate25' => 2200, 'rate5' => 0]]],
            ],
        ]));
        $api = new StatisticsApi($this->makeTransport($mockClient));

        $days = $api->cashboxByDays('org-id', 'kkt-id', new \DateTimeImmutable(), new \DateTimeImmutable());

        self::assertSame(['rate25' => 2200, 'rate5' => 0], $days[0]->sell->ndsBreakdown);
    }

    public function testMissingOperationBlocksBecomeZeros(): void
    {
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonResponse(200, ['items' => [['date' => '2019-11-05']]]));
        $api = new StatisticsApi($this->makeTransport($mockClient));

        $days = $api->cashboxByDays('org-id', 'kkt-id', new \DateTimeImmutable(), new \DateTimeImmutable());

        self::assertSame(0, $days[0]->sell->totalKopeks);
        self::assertSame(0, $days[0]->returnBuy->count);
        self::assertSame([], $days[0]->buy->ndsBreakdown);
    }

    public function testEmptyPeriodReturnsEmptyList(): void
    {
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonResponse(200, ['items' => []]));
        $mockClient->addResponse($this->jsonResponse(200, ['shifts' => []]));
        $api = new StatisticsApi($this->makeTransport($mockClient));
        $from = new \DateTimeImmutable();
        $to = new \DateTimeImmutable();

        self::assertSame([], $api->cashboxByDays('org-id', 'kkt-id', $from, $to));
        self::assertSame([], $api->cashboxByShifts('org-id', 'kkt-id', $from, $to));
    }

    public function testCashboxByShiftsParsesShifts(): void
    {
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonResponse(200, [
            'shifts' => [
                [
                    'shiftNumber' => 103,
                    'shiftOpen' => '2019-11-19T10:00:00',
                    'shiftClose' => '2019-11-19T16:00:01',
                    'sell' => self::operation(6179383, 3316499, 2862884, 166, 1029913),
                ],
                [
                    'shiftNumber' => 104,
                    'shiftOpen' => '2019-11-19T16:01:00',
                    'sell' => self::operation(6374096, 3010182, 3363914, 160, 1062367),
                ],
            ],
        ]));
        $api = new StatisticsApi($this->makeTransport($mockClient));

        $shifts = $api->cashboxByShifts(
            'org-id',
            'kkt-id',
            new \DateTimeImmutable('2019-11-19'),
            new \DateTimeImmutable('2019-11-19'),
        );

        $uri = $this->lastRequest($mockClient)->getUri();
        self::assertSame(
            '/v2/organizations/org-id/cashboxes/kkt-id/statistics/cash-receipt/by-shifts',
            $uri->getPath(),
        );
        self::assertSame('from=2019-11-19&to=2019-11-19', $uri->getQuery());
        self::assertCount(2, $shifts);
        self::assertSame(103, $shifts[0]->shiftNumber);
        self::assertSame('2019-11-19T10:00:00', $shifts[0]->shiftOpen);
        self::assertSame('2019-11-19T16:00:01', $shifts[0]->shiftClose);
        self::assertSame(166, $shifts[0]->sell->count);
        self::assertSame(104, $shifts[1]->shiftNumber);
        self::assertNull($shifts[1]->shiftClose);
        self::assertSame(0, $shifts[1]->returnSell->totalKopeks);
    }

    public function testOrganizationByDaysBuildsPathAndParsesDays(): void
    {
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonResponse(200, [
            'items' => [
                ['date' => '2019-11-05', 'sell' => self::operation(6179383, 3316499, 2862884, 166, 1029913)],
            ],
        ]));
        $api = new StatisticsApi($this->makeTransport($mockClient));

        $days = $api->organizationByDays(
            'org-id',
            new \DateTimeImmutable('2019-01-01'),
            new \DateTimeImmutable('2019-03-01'),
        );

        $uri = $this->lastRequest($mockClient)->getUri();
        self::assertSame('/v2/organizations/org-id/statistics/cash-receipt/by-days', $uri->getPath());
        self::assertSame('from=2019-01-01&to=2019-03-01', $uri->getQuery());
        self::assertCount(1, $days);
        self::assertSame(6179383, $days[0]->sell->totalKopeks);
    }

    public function testOrganizationByDaysWithoutFullOrganizationAccessThrowsAccessDenied(): void
    {
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonResponse(403, ['errorCodeId' => 'urn:error:access:forbidden']));
        $api = new StatisticsApi($this->makeTransport($mockClient));

        $this->expectException(AccessDeniedException::class);

        $api->organizationByDays('org-id', new \DateTimeImmutable(), new \DateTimeImmutable());
    }

    /** @return array<string, mixed> */
    private static function operation(int $total, int $cash, int $cashless, int $count, int $rate20): array
    {
        return [
            'cashlessTotal' => $cashless,
            'cashTotal' => $cash,
            'total' => $total,
            'totalWithNds0' => 0,
            'totalWithNdsFree' => 0,
            'count' => $count,
            'nds' => [
                'rate10' => 0,
                'calculatedWithRate10' => 0,
                'rate18' => 0,
                'calculatedWithRate18' => 0,
                'rate20' => $rate20,
                'calculatedWithRate20' => 0,
                'rate22' => 0,
                'calculatedWithRate22' => 0,
                'rate5' => 0,
                'calculatedWithRate5' => 0,
                'rate7' => 0,
                'calculatedWithRate7' => 0,
            ],
        ];
    }
}
