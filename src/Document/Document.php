<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Document;

/**
 * Общий интерфейс фискального документа. `Receipt` — единственный типизированный до конца
 * вариант сейчас (см. docs/roadmap.md, M4); остальные типы приходят как `GenericDocument`, пока
 * не появится реальная задача разбирать их подробно.
 */
interface Document
{
    public function getType(): DocumentType;

    /** @return array<string, mixed> */
    public function getRawData(): array;
}
