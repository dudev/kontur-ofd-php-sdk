<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Document;

use Dudev\KonturOfdPhpSdk\Http\Transport;
use Dudev\KonturOfdPhpSdk\Internal\Hydrator;

final readonly class DocumentsApi
{
    public function __construct(private Transport $transport)
    {
    }

    /**
     * Одна страница `documents/by-period` (`http/documents-by-period.rst`). `limit` по умолчанию
     * и максимум на стороне API — 1000 (значение больше 1000 тихо срезается до 1000, не ошибка).
     *
     * @param list<DocumentType> $types пустой список — все типы, включая служебные (см. документацию)
     */
    public function byPeriod(
        string $organizationId,
        string $kktRegId,
        \DateTimeImmutable $dateFrom,
        \DateTimeImmutable $dateTo,
        array $types = [],
        ?string $offset = null,
        ?int $limit = null,
    ): DocumentsPage {
        $query = [
            'dateFrom' => $dateFrom->format('Y-m-d\TH:i:s'),
            'dateTo' => $dateTo->format('Y-m-d\TH:i:s'),
        ];
        if ($types !== []) {
            $query['types'] = implode(',', array_map(static fn (DocumentType $type): string => $type->value, $types));
        }
        if ($offset !== null) {
            $query['offset'] = $offset;
        }
        if ($limit !== null) {
            $query['limit'] = (string) $limit;
        }

        $response = $this->transport->getFromDataHost(
            sprintf('/v2/organizations/%s/cashboxes/%s/documents/by-period', $organizationId, $kktRegId),
            $query,
        );

        $documents = array_map(
            DocumentFactory::fromWrappedArray(...),
            Hydrator::listOfObjects($response['documents'] ?? null),
        );

        $paging = Hydrator::object($response['paging'] ?? null);

        return new DocumentsPage($documents, Hydrator::nullableString($paging, 'nextOffset'));
    }

    /**
     * Ленивая постраничная выдача за весь период. Останавливается, когда `nextOffset` ответа
     * совпадёт с `offset`, который был передан в запросе — надёжнее, чем ориентироваться только на
     * пустой `documents: []` (официальная документация описывает именно так, но независимая
     * реализация показывает, что курсор может ещё двигаться при пустой странице — см.
     * `docs/ofd-receipts-design.md` в relsy-backend, §6). Не гарантия, что новых документов не
     * появится, если период включает «сейчас» — документы от кассы могут доезжать с задержкой,
     * это забота вызывающего (окно с нахлёстом при повторных вызовах), не этого метода.
     *
     * @param list<DocumentType> $types
     * @return \Generator<int, Document>
     */
    public function byPeriodAll(
        string $organizationId,
        string $kktRegId,
        \DateTimeImmutable $dateFrom,
        \DateTimeImmutable $dateTo,
        array $types = [],
        ?int $limit = null,
    ): \Generator {
        $offset = null;

        do {
            $page = $this->byPeriod($organizationId, $kktRegId, $dateFrom, $dateTo, $types, $offset, $limit);
            yield from $page->documents;

            $previousOffset = $offset;
            $offset = $page->nextOffset;
        } while ($offset !== null && $offset !== $previousOffset);
    }

    public function get(string $organizationId, string $kktRegId, string $documentId): Document
    {
        $response = $this->transport->getFromDataHost(
            sprintf('/v2/organizations/%s/cashboxes/%s/documents/%s', $organizationId, $kktRegId, $documentId),
        );

        return DocumentFactory::fromWrappedArray(Hydrator::object($response));
    }
}
