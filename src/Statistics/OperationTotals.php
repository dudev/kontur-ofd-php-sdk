<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Statistics;

use Dudev\KonturOfdPhpSdk\Internal\Hydrator;

/**
 * Агрегат по чекам (БСО) одного признака расчёта — `sell`/`returnSell`/`buy`/`returnBuy` в ответах
 * `statistics/cash-receipt/*`. Суммы — в копейках. Отсутствующий в ответе блок разбирается в нули
 * (так же API описывает день/смену без чеков).
 */
final readonly class OperationTotals
{
    /**
     * @param array<string, int> $ndsBreakdown блок `nds` как есть: ключ — `rate20`/`calculatedWithRate20`/...,
     *                                         значение — сумма в копейках. Не фиксированный набор полей:
     *                                         документация перечисляет ставки 10/18/20, новые ставки
     *                                         (22/7/5 и т.п.) придут сюда же без правок SDK
     */
    public function __construct(
        public int $totalKopeks,
        public int $cashTotalKopeks,
        public int $cashlessTotalKopeks,
        public int $totalWithNds0Kopeks,
        public int $totalWithNdsFreeKopeks,
        public int $count,
        public array $ndsBreakdown,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $ndsBreakdown = [];
        foreach (Hydrator::object($data['nds'] ?? null) as $key => $value) {
            if (is_int($value)) {
                $ndsBreakdown[$key] = $value;
            }
        }

        return new self(
            totalKopeks: Hydrator::int($data, 'total'),
            cashTotalKopeks: Hydrator::int($data, 'cashTotal'),
            cashlessTotalKopeks: Hydrator::int($data, 'cashlessTotal'),
            totalWithNds0Kopeks: Hydrator::int($data, 'totalWithNds0'),
            totalWithNdsFreeKopeks: Hydrator::int($data, 'totalWithNdsFree'),
            count: Hydrator::int($data, 'count'),
            ndsBreakdown: $ndsBreakdown,
        );
    }
}
