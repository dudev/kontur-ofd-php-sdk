<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Document;

use Dudev\KonturOfdPhpSdk\Internal\Hydrator;

/**
 * Предмет расчёта чека/БСО (`Structures/ReceiptBSO.rst`) — `priceKopeks`/`sumKopeks` в копейках.
 * `ndsType` — строковый код применённой ставки НДС (`nds20`/`ndsCalculated18`/... — см. таблицу в
 * той же секции документации), не сама сумма НДС по позиции — те доступны через `rawData`, если
 * понадобятся (несколько `ndsCalculated*`/`nds*`-полей одновременно, редко нужны все сразу).
 */
final readonly class ReceiptItem
{
    /** @param array<string, mixed> $rawData */
    public function __construct(
        public string $name,
        public ?string $barcode,
        public int $priceKopeks,
        public int $quantity,
        public ?int $paymentMode,
        public ?int $paymentSubject,
        public int $sumKopeks,
        public ?string $ndsType,
        public array $rawData,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: Hydrator::string($data, 'name'),
            barcode: Hydrator::nullableString($data, 'barcode'),
            priceKopeks: Hydrator::int($data, 'price'),
            quantity: Hydrator::int($data, 'quantity'),
            paymentMode: Hydrator::nullableInt($data, 'paymentMode'),
            paymentSubject: Hydrator::nullableInt($data, 'paymentSubject'),
            sumKopeks: Hydrator::int($data, 'sum'),
            ndsType: Hydrator::nullableString($data, 'ndsType'),
            rawData: $data,
        );
    }
}
