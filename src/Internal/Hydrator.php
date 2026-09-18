<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Internal;

/**
 * @internal Не публичный API SDK — общее узкое приведение mixed из json_decode() к ожидаемым
 * типам при сборке value-объектов из ответа API. Единое место, чтобы каждый `fromArray()` не
 * дублировал одни и те же проверки `is_string`/`is_int`/`is_array`.
 */
final class Hydrator
{
    /** @param array<string, mixed> $data */
    public static function string(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        return is_string($value) ? $value : '';
    }

    /** @param array<string, mixed> $data */
    public static function nullableString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /** @param array<string, mixed> $data */
    public static function int(array $data, string $key): int
    {
        $value = $data[$key] ?? null;

        return is_int($value) ? $value : 0;
    }

    /** @param array<string, mixed> $data */
    public static function nullableInt(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;

        return is_int($value) ? $value : null;
    }

    /** @param array<string, mixed> $data */
    public static function bool(array $data, string $key): bool
    {
        $value = $data[$key] ?? null;

        return is_bool($value) ? $value : false;
    }

    /**
     * Значение под ключом как JSON-объект (обычный PHP-массив со строковыми ключами) — для
     * вложенных структур вроде `paging`/`fiscalDrive`. `null`/не-массив/список — пустой массив,
     * не исключение: часть полей API документированы как «необязательные».
     *
     * @return array<string, mixed>
     */
    public static function object(mixed $value): array
    {
        return is_array($value) ? self::filterStringKeys($value) : [];
    }

    /**
     * Значение под ключом как список JSON-объектов — для `items`/`addresses`/`fiscalDrives`/
     * `documents` и т.п. Элементы, которые внезапно не объекты, тихо отбрасываются — тот же
     * принцип, что и у остальных методов: не падать на неожиданной форме ответа, а лучше отдать
     * пустую/усечённую структуру, чем бросить исключение на месте, не относящемся к делу вызова.
     *
     * @return list<array<string, mixed>>
     */
    public static function listOfObjects(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $result[] = self::filterStringKeys($item);
            }
        }

        return $result;
    }

    /**
     * @param array<mixed> $data
     * @return array<string, mixed>
     */
    private static function filterStringKeys(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
