<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Tests\Internal;

use Dudev\KonturOfdPhpSdk\Internal\Hydrator;
use PHPUnit\Framework\TestCase;

final class HydratorTest extends TestCase
{
    public function testStringReturnsEmptyForMissingOrWrongType(): void
    {
        self::assertSame('foo', Hydrator::string(['a' => 'foo'], 'a'));
        self::assertSame('', Hydrator::string(['a' => 42], 'a'));
        self::assertSame('', Hydrator::string([], 'a'));
    }

    public function testNullableStringReturnsNullForMissingOrWrongType(): void
    {
        self::assertSame('foo', Hydrator::nullableString(['a' => 'foo'], 'a'));
        self::assertNull(Hydrator::nullableString(['a' => 42], 'a'));
        self::assertNull(Hydrator::nullableString([], 'a'));
    }

    public function testIntReturnsZeroForMissingOrWrongType(): void
    {
        self::assertSame(42, Hydrator::int(['a' => 42], 'a'));
        self::assertSame(0, Hydrator::int(['a' => '42'], 'a'));
        self::assertSame(0, Hydrator::int([], 'a'));
    }

    public function testFiscalSignBeyondInt32RangeSurvivesAsInt(): void
    {
        // fiscalSign в реальных данных Контур.ОФД превышает 2^31 (см. docs/roadmap.md) — на
        // 64-битном PHP это по-прежнему обычный int, не строка.
        self::assertSame(3423453811, Hydrator::int(['fiscalSign' => 3423453811], 'fiscalSign'));
    }

    public function testBoolReturnsFalseForMissingOrWrongType(): void
    {
        self::assertTrue(Hydrator::bool(['a' => true], 'a'));
        self::assertFalse(Hydrator::bool(['a' => 'true'], 'a'));
        self::assertFalse(Hydrator::bool([], 'a'));
    }

    public function testObjectReturnsEmptyArrayForNonArray(): void
    {
        self::assertSame(['x' => 1], Hydrator::object(['x' => 1]));
        self::assertSame([], Hydrator::object('not an array'));
        self::assertSame([], Hydrator::object(null));
    }

    public function testObjectDropsNonStringKeys(): void
    {
        self::assertSame(['a' => 1], Hydrator::object([0 => 'ignored', 'a' => 1]));
    }

    public function testListOfObjectsFiltersOutNonArrayItems(): void
    {
        $result = Hydrator::listOfObjects([['a' => 1], 'not an object', ['b' => 2]]);

        self::assertSame([['a' => 1], ['b' => 2]], $result);
    }

    public function testListOfObjectsReturnsEmptyForNonArray(): void
    {
        self::assertSame([], Hydrator::listOfObjects(null));
        self::assertSame([], Hydrator::listOfObjects('nope'));
    }
}
