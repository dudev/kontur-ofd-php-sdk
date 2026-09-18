<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Document;

use Dudev\KonturOfdPhpSdk\Internal\Hydrator;

/** Собирает `Document` из обёртки `{"<тип>": {<поля>}}`, общей для всех методов получения документов (`Methods.rst`). */
final class DocumentFactory
{
    private const RECEIPT_LIKE = [
        DocumentType::Receipt,
        DocumentType::ReceiptCorrection,
        DocumentType::Bso,
        DocumentType::BsoCorrection,
    ];

    /** @param array<string, mixed> $wrapped единственный ключ — тип документа */
    public static function fromWrappedArray(array $wrapped): Document
    {
        foreach ($wrapped as $typeValue => $data) {
            $type = DocumentType::tryFrom($typeValue);
            if ($type === null || !is_array($data)) {
                continue;
            }

            $fields = Hydrator::object($data);

            return in_array($type, self::RECEIPT_LIKE, true)
                ? Receipt::fromArray($type, $fields)
                : new GenericDocument($type, $fields);
        }

        throw new \UnexpectedValueException('Kontur.OFD document response has no recognizable document-type key');
    }
}
