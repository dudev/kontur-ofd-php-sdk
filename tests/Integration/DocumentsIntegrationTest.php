<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Tests\Integration;

use Dudev\KonturOfdPhpSdk\Document\DocumentType;
use Dudev\KonturOfdPhpSdk\Document\Receipt;
use Dudev\KonturOfdPhpSdk\Exception\ValidationException;

final class DocumentsIntegrationTest extends IntegrationTestCase
{
    /** Тестовые кассы генерируют документы сами, но не обязательно каждую минуту — окно с запасом. */
    private const PERIOD = '-3 days';

    public function testByPeriodRespectsLimitAndReturnsTypedReceipts(): void
    {
        $client = self::client();
        $organization = self::firstOrganization($client);
        $cashbox = self::firstCashbox($client, $organization);

        $page = $client->documents()->byPeriod(
            $organization->id,
            $cashbox->regNumber,
            new \DateTimeImmutable(self::PERIOD),
            new \DateTimeImmutable(),
            [DocumentType::Receipt],
            limit: 5,
        );

        self::assertLessThanOrEqual(5, count($page->documents));
        foreach ($page->documents as $document) {
            self::assertInstanceOf(Receipt::class, $document);
            self::assertSame(DocumentType::Receipt, $document->getType());
            self::assertSame($cashbox->regNumber, $document->kktRegId);
        }
    }

    public function testByPeriodAllWalksPastFirstPage(): void
    {
        $client = self::client();
        $organization = self::firstOrganization($client);
        $cashbox = self::firstCashbox($client, $organization);
        $dateFrom = new \DateTimeImmutable(self::PERIOD);
        $dateTo = new \DateTimeImmutable();

        $documents = $client->documents();

        $firstPage = $documents->byPeriod($organization->id, $cashbox->regNumber, $dateFrom, $dateTo, limit: 2);
        if (count($firstPage->documents) < 2) {
            self::markTestSkipped('Not enough documents in the period to check pagination.');
        }

        $count = 0;
        foreach ($documents->byPeriodAll($organization->id, $cashbox->regNumber, $dateFrom, $dateTo, limit: 2) as $_) {
            if (++$count > 2) {
                break;
            }
        }

        self::assertGreaterThan(2, $count, 'byPeriodAll() should continue past the first page of 2.');
    }

    public function testDocumentFromPeriodCanBeFetchedById(): void
    {
        $client = self::client();
        $organization = self::firstOrganization($client);
        $cashbox = self::firstCashbox($client, $organization);

        $page = $client->documents()->byPeriod(
            $organization->id,
            $cashbox->regNumber,
            new \DateTimeImmutable(self::PERIOD),
            new \DateTimeImmutable(),
            [DocumentType::Receipt],
            limit: 1,
        );
        $receipt = $page->documents[0] ?? null;
        if (!$receipt instanceof Receipt) {
            self::markTestSkipped('No receipts in the period.');
        }

        $fetched = $client->documents()->get($organization->id, $cashbox->regNumber, $receipt->id);

        self::assertInstanceOf(Receipt::class, $fetched);
        self::assertSame($receipt->id, $fetched->id);
        self::assertSame($receipt->fiscalSign, $fetched->fiscalSign);
        self::assertSame($receipt->totalKopeks, $fetched->totalKopeks);
    }

    public function testInvertedPeriodIsRejectedWithValidationException(): void
    {
        $client = self::client();
        $organization = self::firstOrganization($client);
        $cashbox = self::firstCashbox($client, $organization);

        $this->expectException(ValidationException::class);

        $client->documents()->byPeriod(
            $organization->id,
            $cashbox->regNumber,
            new \DateTimeImmutable(),
            new \DateTimeImmutable('-1 day'),
        );
    }
}
