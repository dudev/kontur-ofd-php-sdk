<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Document;

/** Одна страница `documents/by-period` — `nextOffset` `null` означает, что в ответе не было `paging.nextOffset` вовсе (см. `DocumentsApi::byPeriod()`). */
final readonly class DocumentsPage
{
    /** @param list<Document> $documents */
    public function __construct(
        public array $documents,
        public ?string $nextOffset,
    ) {}
}
