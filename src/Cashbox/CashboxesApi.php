<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Cashbox;

use Dudev\KonturOfdPhpSdk\Http\Transport;
use Dudev\KonturOfdPhpSdk\Internal\Hydrator;

final readonly class CashboxesApi
{
    public function __construct(private Transport $transport)
    {
    }

    /**
     * Все кассы организации, доступные интегратору (`http/cashboxes.rst`).
     *
     * @return list<Cashbox>
     */
    public function list(string $organizationId): array
    {
        $response = $this->transport->getFromDataHost(sprintf('/v2/organizations/%s/cashboxes', $organizationId));

        return array_map(Cashbox::fromArray(...), Hydrator::listOfObjects($response));
    }

    public function get(string $organizationId, string $kktRegId): Cashbox
    {
        $response = $this->transport->getFromDataHost(
            sprintf('/v2/organizations/%s/cashboxes/%s', $organizationId, $kktRegId),
        );

        return Cashbox::fromArray(Hydrator::object($response));
    }
}
