<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Exception;

/**
 * Базовое исключение SDK. `errorCodeId`/`moreInfo` — как есть в теле ответа API
 * (`Responses.rst`), `errorCode` (числовой) сознательно не хранится — сама документация помечает
 * его устаревшим в пользу `errorCodeId`.
 */
class KonturOfdException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $errorCodeId = null,
        public readonly ?string $moreInfo = null,
        int $httpStatusCode = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $httpStatusCode, $previous);
    }

    public function getHttpStatusCode(): int
    {
        return $this->code;
    }
}
