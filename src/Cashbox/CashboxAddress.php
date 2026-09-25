<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Cashbox;

use Dudev\KonturOfdPhpSdk\Internal\Hydrator;

/** Один адрес из истории размещения кассы (`Cashbox::$addresses`) — касса могла переезжать. */
final readonly class CashboxAddress
{
    public function __construct(
        public string $address,
        public ?string $startDate,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            address: Hydrator::string($data, 'address'),
            startDate: Hydrator::nullableString($data, 'startDate'),
        );
    }
}
