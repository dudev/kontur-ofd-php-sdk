<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Document;

/** Фискальный документ типа, который SDK ещё не разбирает подробно (отчёты смен/накопителя и т.п.) — исходные поля доступны через getRawData(). */
final readonly class GenericDocument implements Document
{
    /** @param array<string, mixed> $rawData */
    public function __construct(
        private DocumentType $type,
        private array $rawData,
    ) {
    }

    public function getType(): DocumentType
    {
        return $this->type;
    }

    /** @return array<string, mixed> */
    public function getRawData(): array
    {
        return $this->rawData;
    }
}
