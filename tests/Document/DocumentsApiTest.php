<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Tests\Document;

use Dudev\KonturOfdPhpSdk\Document\DocumentsApi;
use Dudev\KonturOfdPhpSdk\Document\DocumentType;
use Dudev\KonturOfdPhpSdk\Tests\Support\MakesTransport;
use PHPUnit\Framework\TestCase;

final class DocumentsApiTest extends TestCase
{
    use MakesTransport;

    /** @return array<string, array<string, mixed>> */
    private function openShiftDocument(int $shiftNumber): array
    {
        return ['openShift' => ['shiftNumber' => $shiftNumber, 'id' => (string) $shiftNumber]];
    }

    public function testByPeriodBuildsQueryAndParsesPage(): void
    {
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonResponse(200, [
            'documents' => [$this->openShiftDocument(1)],
            'paging' => ['nextOffset' => 'CURSOR-1'],
        ]));
        $api = new DocumentsApi($this->makeTransport($mockClient));

        $page = $api->byPeriod(
            'org-id',
            'kkt-id',
            new \DateTimeImmutable('2026-01-01T00:00:00'),
            new \DateTimeImmutable('2026-01-02T00:00:00'),
            [DocumentType::Receipt, DocumentType::ReceiptCorrection],
            offset: 'PREV',
            limit: 500,
        );

        self::assertCount(1, $page->documents);
        self::assertSame('CURSOR-1', $page->nextOffset);
        $query = $this->lastRequest($mockClient)->getUri()->getQuery();
        self::assertStringContainsString('types=receipt%2CreceiptCorrection', $query);
        self::assertStringContainsString('offset=PREV', $query);
        self::assertStringContainsString('limit=500', $query);
    }

    public function testByPeriodWithoutPagingReturnsNullNextOffset(): void
    {
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonResponse(200, ['documents' => []]));
        $api = new DocumentsApi($this->makeTransport($mockClient));

        $page = $api->byPeriod('org-id', 'kkt-id', new \DateTimeImmutable(), new \DateTimeImmutable());

        self::assertNull($page->nextOffset);
    }

    public function testByPeriodAllStopsWhenNextOffsetMatchesRequestedOffset(): void
    {
        $mockClient = $this->makeMockClient();
        // Страница 1: offset=null -> nextOffset="A", есть документ.
        $mockClient->addResponse($this->jsonResponse(200, [
            'documents' => [$this->openShiftDocument(1)],
            'paging' => ['nextOffset' => 'A'],
        ]));
        // Страница 2: offset="A" -> nextOffset снова "A" (не сдвинулся), documents пуст — цикл должен остановиться.
        $mockClient->addResponse($this->jsonResponse(200, [
            'documents' => [],
            'paging' => ['nextOffset' => 'A'],
        ]));
        $api = new DocumentsApi($this->makeTransport($mockClient));

        $documents = iterator_to_array(
            $api->byPeriodAll('org-id', 'kkt-id', new \DateTimeImmutable(), new \DateTimeImmutable()),
        );

        self::assertCount(1, $documents);
        self::assertCount(2, $mockClient->getRequests());
    }

    public function testByPeriodAllContinuesWhenDocumentsEmptyButOffsetStillMoving(): void
    {
        // Ключевой случай из docs/ofd-receipts-design.md §6: пустой documents НЕ означает "конец",
        // если nextOffset продолжает отличаться от переданного offset — курсор ещё двигается.
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonResponse(200, [
            'documents' => [],
            'paging' => ['nextOffset' => 'A'],
        ]));
        $mockClient->addResponse($this->jsonResponse(200, [
            'documents' => [$this->openShiftDocument(1)],
            'paging' => ['nextOffset' => 'B'],
        ]));
        $mockClient->addResponse($this->jsonResponse(200, [
            'documents' => [],
            'paging' => ['nextOffset' => 'B'],
        ]));
        $api = new DocumentsApi($this->makeTransport($mockClient));

        $documents = iterator_to_array(
            $api->byPeriodAll('org-id', 'kkt-id', new \DateTimeImmutable(), new \DateTimeImmutable()),
        );

        self::assertCount(1, $documents);
        self::assertCount(3, $mockClient->getRequests());
    }

    public function testByPeriodAllStopsImmediatelyWhenThereIsNoPagingAtAll(): void
    {
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonResponse(200, [
            'documents' => [$this->openShiftDocument(1)],
        ]));
        $api = new DocumentsApi($this->makeTransport($mockClient));

        $documents = iterator_to_array(
            $api->byPeriodAll('org-id', 'kkt-id', new \DateTimeImmutable(), new \DateTimeImmutable()),
        );

        self::assertCount(1, $documents);
        self::assertCount(1, $mockClient->getRequests());
    }

    public function testGetBuildsCorrectPathAndParsesSingleDocument(): void
    {
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonResponse(200, $this->openShiftDocument(42)));
        $api = new DocumentsApi($this->makeTransport($mockClient));

        $document = $api->get('org-id', 'kkt-id', 'doc-id');

        self::assertSame(DocumentType::OpenShift, $document->getType());
        self::assertSame(
            '/v2/organizations/org-id/cashboxes/kkt-id/documents/doc-id',
            $this->lastRequest($mockClient)->getUri()->getPath(),
        );
    }
}
