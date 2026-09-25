<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Auth;

/**
 * Результат `authenticate-by-cert` — `encryptedKey` нужно расшифровать приватным ключом
 * сертификата (ГОСТ 28147-89, вне зоны ответственности SDK, см. docs/roadmap.md, открытый
 * вопрос 2), результат расшифровки передать в `AuthClient::approveCert($approveCertUrl, ...)`.
 */
final readonly class EncryptedKeyResult
{
    public function __construct(
        public string $encryptedKey,
        public string $approveCertUrl,
    ) {}
}
