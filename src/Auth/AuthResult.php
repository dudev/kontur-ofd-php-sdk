<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Auth;

/** Результат `authenticate-by-pass`/`approve-cert` — `refreshToken` документация помечает как «не используется в дальнейшем», но отдаём как есть. */
final readonly class AuthResult
{
    public function __construct(
        public string $sid,
        public ?string $refreshToken = null,
    ) {}
}
