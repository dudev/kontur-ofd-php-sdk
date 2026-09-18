<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Document;

use Dudev\KonturOfdPhpSdk\Internal\Hydrator;

/**
 * Кассовый чек или БСО (`Structures/ReceiptBSO.rst`) — одна структура для четырёх типов документа
 * (`receipt`/`receiptCorrection`/`bso`/`bsoCorrection`), различаются только внешним маркером-ключом
 * в ответе API (см. `DocumentFactory`), не полем внутри самой структуры. Суммы — в копейках.
 *
 * Поля платёжных агентов (`paymentAgentType`/`paymentAgentPhone`/...), `buyerAddress`/
 * `senderAddress`/`addressToCheckFiscalSign`/`cashReceiptProperty`/`properties`/`stornoItems` —
 * сознательно не вынесены в типизированные свойства (нужны только кассам, работающим как
 * платёжный агент, — редкий случай), доступны через `getRawData()`.
 */
final readonly class Receipt implements Document
{
    /**
     * @param list<ReceiptItem> $items
     * @param array<string, int> $ndsBreakdown ключ — `nds20`/`nds18`/.../`ndsCalculated22`, значение — сумма в копейках; только непустые ставки
     * @param array<string, mixed> $rawData
     */
    public function __construct(
        private DocumentType $type,
        public string $id,
        public string $user,
        public string $userInn,
        public ?string $buyerInn,
        public int $requestNumber,
        public string $dateTime,
        public ?string $receiveDateTimeUtc,
        public int $shiftNumber,
        public int $operationType,
        public int $taxationType,
        public string $operator,
        public string $kktRegId,
        public string $fiscalDriveNumber,
        public ?string $retailPlaceAddress,
        public array $items,
        public int $totalKopeks,
        public int $cashTotalKopeks,
        public int $ecashTotalKopeks,
        public int $fiscalDocumentNumber,
        public int $fiscalSign,
        public array $ndsBreakdown,
        public array $rawData,
    ) {
    }

    public function getType(): DocumentType
    {
        return $this->type;
    }

    /** @return array<string, mixed> */
    public function getRawData(): array
    {
        return $this->rawData;
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(DocumentType $type, array $data): self
    {
        return new self(
            type: $type,
            id: Hydrator::string($data, 'id'),
            user: Hydrator::string($data, 'user'),
            userInn: Hydrator::string($data, 'userInn'),
            buyerInn: Hydrator::nullableString($data, 'buyerInn'),
            requestNumber: Hydrator::int($data, 'requestNumber'),
            dateTime: Hydrator::string($data, 'dateTime'),
            receiveDateTimeUtc: Hydrator::nullableString($data, 'receiveDateTimeUtc'),
            shiftNumber: Hydrator::int($data, 'shiftNumber'),
            operationType: Hydrator::int($data, 'operationType'),
            taxationType: Hydrator::int($data, 'taxationType'),
            operator: Hydrator::string($data, 'operator'),
            kktRegId: Hydrator::string($data, 'kktRegId'),
            fiscalDriveNumber: Hydrator::string($data, 'fiscalDriveNumber'),
            retailPlaceAddress: Hydrator::nullableString($data, 'retailPlaceAddress'),
            items: array_map(ReceiptItem::fromArray(...), Hydrator::listOfObjects($data['items'] ?? null)),
            totalKopeks: Hydrator::int($data, 'totalSum'),
            cashTotalKopeks: Hydrator::int($data, 'cashTotalSum'),
            ecashTotalKopeks: Hydrator::int($data, 'ecashTotalSum'),
            fiscalDocumentNumber: Hydrator::int($data, 'fiscalDocumentNumber'),
            fiscalSign: Hydrator::int($data, 'fiscalSign'),
            ndsBreakdown: self::extractNdsBreakdown($data),
            rawData: $data,
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, int>
     */
    private static function extractNdsBreakdown(array $data): array
    {
        $keys = [
            'nds22', 'nds20', 'nds18', 'nds10', 'nds7', 'nds5', 'nds0', 'ndsNo',
            'ndsCalculated22', 'ndsCalculated20', 'ndsCalculated18',
            'ndsCalculated10', 'ndsCalculated7', 'ndsCalculated5',
        ];

        $result = [];
        foreach ($keys as $key) {
            $value = $data[$key] ?? null;
            if (is_int($value)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
