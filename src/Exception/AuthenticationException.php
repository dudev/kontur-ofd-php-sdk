<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Exception;

/** HTTP 401 — `auth.sid` не передан/протух (`401002`), либо не передан/неверен ключ интегратора (`401003`). */
class AuthenticationException extends KonturOfdException {}
