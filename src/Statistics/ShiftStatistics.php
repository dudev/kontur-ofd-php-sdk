<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Statistics;

use Dudev\KonturOfdPhpSdk\Internal\Hydrator;

/**
 * Агрегаты по чекам (БСО) за одну смену (`http/cashboxes-statistics-by-shifts.rst`). Для смены,
 * которая не целиком попала в запрошенный период, суммы — только за его часть; `shiftOpen`/
 * `shiftClose` — `null`, если отчёт об открытии/закрытии смены в период не попал (или смена ещё
 * не закрыта).
 */
final readonly class ShiftStatistics
{
    public function __construct(
        public int $shiftNumber,
        public ?string $shiftOpen,
        public ?string $shiftClose,
        public OperationTotals $sell,
        public OperationTotals $returnSell,
        public OperationTotals $buy,
        public OperationTotals $returnBuy,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            shiftNumber: Hydrator::int($data, 'shiftNumber'),
            shiftOpen: Hydrator::nullableString($data, 'shiftOpen'),
            shiftClose: Hydrator::nullableString($data, 'shiftClose'),
            sell: OperationTotals::fromArray(Hydrator::object($data['sell'] ?? null)),
            returnSell: OperationTotals::fromArray(Hydrator::object($data['returnSell'] ?? null)),
            buy: OperationTotals::fromArray(Hydrator::object($data['buy'] ?? null)),
            returnBuy: OperationTotals::fromArray(Hydrator::object($data['returnBuy'] ?? null)),
        );
    }
}
