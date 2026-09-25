<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Cashbox;

use Dudev\KonturOfdPhpSdk\Internal\Hydrator;

/** Касса организации (`http/cashboxes.rst`, `http/cashbox.rst`). `regNumber` — натуральный ключ (`kktRegId` в путях остальных методов). */
final readonly class Cashbox
{
    /**
     * @param list<CashboxAddress> $addresses история адресов размещения, в т.ч. текущий
     * @param list<FiscalDrive> $fiscalDrives история фискальных накопителей, в т.ч. текущий
     */
    public function __construct(
        public string $regNumber,
        public string $serialNumber,
        public ?string $address,
        public array $addresses,
        public string $name,
        public string $modelName,
        public ?string $kpp,
        public ?string $lastDocumentTimestamp,
        public ?string $lastCashReceiptTimestamp,
        public ?FiscalDrive $fiscalDrive,
        public array $fiscalDrives,
        public ?string $salesPointName,
        public ?string $permissionFrom,
        public ?string $permissionTo,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $fiscalDriveData = $data['fiscalDrive'] ?? null;

        return new self(
            regNumber: Hydrator::string($data, 'regNumber'),
            serialNumber: Hydrator::string($data, 'serialNumber'),
            address: Hydrator::nullableString($data, 'address'),
            addresses: array_map(CashboxAddress::fromArray(...), Hydrator::listOfObjects($data['addresses'] ?? null)),
            name: Hydrator::string($data, 'name'),
            modelName: Hydrator::string($data, 'modelName'),
            kpp: Hydrator::nullableString($data, 'kpp'),
            lastDocumentTimestamp: Hydrator::nullableString($data, 'lastDocumentTimestamp'),
            lastCashReceiptTimestamp: Hydrator::nullableString($data, 'lastCashReceiptTimestamp'),
            fiscalDrive: is_array($fiscalDriveData) ? FiscalDrive::fromArray(Hydrator::object($fiscalDriveData)) : null,
            fiscalDrives: array_map(
                FiscalDrive::fromArray(...),
                Hydrator::listOfObjects($data['fiscalDrives'] ?? null),
            ),
            salesPointName: Hydrator::nullableString($data, 'salesPointName'),
            permissionFrom: Hydrator::nullableString($data, 'permissionFrom'),
            permissionTo: Hydrator::nullableString($data, 'permissionTo'),
        );
    }
}
