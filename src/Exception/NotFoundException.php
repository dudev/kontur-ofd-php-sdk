<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Exception;

/** HTTP 404 — организация не найдена (`404000`) или документ/ФН не найден (`404001`). */
class NotFoundException extends KonturOfdException {}
