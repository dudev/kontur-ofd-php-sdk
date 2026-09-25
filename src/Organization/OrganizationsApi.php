<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Organization;

use Dudev\KonturOfdPhpSdk\Http\Transport;
use Dudev\KonturOfdPhpSdk\Internal\Hydrator;

final readonly class OrganizationsApi
{
    public function __construct(private Transport $transport)
    {
    }

    /**
     * Все организации, доступные по ключу интегратора (`http/organizations.rst`). Пустой список —
     * ключ выдан, но доступ ни к одной организации ещё не настроен (или настройка не применилась).
     *
     * @return list<Organization>
     */
    public function list(): array
    {
        $response = $this->transport->getFromDataHost('/v2/organizations');

        return array_map(Organization::fromArray(...), Hydrator::listOfObjects($response));
    }

    public function get(string $organizationId): Organization
    {
        $response = $this->transport->getFromDataHost(sprintf('/v2/organizations/%s', $organizationId));

        return Organization::fromArray(Hydrator::object($response));
    }
}
