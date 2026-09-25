<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Tests\Organization;

use Dudev\KonturOfdPhpSdk\Organization\OrganizationsApi;
use Dudev\KonturOfdPhpSdk\Tests\Support\MakesTransport;
use PHPUnit\Framework\TestCase;

final class OrganizationsApiTest extends TestCase
{
    use MakesTransport;

    public function testListReturnsTypedOrganizations(): void
    {
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonListResponse(200, [
            [
                'id' => 'c2e3a34c-823f-4b1e-a9g1-d94fa40c22a6',
                'inn' => '6699000000',
                'kpp' => '669901001',
                'ogrn' => '000000000000000',
                'shortName' => 'ООО Тестовая организация',
                'fullName' => 'Общество с органиченной ответственностью '
                    . 'Тестовая организация',
            ],
        ]));
        $api = new OrganizationsApi($this->makeTransport($mockClient));

        $organizations = $api->list();

        self::assertCount(1, $organizations);
        self::assertSame('c2e3a34c-823f-4b1e-a9g1-d94fa40c22a6', $organizations[0]->id);
        self::assertSame('6699000000', $organizations[0]->inn);
        self::assertSame('ООО Тестовая организация', $organizations[0]->shortName);
    }

    public function testListReturnsEmptyArrayWhenNoAccess(): void
    {
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonListResponse(200, []));
        $api = new OrganizationsApi($this->makeTransport($mockClient));

        self::assertSame([], $api->list());
    }

    public function testGetReturnsSingleOrganization(): void
    {
        $mockClient = $this->makeMockClient();
        $mockClient->addResponse($this->jsonResponse(200, [
            'id' => 'c2e3a34c-823f-4b1e-a9g1-d94fa40c22a6',
            'inn' => '6699000000',
            'kpp' => '669901001',
            'ogrn' => '000000000000000',
            'shortName' => 'ООО Тестовая организация',
            'fullName' => 'Полное имя',
        ]));
        $api = new OrganizationsApi($this->makeTransport($mockClient));

        $organization = $api->get('c2e3a34c-823f-4b1e-a9g1-d94fa40c22a6');

        self::assertSame('Полное имя', $organization->fullName);
    }
}
