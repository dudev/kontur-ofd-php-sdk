<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Organization;

use Dudev\KonturOfdPhpSdk\Internal\Hydrator;

/** Организация, доступная по ключу интегратора (`http/organizations.rst`, `http/organization.rst`). */
final readonly class Organization
{
    public function __construct(
        public string $id,
        public string $inn,
        public string $kpp,
        public string $ogrn,
        public string $shortName,
        public string $fullName,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Hydrator::string($data, 'id'),
            inn: Hydrator::string($data, 'inn'),
            kpp: Hydrator::string($data, 'kpp'),
            ogrn: Hydrator::string($data, 'ogrn'),
            shortName: Hydrator::string($data, 'shortName'),
            fullName: Hydrator::string($data, 'fullName'),
        );
    }
}
