<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Cashbox;

use Dudev\KonturOfdPhpSdk\Internal\Hydrator;

/** Фискальный накопитель кассы — текущий (`Cashbox::$fiscalDrive`) или один из истории (`Cashbox::$fiscalDrives`). */
final readonly class FiscalDrive
{
    public function __construct(
        public string $fiscalDriverNumber,
        public ?string $earliestDocumentTimestamp,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            fiscalDriverNumber: Hydrator::string($data, 'fiscalDriverNumber'),
            earliestDocumentTimestamp: Hydrator::nullableString($data, 'earliestDocumentTimestamp'),
        );
    }
}
