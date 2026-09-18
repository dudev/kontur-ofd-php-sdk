<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Tests\Document;

use Dudev\KonturOfdPhpSdk\Document\DocumentFactory;
use Dudev\KonturOfdPhpSdk\Document\DocumentType;
use Dudev\KonturOfdPhpSdk\Document\GenericDocument;
use Dudev\KonturOfdPhpSdk\Document\Receipt;
use PHPUnit\Framework\TestCase;

final class DocumentFactoryTest extends TestCase
{
    public function testReceiptWrapperIsParsedIntoTypedReceipt(): void
    {
        $document = DocumentFactory::fromWrappedArray([
            'receipt' => [
                'receiptCode' => 3,
                'user' => 'ООО Золотой пятачок',
                'userInn' => '6699009482',
                'requestNumber' => 1,
                'dateTime' => '2018-08-27T10:13:51',
                'shiftNumber' => 367,
                'operationType' => 1,
                'taxationType' => 1,
                'operator' => 'Герман Илья',
                'kktRegId' => '0000000003065868',
                'fiscalDriveNumber' => '99990788607',
                'retailPlaceAddress' => 'г. Екатеринбург. ул. Малопрудная 5',
                'items' => [
                    ['name' => 'Ассорти овощное', 'price' => 5668, 'quantity' => 2, 'sum' => 11336],
                ],
                'nds18' => 1234,
                'totalSum' => 11336,
                'cashTotalSum' => 11336,
                'ecashTotalSum' => 0,
                'fiscalDocumentNumber' => 39090,
                'fiscalSign' => 3635260533,
                'id' => '00000000-0000-0000-0000-000000000000',
            ],
        ]);

        self::assertInstanceOf(Receipt::class, $document);
        self::assertSame(DocumentType::Receipt, $document->getType());
        self::assertSame('00000000-0000-0000-0000-000000000000', $document->id);
        self::assertSame(11336, $document->totalKopeks);
        self::assertSame(3635260533, $document->fiscalSign);
        self::assertCount(1, $document->items);
        self::assertSame('Ассорти овощное', $document->items[0]->name);
        self::assertSame(11336, $document->items[0]->sumKopeks);
        self::assertSame(['nds18' => 1234], $document->ndsBreakdown);
    }

    public function testBsoWrapperIsAlsoParsedAsReceipt(): void
    {
        $document = DocumentFactory::fromWrappedArray([
            'bso' => ['id' => 'x', 'user' => 'y', 'userInn' => 'z', 'requestNumber' => 1, 'dateTime' => 'd', 'shiftNumber' => 1, 'operationType' => 1, 'taxationType' => 1, 'operator' => 'o', 'kktRegId' => 'k', 'fiscalDriveNumber' => 'f', 'items' => [], 'totalSum' => 0, 'cashTotalSum' => 0, 'ecashTotalSum' => 0, 'fiscalDocumentNumber' => 1, 'fiscalSign' => 1],
        ]);

        self::assertInstanceOf(Receipt::class, $document);
        self::assertSame(DocumentType::Bso, $document->getType());
    }

    public function testOpenShiftWrapperFallsBackToGenericDocument(): void
    {
        $document = DocumentFactory::fromWrappedArray([
            'openShift' => ['code' => 2, 'shiftNumber' => 367, 'id' => '00000000-0000-0000-0000-000000000000'],
        ]);

        self::assertInstanceOf(GenericDocument::class, $document);
        self::assertSame(DocumentType::OpenShift, $document->getType());
        self::assertSame(367, $document->getRawData()['shiftNumber']);
    }

    public function testThrowsForUnrecognizedWrapper(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        DocumentFactory::fromWrappedArray(['someUnknownType' => ['x' => 1]]);
    }
}
