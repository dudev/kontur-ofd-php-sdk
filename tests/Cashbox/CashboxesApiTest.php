<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Tests\Cashbox;

use Dudev\KonturOfdPhpSdk\Cashbox\CashboxesApi;
use Dudev\KonturOfdPhpSdk\Tests\Support\MakesTransport;
use PHPUnit\Framework\TestCase;

final class CashboxesApiTest extends TestCase
{
    use MakesTransport;

    public function testListParsesFullCashboxShape(): void
    {
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonListResponse(200, [
            [
                'regNumber' => '0000800000000005',
                'serialNumber' => '00106700332501',
                'address' => 'г. Екатеринбург. ул. Малопрудная 5',
                'addresses' => [
                    ['address' => 'г.Москва Ул. победы д522', 'startDate' => '2019-09-12T00:00:00'],
                    ['address' => 'г. Екатеринбург. ул. Малопрудная 5', 'startDate' => '2020-01-19T00:00:00'],
                ],
                'name' => 'Касса 1',
                'modelName' => 'АТОЛ 30Ф',
                'kpp' => '669901001',
                'lastDocumentTimestamp' => '2019-10-25T00:00:00',
                'lastCashReceiptTimestamp' => '2019-10-25T00:00:00',
                'fiscalDrive' => [
                    'fiscalDriverNumber' => '9492452823423233',
                    'earliestDocumentTimestamp' => '2019-10-10T00:00:00',
                ],
                'fiscalDrives' => [
                    ['fiscalDriverNumber' => '4393456832322943', 'earliestDocumentTimestamp' => '2017-11-12T00:00:00'],
                    ['fiscalDriverNumber' => '9492452823423233', 'earliestDocumentTimestamp' => '2019-10-10T00:00:00'],
                ],
                'salesPointName' => 'Четвертая точка продаж',
                'permissionFrom' => '2018-12-12T00:00:00',
                'permissionTo' => '2018-12-14T14:14:41',
            ],
        ]));
        $api = new CashboxesApi($this->makeTransport($mockClient));

        $cashboxes = $api->list('org-id');

        self::assertCount(1, $cashboxes);
        $cashbox = $cashboxes[0];
        self::assertSame('0000800000000005', $cashbox->regNumber);
        self::assertCount(2, $cashbox->addresses);
        self::assertSame('г.Москва Ул. победы д522', $cashbox->addresses[0]->address);
        self::assertNotNull($cashbox->fiscalDrive);
        self::assertSame('9492452823423233', $cashbox->fiscalDrive->fiscalDriverNumber);
        self::assertCount(2, $cashbox->fiscalDrives);
        self::assertSame('Четвертая точка продаж', $cashbox->salesPointName);
    }

    public function testListToleratesMissingOptionalFields(): void
    {
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonListResponse(200, [
            ['regNumber' => 'X', 'serialNumber' => 'Y', 'name' => 'Касса', 'modelName' => 'M'],
        ]));
        $api = new CashboxesApi($this->makeTransport($mockClient));

        $cashboxes = $api->list('org-id');

        self::assertSame([], $cashboxes[0]->addresses);
        self::assertSame([], $cashboxes[0]->fiscalDrives);
        self::assertNull($cashboxes[0]->fiscalDrive);
        self::assertNull($cashboxes[0]->salesPointName);
    }

    public function testGetBuildsCorrectPath(): void
    {
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonResponse(200, ['regNumber' => 'X', 'serialNumber' => 'Y', 'name' => 'Касса', 'modelName' => 'M']));
        $api = new CashboxesApi($this->makeTransport($mockClient));

        $api->get('org-id', 'X');

        self::assertSame('/v2/organizations/org-id/cashboxes/X', $this->lastRequest($mockClient)->getUri()->getPath());
    }
}
