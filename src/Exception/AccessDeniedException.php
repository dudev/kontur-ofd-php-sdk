<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Exception;

/** HTTP 403 — нет доступа к организации/кассе/периоду (`403000`/`403002`/`403003`). Не транзиентная ошибка — доступ мог быть отозван. */
class AccessDeniedException extends KonturOfdException {}
