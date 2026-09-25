<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Statistics;

use Dudev\KonturOfdPhpSdk\Http\Transport;
use Dudev\KonturOfdPhpSdk\Internal\Hydrator;

/**
 * Готовые агрегаты по чекам (БСО) — `statistics/cash-receipt/*`. Период — по датам, обе границы
 * включительно; время у `$dateFrom`/`$dateTo` не учитывается (API принимает только дату).
 */
final readonly class StatisticsApi
{
    public function __construct(private Transport $transport)
    {
    }

    /**
     * По кассе, в разрезе суток (`http/cashboxes-statistics-by-days.rst`).
     *
     * @return list<DayStatistics>
     */
    public function cashboxByDays(
        string $organizationId,
        string $kktRegId,
        \DateTimeImmutable $dateFrom,
        \DateTimeImmutable $dateTo,
    ): array {
        $response = $this->transport->getFromDataHost(
            sprintf('/v2/organizations/%s/cashboxes/%s/statistics/cash-receipt/by-days', $organizationId, $kktRegId),
            self::periodQuery($dateFrom, $dateTo),
        );

        return array_map(DayStatistics::fromArray(...), Hydrator::listOfObjects($response['items'] ?? null));
    }

    /**
     * По кассе, в разрезе смен (`http/cashboxes-statistics-by-shifts.rst`).
     *
     * @return list<ShiftStatistics>
     */
    public function cashboxByShifts(
        string $organizationId,
        string $kktRegId,
        \DateTimeImmutable $dateFrom,
        \DateTimeImmutable $dateTo,
    ): array {
        $response = $this->transport->getFromDataHost(
            sprintf('/v2/organizations/%s/cashboxes/%s/statistics/cash-receipt/by-shifts', $organizationId, $kktRegId),
            self::periodQuery($dateFrom, $dateTo),
        );

        return array_map(ShiftStatistics::fromArray(...), Hydrator::listOfObjects($response['shifts'] ?? null));
    }

    /**
     * По всей организации, в разрезе суток (`http/organizations-statistics-by-days.rst`). Доступен
     * только интегратору с доступом ко всем кассам организации, включая будущие — иначе API
     * отвечает ошибкой доступа (`AccessDeniedException`).
     *
     * @return list<DayStatistics>
     */
    public function organizationByDays(
        string $organizationId,
        \DateTimeImmutable $dateFrom,
        \DateTimeImmutable $dateTo,
    ): array {
        $response = $this->transport->getFromDataHost(
            sprintf('/v2/organizations/%s/statistics/cash-receipt/by-days', $organizationId),
            self::periodQuery($dateFrom, $dateTo),
        );

        return array_map(DayStatistics::fromArray(...), Hydrator::listOfObjects($response['items'] ?? null));
    }

    /** @return array<string, string> */
    private static function periodQuery(\DateTimeImmutable $dateFrom, \DateTimeImmutable $dateTo): array
    {
        return [
            'from' => $dateFrom->format('Y-m-d'),
            'to' => $dateTo->format('Y-m-d'),
        ];
    }
}
