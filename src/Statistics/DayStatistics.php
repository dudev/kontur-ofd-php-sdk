<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Statistics;

use Dudev\KonturOfdPhpSdk\Internal\Hydrator;

/**
 * Агрегаты по чекам (БСО) за одни сутки (`http/cashboxes-statistics-by-days.rst`,
 * `http/organizations-statistics-by-days.rst`). Дня без открытия/закрытия смены и без чеков в
 * ответе нет вовсе; день с открытием/закрытием смены, но без чеков — есть, с нулями.
 */
final readonly class DayStatistics
{
    public function __construct(
        public string $date,
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
            date: Hydrator::string($data, 'date'),
            sell: OperationTotals::fromArray(Hydrator::object($data['sell'] ?? null)),
            returnSell: OperationTotals::fromArray(Hydrator::object($data['returnSell'] ?? null)),
            buy: OperationTotals::fromArray(Hydrator::object($data['buy'] ?? null)),
            returnBuy: OperationTotals::fromArray(Hydrator::object($data['returnBuy'] ?? null)),
        );
    }
}
